<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'stratastorage/driver/driver.php');

/**
 * The base class for database drivers.
 */
class plugin_strata_driver_sqlite extends plugin_strata_driver {
    protected static $connections = array();

    public function ci($val='?') {
        return "$val COLLATE NOCASE";
    }

    public function isInitialized() {
        return $this->_db->query("SELECT count(*) FROM sqlite_master WHERE name = 'data'")->fetchColumn() != 0;
    }

    /**
     * Initialize the PDO object. This method is overridden because the PDO SQLite
     * driver has concurrency issues when connecting from the same process to the
     * same database.
     *
     * @param dsn string the dsn to use for construction
     * @return the PDO object
     */
    protected function initializePDO($dsn) {
        // lazy create and cache connections to the same DSN
        if(empty(self::$connections[$dsn])) {
            self::$connections[$dsn] = parent::initializePDO($dsn);
        }

        return self::$connections[$dsn];
    }

}
