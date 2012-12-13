<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Steven te Brinke <s.tebrinke@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

require_once(DOKU_PLUGIN.'strata/driver/driver.php');

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

    protected function initializeConnection() {
        $this->query('SET NAMES utf8mb4');
        $this->query("SET sql_mode = 'PIPES_AS_CONCAT'"); // Ensure that || works as string concatenation
    }

    public function isInitialized() {
        return $this->_db->query("SHOW TABLES LIKE 'data'")->rowCount() != 0;
    }
}

