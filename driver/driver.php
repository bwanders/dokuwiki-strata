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
     * Whether the driver should generate debug output.
     */
    var $_debug;

    /**
     * The dsn.
     */
    var $_dsn;

    /**
     * The PDO database object.
     */
    var $_db;

    /**
     * Create a new database driver.
     *
     * @param debug boolean whether the created driver should generate debug output.
     */
    function __construct($debug=false) {
        $this->_debug = $debug;
    }

    /**
     * Produces the syntax to cast something to a number.
     *
     * @param val string the thing to cast
     */
    public function castToNumber($val) {
        return "CAST($val AS NUMERIC)";
    }

    /**
     * Casts the given value to a case insensitive variant.
     *
     * This cast can, for example, be using a case insensitive collation or using the function 'lower' (default).
     * @param val string the thing make case insensitive
     */
    public function ci($val='?') {
        return "lower($val)";
    }

    /**
     * Returns the syntax for case-insensitive string comparison.
     *
     * Preferably, this syntax should allow % as a wildcard (e.g. as done by LIKE).
     */
    public function stringCompare() {
        return 'LIKE';
    }

    /**
     * Returns the terms on which we should order.
     *
     * Ideally, the ordering should be natural, that is '2 apples' is sorted before '10 pears'.
     * However, depending on the supported database, ordering can vary between string and natural ordering, including any compromises.
     * @param val string the thing to sort on
     * @return an array of terms to sort on
     */
    public function orderBy($val) {
        return array(
            $this->castToNumber($val),
            $val
        );
    }

    /**
     * Open the database connection.
     *
     * @param dsn string the dsn to use for connecting
     * @return boolean true when connecting was successful
     */
    public function connect($dsn) {
        $this->_dsn = $dsn;
        try {
            $this->_db = $this->initializePDO($dsn);
        } catch(PDOException $e) {
            if ($this->_debug) {
                msg(hsc("Strata storage: Failed to open data source '$dsn': " . $e->getMessage()), -1);
            } else {
                msg('Strata storage: Failed to open data source.', -1);
            }
            return false;
        }
        $this->initializeConnection();
        return true;
    }

    /**
     * Initialize the PDO object.
     *
     * @param dsn string the dsn to use for construction
     * @return the PDO object
     */
    protected function initializePDO($dsn) {
        $credentials = array('','');
        if(@file_exists(DOKU_PLUGIN.'stratastorage/credentials.local.php')) {
            $credentials = include(DOKU_PLUGIN.'stratastorage/credentials.local.php');
        }
        return new PDO($dsn, $credentials[0], $credentials[1]);
    }


    /**
     * Initialises a connection directly after the connection was made (e.g. by setting the character set of the connection).
     */
    protected function initializeConnection() {}

    /**
     * Determines whether the database is initialised.
     *
     * @return boolean true if the database is initialised
     */
    public abstract function isInitialized();

    /**
     * Initialises the database by setting up all tables.
     *
     * This implementation looks for a file called 'setup-@driver@.sql' and executes all SQL statements in that file.
     * Here, '@driver@' represents the database driver, such as 'sqlite'.
     *
     * @return boolean true if the database was initialised successfully
     */
    public function initializeDatabase() {
        if($this->_db == false) return false;

        // determine driver
        list($driver, $connection) = explode(':', $this->_dsn, 2);
        if ($this->_debug) msg('Strata storage: Setting up ' . $driver . ' database.');

        // load SQL script
        $sqlfile = DOKU_PLUGIN . "stratastorage/sql/setup-$driver.sql";

        $sql = io_readFile($sqlfile, false);
        $lines = explode("\n",$sql);

        // remove empty lines and comment lines
        // (this makes sure that a semicolon in the comment doesn't break the script)
        $sql = '';
        foreach($lines as $line) {
            $line = preg_replace('/--.*$/','',$line);
            if(trim($line," \t\n\r") == '') continue;
            $sql .= $line;
        }

        // split the script into distinct statements
        $sql = explode(';', $sql);

        // execute the database initialisation script in a transaction
        // (doesn't work in all databases, but provides some failsafe where it works)
        $this->beginTransaction();
        foreach($sql as $s) {
            // skip empty lines (usually the last line is empty, due to the final semicolon)
            if(trim($s) == '') continue;

            if ($this->_debug) msg(hsc('Strata storage: Executing \'' . $s . '\'.'));
            if(!$this->query($s, 'Failed to set up database')) {
                $this->rollBack();
                return false;
            }
        }
        $this->commit();

        msg('Strata storage: Database set up successful!', 1);

        return true;
    }

    /**
     * Removes a database that was initialized before.
     *
     * @return whether the database was removed successfully
     */
    public function removeDatabase() {
        return $this->query('DROP TABLE data', 'Failed to remove database');
    }

    /**
     * Prepares a query and reports any problems to Dokuwiki.
     *
     * @param query string the query to prepare
     * @return the prepared statement
     */
    public function prepare($query) {
        if($this->_db == false) return false;

        $result = $this->_db->prepare($query);
        if ($result === false) {
            $error = $this->_db->errorInfo();
            msg(hsc('Strata storage: Failed to prepare query \''.$query.'\': '.$error[2]),-1);
            return false;
        }

        return $result;
    }

     /**
      * Executes a query and reports any problems to Dokuwiki.
      *
      * @param query string the query to execute
      * @param message string message to report when executing the query fails
      * @return whether querying succeeded
      */
    public function query($query, $message="Query failed") {
        if($this->_db == false) return false;

        $res = $this->_db->query($query);
        if ($res === false) {
            $error = $this->_db->errorInfo();
            msg(hsc('Strata storage: '.$message.' (with \''.$query.'\'): '.$error[2]),-1);
            return false;
        }
        return true;
    }

    /**
     * Begins a transaction.
     */
    public function beginTransaction() {
        if($this->_db == false) return false;

        return $this->_db->beginTransaction();
    }

    /**
     * Commits the current transaction.
     */
    public function commit() {
        if($this->_db == false) return false;

        return $this->_db->commit();
    }

    /**
     * Rolls back the current transaction.
     */
    public function rollBack() {
        if($this->_db == false) return false;

        return $this->_db->rollBack();
    }
}

