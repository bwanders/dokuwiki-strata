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
class plugin_strata_driver_pgsql extends plugin_strata_driver {

    public function stringCompare() {
        return 'ILIKE';
    }

    public function castToNumber($val) {
        return "SUBSTRING($val FROM E'^(-?[0-9]+\\\\.?[0-9]*)')::numeric";
    }

    public function orderBy($val) {
        return array(
            "$val IS NOT NULL",
            $this->castToNumber($val),
            $val
        );
    }

    public function isInitialized() {
        return $this->_db->query("SELECT * FROM pg_tables WHERE schemaname = 'public' AND tablename = 'data'")->rowCount() != 0;
    }
}

