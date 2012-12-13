<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

require_once(DOKU_PLUGIN.'strata/driver/driver.php');

/**
 * The base class for database drivers.
 */
class plugin_strata_driver_sqlite extends plugin_strata_driver {
    public function ci($val='?') {
        return "$val COLLATE NOCASE";
    }

    public function isInitialized() {
        return $this->_db->query("SELECT count(*) FROM sqlite_master WHERE name = 'data'")->fetchColumn() != 0;
    }
}
