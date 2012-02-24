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
    
    function initialize($dsn=null) {
        if($dsn == null) {
            $dsn = $this->getConf('default_dsn');

            if($dsn == '') {
                global $conf;
                $file = "{$conf['metadir']}/strata.sqlite";
                $init = (!@file_exists($file) || ((int) @filesize($file) < 3));
                $dsn = "sqlite:$file";
            }
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
            if($this->getConf('debug')) msg(hsc("Strata storage: failed to open DSN '$dsn': ".$e->getMessage()),-1);
            return false;
        }

        if($init) {
            $this->_setupDatabase();
        }

        return true;
    }

    function _setupDatabase() {
        list($driver,$connection) = explode(':',$this->_dsn,2);
        if($this->getConf('debug')) msg('Strata storage: Setting up '.$driver.' database.');

        $sqlfile = DOKU_PLUGIN."stratastorage/sql/setup-$driver.sql";

        $sql = io_readFile($sqlfile, false);

        $sql = explode(';', $sql);

        array_unshift($sql, $this->_driver->startTransaction());
        array_push($sql, $this->_driver->commit());

        foreach($sql as $s) {
            $s = preg_replace('/^\s*--.*$/','',$s);
            $s = trim($s);
            if($s == '') continue;

            if($this->getConf('debug')) msg(hsc('Strata storage: Executing \''.$s.'\'.'));
            $res = $this->_db->query($s);
            if($res === false) {
                $error = $this->_db->errorInfo();
                msg(hsc('Strata storage: Failed to set up database: '.$error[2]),-1);
                $this->_db->query($this->_driver->rollback());
                return false;
            }
        }

        msg('Strata storage: Database set up succesful!',1);

        return true;
    }

    function _prepare($query) {
        $result = $this->_db->prepare($query);
        if($result === false) {
            $error = $this->_db->errorInfo();
            msg(hsc('Strata storage: Failed to prepare query \''.$query.'\': '.$error[2]),-1);
            return false;
        }

        return $result;
    }

    function removeTriples($subject=null, $predicate=null, $object=null, $graph=null) {
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
        $query->execute($values);
    }

    function addTriple($subject, $predicate, $object, $graph) {
        return $this->addTriples(array(array('subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object)), $graph);
    }

    function addTriples($triples, $graph) {
        $sql = "INSERT INTO data(subject, predicate, object, graph) VALUES(?, ?, ?, ?)";
        $query = $this->_prepare($sql);
        if($query == false) return;
        foreach($triples as $triple) {
            $triple[] = $graph;
            $query->execute($triple);
        }
    }
}
