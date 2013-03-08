<?php
/**
 * DokuWiki Plugin strata (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * The triples helper is responsible for querying.
 */
class helper_plugin_strata_triples extends DokuWiki_Plugin {
    public static $readable = 'data';
    public static $writable = 'data';

    function __construct() {
        $this->_initialize();
    }

    function getMethods() {
        $result = array();
        return $result;
    }

    /**
     * Expands tokens in the DSN.
     * 
     * @param str string the string to process
     * @return a string with replaced tokens
     */
    function _expandTokens($str) {
        global $conf;
        $tokens    = array('@METADIR@');
        $replacers = array($conf['metadir']);
        return str_replace($tokens,$replacers,$str);
    }

    /**
     * Initializes the triple helper.
     * 
     * @param dsn string an optional alternative DSN
     * @return true if initialization succeeded, false otherwise
     */
    function _initialize() {
        // load default DSN
        $dsn = $this->getConf('default_dsn');
        $dsn = $this->_expandTokens($dsn);

        $this->_dsn = $dsn;

        // construct driver
        list($driver,$connection) = explode(':',$dsn,2);
        $driverFile = DOKU_PLUGIN."strata/driver/$driver.php";
        if(!@file_exists($driverFile)) {
            msg(sprintf($this->getLang('error_triples_nodriver'), $driver), -1);
            return false;
        }
        require_once($driverFile);
        $driverClass = "plugin_strata_driver_$driver";
        $this->_db = new $driverClass($this->getConf('debug'));

        // connect driver
        if(!$this->_db->connect($dsn)) {
            return false;
        }

        // initialize database if necessary
        if(!$this->_db->isInitialized()) {
            $this->_db->initializeDatabase();
        }


        return true;
    }

    /**
     * Makes the an SQL expression case insensitive.
     * 
     * @param a string the expression to process
     * @return a SQL expression
     */
    function _ci($a) {
        return $this->_db->ci($a);
    }

    /**
     * Constructs a case insensitive string comparison in SQL.
     *
     * @param a string the left-hand side
     * @param b string the right-hand side
     *
     * @return a case insensitive SQL string comparison
     */
    function _cic($a, $b) {
        return $this->_ci($a).' = '.$this->_ci($b);
    }

    /**
     * Begins a preview.
     */
    function beginPreview() {
        $this->_db->beginTransaction();
    }

    /**
     * Ends a preview.
     */
    function endPreview() {
        $this->_db->rollback();
    }

