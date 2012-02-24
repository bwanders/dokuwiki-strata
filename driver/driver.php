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
class plugin_strata_driver {
    /**
     * Returns the syntax to start a transaction.
     */
    public function startTransaction() {
        return "BEGIN TRANSACTION";
    }

    /**
     * Returns the syntax to commit a transaction.
     */
    public function commit() {
        return "COMMIT";
    }

    /**
     * Returns the syntax to roll back a transaction.
     */
    public function rollback() {
        return "ROLLBACK";
    }

    /**
     * Produces the syntax to cast something to a number.
     *
     * @param val string the thing to cast
     */
    public function castToNumber($val) {
        return "CAST($val AS NUMERIC)";
    }
}
