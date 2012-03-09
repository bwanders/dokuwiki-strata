<?php
/**
 * DokuWiki Plugin stratastorage (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'stratastorage/driver/driver.php');

/**
 * The triples helper is responsible for querying.
 */
class helper_plugin_stratastorage_triples extends DokuWiki_Plugin {
    function getMethods() {
        $result = array();
        $result[] = array(
            'name'=> 'initialize',
            'desc'=> 'Sets up a connection to the triple storage.',
            'params'=> array(
                'dsn (optional)'=>'string'
            ),
            'return' => array('success'=>'boolean')
        );
        $result[] = array(
            'name'=>'removeTriples',
            'desc'=>'Removes triples according to a triple pattern.',
            'params'=> array(
                'subject (optional)'=>'string',
                'predicate (optional)'=>'string',
                'object (optional)'=>'string',
                'graph (optional)'=>'string'
            ),
            'return' => array()
        );
        $result[] = array(
            'name'=>'fetchTriples',
            'desc'=>'Fetches all triples matching the given triple pattern.',
            'params'=> array(
                'subject (optional)'=>'string',
                'predicate (optional)'=>'string',
                'object (optional)'=>'string',
                'graph (optional)'=>'string'
            ),
            'return' => array('resultset'=>'array')
        );
        $result[] = array(
            'name'=>'addTriple',
            'desc'=>'Adds a single triple to the store.',
            'params'=> array(
                'subject'=>'string',
                'predicate'=>'string',
                'object'=>'string',
                'graph (optional)'=>'string'
            ),
            'return' => array('success'=>'boolean')
        );
        $result[] = array(
            'name'=>'addTriples',
            'desc'=>'Adds a batch of triples to the store.',
            'params'=> array(
                'triples'=>'array with triples',
                'graph (optional)'=>'string'
            ),
            'return' => array('success'=>'boolean')
        );
        $result[] = array(
            'name'=>'queryRelations',
            'desc'=>'Executes a query, given as an abstract query tree, and returns the resulting rows.',
            'params'=> array(
                'querytree'=>'array'
            ),
            'return' => array('resultset'=>'array')
        );
        $result[] = array(
            'name'=>'queryResources',
            'desc'=>'Executes a query, given as an abstract query tree, and return the resulting resources.',
            'params'=> array(
                'querytree'=>'array'
            ),
            'return' => array('resources'=>'array')
        );

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
    function initialize($dsn=null) {
        // load default DSN
        if($dsn == null) {
            $dsn = $this->getConf('default_dsn');
            $dsn = $this->_expandTokens($dsn);
        }

        $this->_dsn = $dsn;

        // construct driver
        list($driver,$connection) = explode(':',$dsn,2);
        $driverFile = DOKU_PLUGIN."stratastorage/driver/$driver.php";
        if(!@file_exists($driverFile)) {
            msg('Strata storage: no complementary driver for PDO driver '.$driver.'.',-1);
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
        return $this->_ci($a).' '.$this->_db->stringCompare().' '.$this->_ci($b);
    }

    /**
     * Removes all triples matching the given triple pattern. The parameters
     * subject, predicate and object can be left out to indicate 'any'. If graph
     * is left out, this indicates the use of the default graph.
     */
    function removeTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        // don't nuke all graphs
        $graph = $graph?:$this->getConf('default_graph');

        // construct triple filter
        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param, '?');
                $values[] = $$param;
            }
        }

        $sql .= "DELETE FROM data WHERE ". implode(" AND ", $filters);

        // prepare query
        $query = $this->_db->prepare($sql);
        if($query == false) return;

        // execute query
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to remove triples: '.$error[2]),-1);
        }

        $query->closeCursor();
    }

    /**
     * Fetches all triples matching the given triple pattern. The parameters
     * subject, predicate and object can be left out to indicate 'any'. If graph
     * is left out, this indicates the use of the default graph.
     */
    function fetchTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        // use default graph
        $graph = $graph?:$this->getConf('default_graph');

        // construct filter
        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param,'?');
                $values[] = $$param;
            }
        }

        $sql .= "SELECT subject, predicate, object, graph FROM data WHERE ". implode(" AND ", $filters);

        // prepare queyr
        $query = $this->_db->prepare($sql);
        if($query == false) return;

        // execute query
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to fetch triples: '.$error[2]),-1);
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
     * @param graph string optional graph name, will use default graph of none given.
     * @return true of triple was added succesfully, false if not
     */
    function addTriple($subject, $predicate, $object, $graph=null) {
        return $this->addTriples(array(array('subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object)), $graph);
    }

    /**
     * Adds multiple triples.
     * @param triples array contains all triples as arrays with subject, predicate and object keys
     * @param graph string optional graph name, uses default graph if omitted
     * @return true if the triples were comitted, false otherwise
     */
    function addTriples($triples, $graph=null) {
        // handle null graph
        $graph = $graph?:$this->getConf('default_graph');

        // prepare insertion query
        $sql = "INSERT INTO data(subject, predicate, object, graph) VALUES(?, ?, ?, ?)";
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
                msg(hsc('Strata storage: Failed to add triples: '.$error[2]),-1);
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
     * @param query array an abstract query tree
     * @return an array with the resulting rows
     */
    function queryRelations($query) {
        // create the SQL generator, and generate the SQL query
        $generator = new stratastorage_sql_generator($this);
        list($sql, $literals) = $generator->translate($query);

        // prepare the query
        $query = $this->_db->prepare($sql);
        if($query === false) {
            return false;
        }

        // execute the query
        $res = $query->execute($literals);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to execute query: '.$error[2]),-1);
            return false;
        }

        // fetch all results and return them
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();
        
        return $result;
    }

    /**
     * Executes the abstract query tree, and returns all properties of the matching subjects.
     * @param query array the abstract query tree
     * @return an array of resources
     */
    function queryResources($query) {
        //TODO: Implementation
        return array();
    }
}

class stratastorage_sql_generator {
    function stratastorage_sql_generator($triples) {
        $this->_triples = $triples;
        $this->_db = $this->_triples->_db;
    }

    function _ci($a) {
        return $this->_triples->_ci($a);
    }

    private $_aliasCounter = 0;
    function _alias() {
        return 'a'.($this->_aliasCounter++);
    }

    private $_literalLookup = array();
    function _name($term) {
        if($term['type'] == 'variable') {
            return 'v_'.$term['text'];
        } elseif($term['type'] == 'literal') {
            if(empty($this->_literalLookup[$term['text']])) {
                if($this->_triples->getConf('debug')) {
                    // use double-quotes literal names as test
                    $this->_literalLookup[$term['text']] = '"'.str_replace('"','""',$term['text']).'"';
                } else {
                    $this->_literalLookup[$term['text']] = $this->_alias();
                }
            }
            return $this->_literalLookup[$term['text']];
        }
    }

    function _patternEquals($pa, $pb) {
        return $pa['type'] == $pb['type'] && $pa['text'] == $pb['text'];
    }

    function _genCond($tp) {
        $conditions = array();
        if($tp['subject']['type'] != 'variable') {
            $id = $this->_alias();
            $conditions[] = $this->_ci('subject').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['subject']['text'];
        }
        if($tp['predicate']['type'] != 'variable') {
            $id = $this->_alias();
            $conditions[] = $this->_ci('predicate').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['predicate']['text'];
        }
        if($tp['object']['type'] != 'variable') {
            $id = $this->_alias();
            $conditions[] = $this->_ci('object').' = '.$this->_ci(':'.$id);
            $this->literals[$id] = $tp['object']['text'];
        }
        if($this->_patternEquals($tp['subject'],$tp['predicate'])) {
            $conditions[] = $this->_ci('subject').' = '.$this->_ci('predicate');
        }
        if($this->_patternEquals($tp['subject'],$tp['object'])) {
            $conditions[] = $this->_ci('subject').' = '.$this->_ci('object');
        }
        if($this->_patternEquals($tp['predicate'],$tp['object'])) {
            $conditions[] = $this->_ci('predicate').' = '.$this->_ci('object');
        }

        if(count($conditions)!=0) {
            return implode(' AND ',$conditions);
        } else {
            return '1';
        }
    }

    function _genPR($tp) {
        $list = array();
        $list[] = 'subject AS '.$this->_name($tp['subject']);
        if(!$this->_patternEquals($tp['subject'], $tp['predicate'])) {
            $list[] = 'predicate AS '.$this->_name($tp['predicate']);
        }
        if(!$this->_patternEquals($tp['subject'], $tp['object']) && !$this->_patternEquals($tp['predicate'],$tp['object'])) {
            $list[] = 'object AS '.$this->_name($tp['object']);
        }
        return implode(', ',$list);
    }

    private $literals = array();

    function _trans_tp($tp) {
        return array(
            'sql'=>'SELECT '.$this->_genPR($tp).' FROM data WHERE '.$this->_genCond($tp),
            'terms'=>array($this->_name($tp['subject']),$this->_name($tp['predicate']), $this->_name($tp['object']))
        );
    }

    function _trans_group($gp1, $gp2, $join) {
        $terms = array_unique(array_merge($gp1['terms'], $gp2['terms']));
        $common = array_intersect($gp1['terms'], $gp2['terms']);
        $fields = array_diff($terms, $common);

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


    function _trans_opt($gp1, $gp2) {
        return $this->_trans_group($gp1, $gp2, 'LEFT OUTER JOIN');
    }

    function _trans_and($gp1, $gp2) {
        return $this->_trans_group($gp1, $gp2, 'INNER JOIN');
    }

    function _trans_filter($gp, $fs) {
        $filters = array();
        foreach($fs as $f) {
            if($f['lhs']['type'] == 'variable') {
                $lhs = $this->_name($f['lhs']);
            } else {
                $id = $this->_alias();
                $lhs = ':'.$id;
                $this->literals[$id] = $f['lhs']['text'];
            }

            if($f['rhs']['type'] == 'variable') {
                $rhs = $this->_name($f['rhs']);
            } else {
                $id = $this->_alias();
                $rhs = ':'.$id;
                $this->literals[$id] = $f['rhs']['text'];
            }

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
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' '. $this->_ci('(\'%\' || ' . $rhs . ' || \'%\')') . ')';
                    break;
                case '!~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' NOT '.$this->_db->stringCompare().' '. $this->_ci('(\'%\' || ' . $rhs. ' || \'%\')') . ')';
                    break;
                case '^~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' ' .$this->_ci('('. $rhs . ' || \'%\')'). ')';
                    break;
                case '$~':
                    $filters[] = '( ' . $this->_ci($lhs) . ' '.$this->_db->stringCompare().' '.$this->_ci('(\'%\' || ' . $rhs. ')') . ')';
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

    function _trans_minus($gp1, $gp2) {
        $common = array_intersect($gp1['terms'], $gp2['terms']);
        $terms = array();
        foreach($common as $c) {
            $terms[] = '('.$this->_ci('r1.'.$c).' = '.$this->_ci('r2.'.$c).')';
        }

        if(count($terms)>0) {
            $terms = implode(' AND ',$terms);
        } else {
            $terms = '1';
        }

        return array(
            'sql'=>'SELECT DISTINCT * FROM ('.$gp1['sql'].') r1 WHERE NOT EXISTS (SELECT * FROM ('.$gp2['sql'].') r2 WHERE '.$terms.')',
            'terms'=>$gp1['terms']
        );
    }

    function _trans_select($gp, $vars, $order) {
        $terms = array();
        $fields = array();
        foreach($vars as $v) {
            $name = $this->_name(array('type'=>'variable','text'=>$v));
            $terms[] = $name;
            $fields[] = $name. ' AS "' . $v . '"';
        }
        $fields = implode(', ',$fields);


        $ordering = array();
        foreach($order as $o) {
            $ordering[] = $this->_ci($this->_name(array('type'=>'variable','text'=>$o['name']))).' '. strtoupper($o['order']);
        }
        if(count($ordering)>0) {
            $ordering = ' ORDER BY '.implode(', ',$ordering);
        } else {
            $ordering = '';
        }

        return array(
            'sql'=>'SELECT '.$fields.' FROM ('.$gp['sql'].') r'.$ordering,
            'terms'=>$terms
        );
    }

    function _convertQueryGroup($group) {
        $filters = array();
        $patterns = array();
        foreach($group as $i) {
            switch($i['type']) {
                case 'triple': $patterns[]=$i; break;
                case 'filter': $filters[] =$i; break;
                default: break;
            }
        }

        $gp = $this->_trans_tp($patterns[0]);
        for($i=1; $i<count($patterns); $i++) {
            $gp = $this->_trans_and($gp, $this->_trans_tp($patterns[$i]));
        }

        if(count($filters)) {
            $gp = $this->_trans_filter($gp, $filters);
        }

        return $gp;
    }


    function translate($query) {
        $gp = $this->_convertQueryGroup($query['where']);

        foreach($query['minus'] as $minus) {
            $gp = $this->_trans_minus($gp, $this->_convertQueryGroup($minus));
        }

        foreach($query['optionals'] as $opt) {
            $gp = $this->_trans_opt($gp, $this->_convertQueryGroup($opt));
        }

        $q = $this->_trans_select($gp, $query['select'], $query['sort']);

        return array($q['sql'], $this->literals);
    }
}
