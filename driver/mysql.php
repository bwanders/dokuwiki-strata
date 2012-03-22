<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Steven te Brinke <s.tebrinke@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'stratastorage/driver/driver.php');

/**
 * The MySQL database driver.
 */
class plugin_strata_driver_mysql extends plugin_strata_driver {

    public function castToNumber($val) {
        return "CAST($val AS DECIMAL)";
    }

    public function ci($val='?') {
        return "$val COLLATE utf8mb4_unicode_ci";
    }

    protected function initializePDO($dsn) {
        $credentials = array('','');
        if(@file_exists(DOKU_PLUGIN.'stratastorage/local-mysql.php')) {
            $credentials = include(DOKU_PLUGIN.'stratastorage/local-mysql.php');
        }
        return new PDO($dsn, $credentials[0], $credentials[1]);
    }

    protected function initializeConnection() {
        $this->query('SET NAMES utf8mb4');
        $this->query("SET sql_mode = 'PIPES_AS_CONCAT'"); // Ensure that || works as string concatenation
    }

    public function isInitialized() {
        return $this->_db->query("SHOW TABLES LIKE 'data'")->rowCount() != 0;
    }
}

