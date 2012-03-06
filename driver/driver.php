<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The base class for database drivers.
 */
abstract class plugin_strata_driver {
    /**
     * Produces the syntax to cast something to a number.
     *
     * @param val string the thing to cast
     */
    public function castToNumber($val) {
        return "CAST($val AS NUMERIC)";
    }

    /**
     * Determines whether the database is initialised.
     *
     * @param db object the PDO connection to check
     * @return boolean true if the database is initialised
     */
    public abstract function isInitialized($db);

    /**
     * Initialises the database by setting up all tables.
     *
     * This implementation looks for a file called 'setup-@driver@.sql' and executes all SQL statements in that file.
     * Here, '@driver@' represents the database driver, such as 'sqlite'.
     *
     * @param db object the PDO connection to use
     * @param dsn string the dsn used to connect to the db (which starts with '@driver@:')
     * @param debug boolean whether debug output should be given (defaults to false)
     * @return boolean true if the database was initialised successfully
     */
    public function initializeDatabase($db, $dsn, $debug=false) {
        list($driver, $connection) = explode(':', $dsn, 2);
        if ($debug) msg('Strata storage: Setting up ' . $driver . ' database.');

        $sqlfile = DOKU_PLUGIN . "stratastorage/sql/setup-$driver.sql";

        $sql = io_readFile($sqlfile, false);
        $sql = explode(';', $sql);

        $db->beginTransaction();
        foreach($sql as $s) {
            $s = preg_replace('/^\s*--.*$/','',$s);
            $s = trim($s);
            if($s == '') continue;

            if ($debug) msg(hsc('Strata storage: Executing \'' . $s . '\'.'));
            if(!$this->query($db, $s, 'Failed to set up database')) {
                $db->rollback();
                return false;
            }
        }
        $db->commit();

        msg('Strata storage: Database set up successful!', 1);

        return true;
    }

    /**
     * Removes a database that was initialized before.
     *
     * @param db object the PDO connection to use
     * @return whether the database was removed successfully
     */
    public function removeDatabase($db) {
        return $db->query('DROP TABLE data', 'Failed to remove database');
    }

    /**
     * Prepares a query and reports any problems to Dokuwiki.
     *
     * @param db object the PDO connection to use
     * @param query string the query to prepare
     * @return the prepared statement
     */
    public function prepare($db, $query) {
        $result = $db->prepare($query);
        if ($result === false) {
            $error = $db->errorInfo();
            msg(hsc('Strata storage: Failed to prepare query \''.$query.'\': '.$error[2]),-1);
            return false;
        }

        return $result;
    }

     /**
     * Executes a query and reports any problems to Dokuwiki.
     *
     * @param db object the PDO connection to use
     * @param query string the query to execute
     * @param message string message to report when executing the query fails
     * @return whether querying succeeded
     */
    public function query($db, $query, $message="Query failed") {
        $res = $db->query($query);
        if ($res === false) {
            $error = $db->errorInfo();
            msg(hsc('Strata storage: '.$message.' (with \''.$query.'\'): '.$error[2]),-1);
            return false;
        }
        return true;
    }
}

