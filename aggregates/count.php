<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The counting aggregator.
 */
class plugin_strata_aggregate_count extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        return array(count($values));
    }

    function getInfo() {
        return array(
            'desc'=>'Counts the number of items.',
            'tags'=>array()
        );
    }
}
