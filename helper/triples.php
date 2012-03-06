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
}
