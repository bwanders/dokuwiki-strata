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
            'return' => 'boolean'
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
        $this->_driver = new $driverClass();

        try {
            $this->_db = new PDO($dsn);
        } catch(PDOException $e) {
            if($this->getConf('debug')) {
                msg(hsc("Strata storage: Failed to open data source '$dsn': ".$e->getMessage()),-1);
            } else {
                msg('Strata storage: Failed to open data source.',-1);
            }
            return false;
        }

        if(!$this->_driver->isInitialized($this->_db)) {
            $this->_driver->initializeDatabase($this->_db, $dsn, $this->getConf('debug'));
        }


        return true;
    }

   function _prepare($query) {
        return $this->_driver->prepare($this->_db, $query);
    }

    function removeTriples($subject=null, $predicate=null, $object=null, $graph=null) {
        $graph = $graph?:$this->getConf('default_graph');

        $filters = array('1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]="$param LIKE ?";
                $values[] = $$param;
            }
        }

        $sql .= "DELETE FROM data WHERE ". implode(" AND ", $filters);

        $query = $this->_prepare($sql);
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

        $filters = array('1');
        foreach(array('subject','predicate','object','graph') as $param) {
            if($$param != null) {
                $filters[]="$param LIKE ?";
                $values[] = $$param;
            }
        }

        $sql .= "SELECT * FROM data WHERE ". implode(" AND ", $filters);

        $query = $this->_prepare($sql);
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
        $query = $this->_prepare($sql);
        if($query == false) return false;

        $this->_db->beginTransaction();
        foreach($triples as $t) {
            $values = array($t['subject'],$t['predicate'],$t['object'],$graph);
            $res = $query->execute($values);
            if($res === false) {
                $error = $query->errorInfo();
                msg(hsc('Strata storage: Failed to add triples: '.$error[2]),-1);
                $this->_db->rollback();
                return false;
            }
            $query->closeCursor();
        }
        return $this->_db->commit();
    }

    function queryRelations($query) {
        $generator = new stratastorage_sql_generator();
        
        list($sql, $literals) = $generator->translate($query);
        
        return array('sql'=>$sql,'literals'=>$literals);
    }

    function queryResources($query) {
        return array();
    }
}

class stratastorage_sql_generator {
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
                // use double-quotes literal names as test
                // shouldn't do this in production
                $this->_literalLookup[$term['text']] = '"'.str_replace('"','""',$term['text']).'"';
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
            $conditions[] = 'subject = :'.$id;
            $this->literals[$id] = $tp['subject']['text'];
        }
        if($tp['predicate']['type'] != 'variable') {
            $id = $this->_alias();
            $conditions[] = 'predicate = :'.$id;
            $this->literals[$id] = $tp['predicate']['text'];
        }
        if($tp['object']['type'] != 'variable') {
            $id = $this->_alias();
            $conditions[] = 'object = :'.$id;
            $this->literals[$id] = $tp['object']['text'];
        }
        if($this->_patternEquals($tp['subject'],$tp['predicate'])) {
            $conditions[] = 'subject = predicate';
        }
        if($this->_patternEquals($tp['subject'],$tp['object'])) {
            $conditions[] = 'subject = object';
        }
        if($this->_patternEquals($tp['predicate'],$tp['object'])) {
            $conditions[] = 'predicate = object';
        }

        if(count($conditions)!=0) {
            return implode(' AND ',$conditions);
        } else {
            return 'TRUE';
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

    function translate($query) {
        $sql = '';
        return array($sql, $this->literals);
    }
}
