<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

// Define the location of the local credentials file.
if(!defined('STRATA_CREDENTIALS')) define('STRATA_CREDENTIALS', DOKU_PLUGIN.'strata/credentials.local.php');

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
        $this->util =& plugin_load('helper', 'strata_util');
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
            $this->ci($val)
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
                msg(sprintf($this->util->getLang('driver_failed_detail'), hsc($dsn), hsc($e->getMessage())), -1);
            } else {
                msg($this->util->getLang('driver_failed'), -1);
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
        $credentials = array(null,null);
        if(@file_exists(STRATA_CREDENTIALS)) {
            $credentials = include(STRATA_CREDENTIALS);
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
        if ($this->_debug) msg(sprintf($this->util->getLang('driver_setup_start'), hsc($driver)));

        // load SQL script
        $sqlfile = DOKU_PLUGIN . "strata/sql/setup-$driver.sql";

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

            if ($this->_debug) msg(sprintf($this->util->getLang('driver_setup_statement'),hsc($s)));
            if(!$this->query($s, $this->util->getLang('driver_setup_failed'))) {
                $this->rollBack();
                return false;
            }
        }
        $this->commit();

        if($this->_debug) msg($this->util->getLang('driver_setup_succes'), 1);

        return true;
    }

    /**
     * Removes a database that was initialized before.
     *
     * @return whether the database was removed successfully
     */
    public function removeDatabase() {
        return $this->query('DROP TABLE data', $this->util->getLang('driver_remove_failed'));
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
            msg(sprintf($this->util->getLang('driver_prepare_failed'),hsc($query), hsc($error[2])),-1);
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
    public function query($query, $message=false) {
        if($this->_db == false) return false;

        if($message === false) {
            $message = $this->util->getLang('driver_query_failed_default');
        }

        $res = $this->_db->query($query);
        if ($res === false) {
            $error = $this->_db->errorInfo();
            msg(sprintf($this->utiutil->getLang('driver_query_failed'), $message, hsc($query), hsc($error[2])),-1);
            return false;
        }
        return true;
    }

    private $transactions = array();
    private $transactionCount = 0;

    private function _transactionId() {
        return "t".$this->transactionCount++;
    }

    /**
     * Begins a transaction.
     */
    public function beginTransaction() {
        if($this->_db == false) return false;

        if(count($this->transactions)) {
            $t = $this->_transactionId();
            array_push($this->transactions, $t);
            $this->_db->query('SAVEPOINT '.$t.';');
            return true;
        } else {
            array_push($this->transactions, 'work');
            return $this->_db->beginTransaction();
        }
    }

    /**
     * Commits the current transaction.
     */
    public function commit() {
        if($this->_db == false) return false;

        array_pop($this->transactions);
        if(count($this->transactions)) {
            return true;
        } else {
            return $this->_db->commit();
        }
    }

    /**
     * Rolls back the current transaction.
     */
    public function rollBack() {
        if($this->_db == false) return false;

        $t = array_pop($this->transactions);
        if(count($this->transactions)) {
            $this->_db->query('ROLLBACK TO '.$t.';');
            return true;
        } else {
            return $this->_db->rollBack();
        }
    }
}

