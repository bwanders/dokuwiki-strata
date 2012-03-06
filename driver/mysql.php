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
    // Does not require overrides

    public function isInitialized($db) {
        return $db->query("SHOW TABLES LIKE 'data'")->rowCount() != 0;
    }
}

