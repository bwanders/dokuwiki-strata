<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The unique aggregator.
 */
class plugin_strata_aggregate_unique extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        return array_unique($values);
    }

    function getInfo() {
        return array(
            'desc'=>'Removes all duplicates.',
            'tags'=>array()
        );
    }
}
