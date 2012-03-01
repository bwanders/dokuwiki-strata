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
     * Produces the syntax to cast something to a number.
     *
     * @param val string the thing to cast
     */
    public function castToNumber($val) {
        return "CAST($val AS NUMERIC)";
    }

    /**
     * Determines whether the database is initialised.
     *
     * @param connection string the connection part of the PDO Data Source Name
     * @return boolean true if the database is initialised
     */
    public abstract function isInitialized($connection);
}
