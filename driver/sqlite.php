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
    // Does not require overrides

    public function isInitialized($file) {
        if($file == ':memory:') {
            return false;
        } else {
            return @file_exists($file) && ((int) @filesize($file) >= 3);
        }
    }
}
