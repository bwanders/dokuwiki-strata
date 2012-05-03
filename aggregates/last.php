<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The last aggregator.
 */
class plugin_strata_aggregate_last extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        $val = end($values);
        if($val === false ) {
            return array();
        }

        return array($val);
    }

    function getInfo() {
        return array(
            'desc'=>'Selects only the last element.',
            'tags'=>array()
        );
    }
}
