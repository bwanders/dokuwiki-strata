<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The maximum aggregator.
 */
class plugin_strata_aggregate_max extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        if(empty($values)) return array();
        return array(max($values));
    }

    function getInfo() {
        return array(
            'desc'=>'Returns the maximum value. Any item that does not have a clear numeric value (i.e. starts with a number) is counted as 0.',
            'tags'=>array('numeric')
        );
    }
}