    /**
     * Removes all triples matching the given triple pattern. One or more parameters
     * can be left out to indicate 'any'.
     */
    function removeTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        // construct triple filter
        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param, '?');
                $values[] = $$param;
            }
        }

        $sql .= "DELETE FROM ".self::$writable." WHERE ". implode(" AND ", $filters);

        // prepare query
        $query = $this->_db->prepare($sql);
        if($query == false) return;

        // execute query
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(sprintf($this->getLang('error_triples_remove'),hsc($error[2])),-1);
        }

        $query->closeCursor();
    }

    /**
     * Fetches all triples matching the given triple pattern. Onr or more of
     * parameters can be left out to indicate 'any'.
     */
    function fetchTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        // construct filter
        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param,'?');
                $values[] = $$param;
            }
        }

        $sql .= "SELECT subject, predicate, object, graph FROM ".self::$readable." WHERE ". implode(" AND ", $filters);

        // prepare queyr
        $query = $this->_db->prepare($sql);
        if($query == false) return;

        // execute query
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(sprintf($this->getLang('error_triples_fetch'),hsc($error[2])),-1);
        }

        // fetch results and return them
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();

        return $result;
    }

    /**
     * Adds a single triple.
     * @param subject string
     * @param predicate string
     * @param object string
     * @param graph string
     * @return true of triple was added succesfully, false if not
     */
    function addTriple($subject, $predicate, $object, $graph) {
        return $this->addTriples(array(array('subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object)), $graph);
    }

    /**
     * Adds multiple triples.
     * @param triples array contains all triples as arrays with subject, predicate and object keys
     * @param graph string graph name
     * @return true if the triples were comitted, false otherwise
     */
    function addTriples($triples, $graph) {
        // prepare insertion query
        $sql = "INSERT INTO ".self::$writable."(subject, predicate, object, graph) VALUES(?, ?, ?, ?)";
        $query = $this->_db->prepare($sql);
        if($query == false) return false;

        // put the batch in a transaction
        $this->_db->beginTransaction();
        foreach($triples as $t) {
            // insert a single triple
            $values = array($t['subject'],$t['predicate'],$t['object'],$graph);
            $res = $query->execute($values);
            
            // handle errors
            if($res === false) {
                $error = $query->errorInfo();
                msg(sprintf($this->getLang('error_triples_add'),hsc($error[2])),-1);
                $this->_db->rollBack();
                return false;
            }
            $query->closeCursor();
        }

        // commit and return
        return $this->_db->commit();
    }

    /**
     * Executes the given abstract query tree as a query on the store.
     *
     * @param query array an abstract query tree
     * @return an iterator over the resulting rows
     */
    function queryRelations($queryTree) {
        // create the SQL generator, and generate the SQL query
        $generator = new strata_sql_generator($this);
        list($sql, $literals, $projected, $grouped) = $generator->translate($queryTree);

        // prepare the query
        $query = $this->_db->prepare($sql);
        if($query === false) {
            return false;
        }

        // execute the query
        $res = $query->execute($literals);
        if($res === false) {
            $error = $query->errorInfo();
            msg(sprintf($this->getLang('error_triples_query'),hsc($error[2])),-1);
            if($this->getConf('debug')) {
                msg(sprintf($this->getLang('debug_sql'),hsc($sql)),-1);
                msg(sprintf($this->getLang('debug_literals'), hsc(print_r($literals,1))),-1);
            }
            return false;
        }

        // wrap the results in an iterator, and return it
        if($queryTree['grouping'] === false) {
            return new strata_relations_iterator($query, $projected);
        } else {
            return new strata_aggregating_iterator($query, $projected, $grouped);
        }
    }

    /**
     * Executes the abstract query tree, and returns all properties of the matching subjects.
     * This method assumes that the root is a 'select' node.
     * 
     * @param query array the abstract query tree
     * @return an iterator over the resources
     */
    function queryResources($query) {
        // We transform the given query into a resource-centric query as follows:
        //   Remember the single projected variable Vx.
        //   Append two variables ?__p and ?__o to the projection
        //   Add an extra triple pattern (Vx, ?__p, ?__o)
        //   Append Vx to the ordering
        // The query is ready for execution. Result set can be transformed into a
        // resource-centric view by fetching all triples related to a single subject
        // (each subject is in a single continuous block, due to the ordering)

        // add extra tuple
        $query['group'] = array(
            'type'=>'and',
            'lhs'=>$query['group'],
            'rhs'=>array(
                'type'=>'triple',
                'subject'=>array('type'=>'variable','text'=>$query['projection'][0]),
                'predicate'=>array('type'=>'variable','text'=>'__predicate'),
                'object'=>array('type'=>'variable','text'=>'__object')
            )
        );

        // fix projection list
        $query['projection'] = array(
            $query['projection'][0],
            '__predicate',
            '__object'
        );

        // append tuple ordering
        $query['ordering'][] = array(
            'variable'=>$query['projection'][0],
            'direction'=>'asc'
        );

        // remove grouping
        $query['grouping'] = false;

        // execute query
        $result = $this->queryRelations($query);

        if($result === false) {
            return false;
        }

        // invoke iterator that's going to aggregate the resulting relations
        return new strata_resource_iterator($result,$query['projection']);
    }
}

/**
 * SQL generator.
 */
class strata_sql_generator {
    /**
     * Stores all literal values keyed to their placeholder.
     */
    private $literals = array();

    /**
     * Stores all projected variables.
     */
    private $projected = array();

    /**
     * Stores all grouped variables.
     */
    private $grouped = array();

    /**
     * Constructor.
     */
    function strata_sql_generator($triples) {
        $this->_triples = $triples;
        $this->_db = $this->_triples->_db;
    }

    /**
     * Passes through localisation calls.
     */
    function getLang($key) {
        return $this->_triples->getLang($key);
    }

    /**
     * Wrap SQL expression in case insensitivisation.
     */
    function _ci($a) {
        return $this->_triples->_ci($a);
    }

    /**
     * Alias generator.
     */
    private $_aliasCounter = 0;
    function _alias($prefix='a') {
        return $prefix.($this->_aliasCounter++);
    }

    /**
     * All used literals.
     */
    private $_literalLookup = array();
    private $_variableLookup = array();

    /**
     * Name generator. Makes the distinction between literals
     * and variables, as they can have the same spelling (and 
     * frequently do in simple queries).
     */
    function _name($term) {
        if($term['type'] == 'variable') {
            // always try the cache
            if(empty($this->_variableLookup[$term['text']])) {
                $this->_variableLookup[$term['text']] = $this->_alias('v');
            }

            return $this->_variableLookup[$term['text']];
        } elseif($term['type'] == 'literal') {
            // always try the cache
            if(empty($this->_literalLookup[$term['text']])) {
                // use aliases to represent literals
                $this->_literalLookup[$term['text']] = $this->_alias('lit');
            }

            // return literal name
            return $this->_literalLookup[$term['text']];
        }
    }

    /**
     * Test whether two things are equal (i.e., equal variables, or equal literals)
     */
    function _patternEquals($pa, $pb) {
        return $pa['type'] == $pb['type'] && $pa['text'] == $pb['text'];
    }

    /**
     * Generates a conditional for the given triple pattern.
     */
    function _genCond($tp) {
        $conditions = array();

        // the subject is a variable
        if($tp['subject']['type'] != 'variable') {
            $id = $this->_alias('qv');
            $conditions[] = $this->_ci('subject').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['subject']['text'];
        }

        // the predicate is a variable
        if($tp['predicate']['type'] != 'variable') {
            $id = $this->_alias('qv');
            $conditions[] = $this->_ci('predicate').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['predicate']['text'];
        }

        // the object is a variable
        if($tp['object']['type'] != 'variable') {
            $id = $this->_alias('qv');
            $conditions[] = $this->_ci('object').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['object']['text'];
        }

        // subject equals predicate
        if($this->_patternEquals($tp['subject'],$tp['predicate'])) {
            $conditions[] = $this->_ci('subject').' = '.$this->_ci('predicate');
        }

        // subject equals object
        if($this->_patternEquals($tp['subject'],$tp['object'])) {
            $conditions[] = $this->_ci('subject').' = '.$this->_ci('object');
        }

        // predicate equals object
        if($this->_patternEquals($tp['predicate'],$tp['object'])) {
            $conditions[] = $this->_ci('predicate').' = '.$this->_ci('object');
        }

        if(count($conditions)!=0) {
            return implode(' AND ',$conditions);
        } else {
            return '1 = 1';
        }
    }

    /**
     * Generates a projection for the given triple pattern.
     */
    function _genPR($tp) {
        $list = array();

        // always project the subject
        $list[] = 'subject AS '.$this->_name($tp['subject']);

        // project the predicate if it's different from the subject
        if(!$this->_patternEquals($tp['subject'], $tp['predicate'])) {
            $list[] = 'predicate AS '.$this->_name($tp['predicate']);
        }

        // project the object if it's different from the subject and different from the predicate
        if(!$this->_patternEquals($tp['subject'], $tp['object']) && !$this->_patternEquals($tp['predicate'],$tp['object'])) {
            $list[] = 'object AS '.$this->_name($tp['object']);
        }

        return implode(', ',$list);
    }

    /**
     * Translates a triple pattern into a graph pattern.
     */
    function _trans_tp($tp) {
        return array(
            'sql'=>'SELECT '.$this->_genPR($tp).' FROM '.helper_plugin_strata_triples::$readable.' WHERE '.$this->_genCond($tp),
            'terms'=>array($this->_name($tp['subject']),$this->_name($tp['predicate']), $this->_name($tp['object']))
        );
    }

    /**
     * Translates a group operation on the two graph patterns.
     */
    function _trans_group($gp1, $gp2, $join) {
        // determine the resulting terms
        $terms = array_unique(array_merge($gp1['terms'], $gp2['terms']));

        // determine the overlapping terms (we need to coalesce these)
        $common = array_intersect($gp1['terms'], $gp2['terms']);

        // determine the non-overlapping terms (we can project them directly)
        $fields = array_diff($terms, $common);

        // handle overlapping terms by coalescing them into a single term
        if(count($common)>0) {
            $intersect = array();
            foreach($common as $c) {
                $intersect[] = '('.$this->_ci('r1.'.$c).' = '.$this->_ci('r2.'.$c).' OR r1.'.$c.' IS NULL OR r2.'.$c.' IS NULL)';
                $fields[]='COALESCE(r1.'.$c.', r2.'.$c.') AS '.$c;
            }
            $intersect = implode(' AND ',$intersect);
        } else {
            $intersect = '1';
        }

        $fields = implode(', ',$fields);

        return array(
            'sql'=>'SELECT DISTINCT '.$fields.' FROM ('.$gp1['sql'].') AS r1 '.$join.' ('.$gp2['sql'].') AS r2 ON '.$intersect,
            'terms'=>$terms
        );
    }

    /**
     * Translate an optional operation.
     */
    function _trans_opt($query) {
        $gp1 = $this->_dispatch($query['lhs']);
        $gp2 = $this->_dispatch($query['rhs']);
        return $this->_trans_group($gp1, $gp2, 'LEFT OUTER JOIN');
    }

    /**
     * Translate an and operation.
     */
    function _trans_and($query) {
        $gp1 = $this->_dispatch($query['lhs']);
        $gp2 = $this->_dispatch($query['rhs']);
        return $this->_trans_group($gp1, $gp2, 'INNER JOIN');
    }

    /**
     * Translate a filter operation. The filters are a conjunction of separate expressions.
     */
    function _trans_filter($query) {
        $gp = $this->_dispatch($query['lhs']);
        $fs = $query['rhs'];

        $filters = array();

        foreach($fs as $f) {
            // determine representation of left-hand side
            if($f['lhs']['type'] == 'variable') {
                $lhs = $this->_name($f['lhs']);
            } else {
                $id = $this->_alias('qv');
                $lhs = ':'.$id;
                $this->literals[$id] = $f['lhs']['text'];
            }

            // determine representation of right-hand side
            if($f['rhs']['type'] == 'variable') {
                $rhs = $this->_name($f['rhs']);
            } else {
                $id = $this->_alias('qv');
                $rhs = ':'.$id;
                $this->literals[$id] = $f['rhs']['text'];
            }

            // the escaping constants (head, tail and modifier)
            $eh= "REPLACE(REPLACE(REPLACE(";
            $et= ",'!','!!'),'_','!_'),'%','!%')";
            $em= " ESCAPE '!'";

            // handle different operators
            switch($f['operator']) {
                case '=':
                case '!=':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$f['operator'].' ' . $this->_ci($rhs). ' )';
                    break;
                case '>':
                case '<':
                case '>=':
                case '<=':
                    $filters[] = '( ' . $this->_triples->_db->castToNumber($lhs) . ' ' . $f['operator'] . ' ' . $this->_triples->_db->castToNumber($rhs) . ' )';
                    break;
                case '~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' '. $this->_ci('(\'%\' || ' .$eh.$rhs.$et. ' || \'%\')') .$em. ')';
                    break;
                case '!~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' NOT '.$this->_db->stringCompare().' '. $this->_ci('(\'%\' || ' . $eh.$rhs.$et. ' || \'%\')') .$em. ')';
                    break;
                case '^~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' ' .$this->_ci('('. $eh.$rhs.$et . ' || \'%\')').$em. ')';
                    break;
                case '!^~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' NOT '.$this->_db->stringCompare().' ' .$this->_ci('('. $eh.$rhs.$et . ' || \'%\')').$em. ')';
                    break;
                case '$~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' '.$this->_ci('(\'%\' || ' . $eh.$rhs.$et. ')') .$em. ')';
                    break;
                case '!$~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' NOT '.$this->_db->stringCompare().' '.$this->_ci('(\'%\' || ' . $eh.$rhs.$et. ')') .$em. ')';
                    break;

                default:
            }
        }

        $filters = implode(' AND ', $filters);

        return array(
            'sql'=>'SELECT * FROM ('.$gp['sql'].') r WHERE '.$filters,
            'terms'=>$gp['terms']
        );
    }

    /**
     * Translate minus operation.
     */
    function _trans_minus($query) {
        $gp1 = $this->_dispatch($query['lhs']);
        $gp2 = $this->_dispatch($query['rhs']);

        // determine overlapping terms (we need to substitute these)
        $common = array_intersect($gp1['terms'], $gp2['terms']);

        // create conditional that 'substitutes' terms by requiring equality
        $terms = array();
        foreach($common as $c) {
            $terms[] = '('.$this->_ci('r1.'.$c).' = '.$this->_ci('r2.'.$c).')';
        }

        if(count($terms)>0) {
            $terms = implode(' AND ',$terms);
        } else {
            $terms = '1=1';
        }

        return array(
            'sql'=>'SELECT DISTINCT * FROM ('.$gp1['sql'].') r1 WHERE NOT EXISTS (SELECT * FROM ('.$gp2['sql'].') r2 WHERE '.$terms.')',
            'terms'=>$gp1['terms']
        );
    }

    /**
     * Translate union operation.
     */
    function _trans_union($query) {
        // dispatch the child graph patterns
        $gp1 = $this->_dispatch($query['lhs']);
        $gp2 = $this->_dispatch($query['rhs']);

        // dispatch them again to get new literal binding aliases
        // (This is required by PDO, as no named variable may be used twice)
        $gp1x = $this->_dispatch($query['lhs']);
        $gp2x = $this->_dispatch($query['rhs']);

        // determine non-overlapping terms
        $ta = array_diff($gp1['terms'], $gp2['terms']);
        $tb = array_diff($gp2['terms'], $gp1['terms']);

        // determine overlapping terms
        $tc = array_intersect($gp1['terms'], $gp2['terms']);

        // determine final terms
        $terms = array_unique(array_merge($gp1['terms'], $gp2['terms']));

        // construct selected term list
        $sa = array_merge($ta, $tb);
        $sb = array_merge($ta, $tb);

        // append common terms with renaming
        foreach($tc as $c) {
            $sa[] = 'r1.'.$c.' AS '.$c;
            $sb[] = 'r3.'.$c.' AS '.$c;
        }

        $sa = implode(', ', $sa);
        $sb = implode(', ', $sb);

        return  array(
            'sql'=>'SELECT DISTINCT '.$sa.' FROM ('.$gp1['sql'].') r1 LEFT OUTER JOIN ('.$gp2['sql'].') r2 ON (1=0) UNION SELECT DISTINCT '.$sb.' FROM ('.$gp2x['sql'].') r3 LEFT OUTER JOIN ('.$gp1x['sql'].') r4 ON (1=0)',
            'terms'=>$terms
        );
    }

    /**
     * Translate projection and ordering.
     */
    function _trans_select($query) {
        $gp = $this->_dispatch($query['group']);
        $vars = $query['projection'];
        $order = $query['ordering'];
        $group = $query['grouping'];
        $consider = $query['considering'];
        $terms = array();
        $fields = array();

        // if we get a don't group, put sentinel value in place
        if($group === false) $group = array();

        // massage ordering to comply with grouping
        // we do this by combining ordering and sorting information as follows:
        // The new ordering is {i, Gc, Oc} where
        //  i = (order intersect group)
        //  Gc = (group diff order)
        //  Oc = (order diff group)
        $order_vars = array();
        $order_lookup = array();
        foreach($order as $o) {
            $order_vars[] = $o['variable'];
            $order_lookup[$o['variable']] = $o;
        }

        // determine the three components
        $order_i = array_intersect($order_vars, $group);
        $group_c = array_diff($group, $order_vars);
        $order_c = array_diff($order_vars, $group);
        $order_n = array_merge($order_i, $group_c, $order_c);

        // construct new ordering array
        $neworder = array();
        foreach($order_n as $ovar) {
            if(!empty($order_lookup[$ovar])) {
                $neworder[] = $order_lookup[$ovar];
            } else {
                $neworder[] = array('variable'=>$ovar, 'direction'=>'asc');
            }
        }

        // project extra fields that are required for the grouping
        foreach($group as $v) {
            // only project them if they're not projected somewhere else
            if(!in_array($v, $vars)) {
                $name = $this->_name(array('type'=>'variable', 'text'=>$v));
                $fields[] = $name;

                // store grouping translation
                $this->grouped[$name] = $v;
            }
        }
        

        // determine exact projection
        foreach($vars as $v) {
            // determine projection translation
            $name = $this->_name(array('type'=>'variable','text'=>$v));

            // fix projected variable into SQL
            $terms[] = $name;
            $fields[] = $name;

            // store projection translation
            $this->projected[$name] = $v;

            // store grouping translation
            if(in_array($v, $group)) {
                $this->grouped[$name] = $v;
            }
        }

        // add fields suggested for consideration
        foreach($consider as $v) {
            $name = $this->_name(array('type'=>'variable', 'text'=>$v));
            $alias = $this->_alias('c');
            $fields[] = "$name AS $alias";
        }

        // assign ordering if required
        $ordering = array();
        foreach($neworder as $o) {
            $name = $this->_name(array('type'=>'variable','text'=>$o['variable']));
            $orderTerms = $this->_db->orderBy($name);
            foreach($orderTerms as $term) {
                $a = $this->_alias('o');
                $fields[] = "$term AS $a";
                $ordering[] = "$a ".($o['direction'] == 'asc'?'ASC':'DESC');
            }
        }

        // construct select list
        $fields = implode(', ',$fields);

        // construct ordering
        if(count($ordering)>0) {
            $ordering = ' ORDER BY '.implode(', ',$ordering);
        } else {
            $ordering = '';
        }

        return array(
            'sql'=>'SELECT DISTINCT '.$fields.' FROM ('.$gp['sql'].') r'.$ordering,
            'terms'=>$terms
        );
    }

    function _dispatch($query) {
        switch($query['type']) {
            case 'select':
                return $this->_trans_select($query);
            case 'union':
                return $this->_trans_union($query);
            case 'minus':
                return $this->_trans_minus($query);
            case 'optional':
                return $this->_trans_opt($query);
            case 'filter':
                return $this->_trans_filter($query);
            case 'triple':
                return $this->_trans_tp($query);
            case 'and':
                return $this->_trans_and($query);
            default:
                msg(sprintf($this->getLang('error_triples_node'),hsc($query['type'])),-1);
                return array('sql'=>'<<INVALID QUERY NODE>>', 'terms'=>array());
        }
    }

    /**
     * Translates an abstract query tree to SQL.
     */
    function translate($query) {
        $q = $this->_dispatch($query);
        return array($q['sql'], $this->literals, $this->projected, $this->grouped);
    }
}

/**
 * This iterator is used to offer an interface over a
 * relations query result.
 */
class strata_relations_iterator implements Iterator {
    function __construct($pdostatement, $projection) {
        // backend iterator
        $this->data = $pdostatement;

        // state information
        $this->closed = false;
        $this->id = 0;

        // projection data
        $this->projection = $projection;

        // initialize the iterator
        $this->next();
    }
    
    function current() {
        return $this->row;
    }

    function key() {
        return $this->id;
    }

    function next() {
        // fetch...
        $this->row = $this->data->fetch(PDO::FETCH_ASSOC);

        if($this->row) {
            $row = array();

            // ...project...
            foreach($this->projection as $alias=>$field) {
                $row[$field] = $this->row[$alias] != null ? array($this->row[$alias]) : array();
            }
            $this->row = $row;

            // ...and increment the id.
            $this->id++;
        }

        // Close the backend if we're out of rows.
        // (This should not be necessary if everyone closes
        // their iterator after use -- but experience dictates that
        // this is a good safety net)
        if(!$this->valid()) {
            $this->closeCursor();
        }
    }

    function rewind() {
        // noop
    }

    function valid() {
        return $this->row != null;
    }

    /**
     * Closes this result set.
     */
    function closeCursor() {
        if(!$this->closed) {
            $this->data->closeCursor();
            $this->closed = true;
        }
    }
}

/**
 * This iterator is used to offer an interface over a
 * resources query result.
 */
class strata_resource_iterator implements Iterator {
    function __construct($relations, $projection) {
        // backend iterator (ordered by tuple)
        $this->data = $relations;

        // state information
        $this->closed = false;
        $this->valid = true;
        $this->item = null;
        $this->subject = null;

        // projection data
        list($this->__subject, $this->__predicate, $this->__object) = $projection;

        // initialize the iterator
        $this->next();
    }
    
    function current() {
        return $this->item;
    }

    function key() {
        return $this->subject;
    }

    function next() {
        if(!$this->data->valid()) {
            $this->valid = false;
            return;
        }

        // the current relation
        $peekRow = $this->data->current();

        // construct a new subject
        $this->item = array();
        $this->subject = $peekRow[$this->__subject][0];

        // continue aggregating data as long as the subject doesn't change and
        // there is data available
        while($this->data->valid() && $peekRow[$this->__subject][0] == $this->subject) {
            $p = $peekRow[$this->__predicate][0];
            $o = $peekRow[$this->__object][0];
            if(!isset($this->item[$p])) $this->item[$p] = array();
            $this->item[$p][] = $o;
            
            $this->data->next();
            $peekRow = $this->data->current();
        }

        return $this->item;
    }

    function rewind() {
        // noop
    }

    function valid() {
        return $this->valid;
    }

    /**
     * Closes this result set.
     */
    function closeCursor() {
        if(!$this->closed) {
            $this->data->closeCursor();
            $this->closed = true;
        }
    }
}

/**
 * This iterator aggregates the results of the underlying
 * iterator for the given grouping key.
 */
class strata_aggregating_iterator implements Iterator {
    function __construct($pdostatement, $projection, $grouped) {
        // backend iterator (ordered by tuple)
        $this->data = $pdostatement;

        // state information
        $this->closed = false;
        $this->valid = true;
        $this->item = null;
        $this->subject = 0;

        $this->groupKey = $grouped;
        $this->projection = $projection;

        // initialize the iterator
        $this->peekRow = $this->data->fetch(PDO::FETCH_ASSOC);
        $this->next();
    }
    
    function current() {
        return $this->item;
    }

    function key() {
        return $this->subject;
    }

    private function extractKey($row) {
        $result = array();
        foreach($this->groupKey as $alias=>$field) {
            $result[$field] = $row[$alias];
        }
        return $result;
    }

    private function keyCheck($a, $b) {
        return $a === $b;
    }

    function next() {
        if($this->peekRow == null) {
            $this->valid = false;
            return;
        }

        // the current relation
        $key = $this->extractKey($this->peekRow);

        // construct a new subject
        $this->subject++;
        $this->item = array();

        // continue aggregating data as long as the subject doesn't change and
        // there is data available
        while($this->peekRow != null && $this->keyCheck($key,$this->extractKey($this->peekRow))) {
            foreach($this->projection as $alias=>$field) {
                if(in_array($field, $this->groupKey)) {
                    // it is a key field, grab it directly from the key
                    $this->item[$field] = $key[$field]!=null ? array($key[$field]) : array();
                } else {
                    // lazy create the field's bucket
                    if(empty($this->item[$field])) {
                        $this->item[$field] = array();
                    }

                    // push the item into the bucket if we have an item
                    if($this->peekRow[$alias] != null) {
                        $this->item[$field][] = $this->peekRow[$alias];
                    }
                }
            }
            
            $this->peekRow = $this->data->fetch(PDO::FETCH_ASSOC);
        }

        if($this->peekRow == null) {
            $this->closeCursor();
        }

        return $this->item;
    }

    function rewind() {
        // noop
    }

    function valid() {
        return $this->valid;
    }

    /**
     * Closes this result set.
     */
    function closeCursor() {
        if(!$this->closed) {
            $this->data->closeCursor();
            $this->closed = true;
        }
    }
}
