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
class plugin_strata_driver_pgsql extends plugin_strata_driver {

    public function stringCompare() {
        return 'ILIKE';
    }

    public function orderBy($val, $asc=true) {
        $order = $asc ? 'ASC' : 'DESC';
        // Sort first on numeric prefix and then on full string
        return "substring($val from '^(-?[0-9]+\.?[0-9]*)')::numeric $order, $val $order";
    }

    public function isInitialized() {
        return $this->_db->query("SELECT * FROM pg_tables WHERE schemaname = 'public' AND tablename = 'data'")->rowCount() != 0;
    }
}

