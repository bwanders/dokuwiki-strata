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

        return $result;
    }

    function _expandTokens($str) {
        global $conf;
        $tokens    = array('@METADIR@');
        $replacers = array($conf['metadir']);
        return str_replace($tokens,$replacers,$str);
    }
    
    function initialize($dsn=null) {
        if($dsn == null) {
            $dsn = $this->getConf('default_dsn');
            $dsn = $this->_expandTokens($dsn);
        }

        $this->_dsn = $dsn;

        list($driver,$connection) = explode(':',$dsn,2);
        $driverFile = DOKU_PLUGIN."stratastorage/driver/$driver.php";
        if(!@file_exists($driverFile)) {
            msg('Strata storage: no complementary driver for PDO driver '.$driver.'.',-1);
            return false;
        }
        require_once($driverFile);
        $driverClass = "plugin_strata_driver_$driver";
        $this->_db = new $driverClass($this->getConf('debug'));

        if(!$this->_db->connect($dsn)) {
            return false;
        }

        if(!$this->_db->isInitialized()) {
            $this->_db->initializeDatabase();
        }


        return true;
    }

    function _ci($a) {
        return $this->_db->ci($a);
    }

    function _cic($a, $b) {
        return $this->_ci($a).' '.$this->_db->stringCompare().' '.$this->_ci($b);
    }

    function removeTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        $graph = $graph?:$this->getConf('default_graph');

        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param, '?');
                $values[] = $$param;
            }
        }

        $sql .= "DELETE FROM data WHERE ". implode(" AND ", $filters);

        $query = $this->_db->prepare($sql);
        if($query == false) return;
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to remove triples: '.$error[2]),-1);
        }
        $query->closeCursor();
    }

    function fetchTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        $graph = $graph?:$this->getConf('default_graph');

        $filters = array('1 = 1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]=$this->_cic($param,'?');
                $values[] = $$param;
            }
        }

        $sql .= "SELECT subject, predicate, object, graph FROM data WHERE ". implode(" AND ", $filters);

        $query = $this->_db->prepare($sql);
        if($query == false) return;
        $res = $query->execute($values);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to fetch triples: '.$error[2]),-1);
        }

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();
        return $result;
    }

    function addTriple($subject, $predicate, $object, $graph=null) {
        return $this->addTriples(array(array('subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object)), $graph);
    }

    function addTriples($triples, $graph=null) {
        $graph = $graph?:$this->getConf('default_graph');

        $sql = "INSERT INTO data(subject, predicate, object, graph) VALUES(?, ?, ?, ?)";
        $query = $this->_db->prepare($sql);
        if($query == false) return false;

        $this->_db->beginTransaction();
        foreach($triples as $t) {
            $values = array($t['subject'],$t['predicate'],$t['object'],$graph);
            $res = $query->execute($values);
            if($res === false) {
                $error = $query->errorInfo();
                msg(hsc('Strata storage: Failed to add triples: '.$error[2]),-1);
                $this->_db->rollBack();
                return false;
            }
            $query->closeCursor();
        }
        return $this->_db->commit();
    }

    function queryRelations($query) {
        $generator = new stratastorage_sql_generator($this);
        
        list($sql, $literals) = $generator->translate($query);

        $query = $this->_db->prepare($sql);
        if($query === false) {
            return false;
        }

        $res = $query->execute($literals);
        if($res === false) {
            $error = $query->errorInfo();
            msg(hsc('Strata storage: Failed to execute query: '.$error[2]),-1);
            return false;
        }

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();
        
        return $result;
    }

    function queryResources($query) {
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
