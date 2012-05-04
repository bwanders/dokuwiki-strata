<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The minimum aggregator.
 */
class plugin_strata_aggregate_min extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        if($hint == 'strict') $values = array_filter($values, 'is_numeric');
        if(empty($values)) return array();
        return array(min($values));
    }

    function getInfo() {
        return array(
            'desc'=>'Returns the minimum value. Any item that does not have a clear numeric value (i.e. starts with a number) is counted as 0. If the \'strict\' hint is used, values that are not strictly numeric (i.e. contains only a number) are ignored.',
            'hint'=>'\'strict\' to ignore non-numeric values',
            'tags'=>array('numeric')
        );
    }
}
