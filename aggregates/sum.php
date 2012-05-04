<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The summation aggregator.
 */
class plugin_strata_aggregate_sum extends plugin_strata_aggregate {
    function aggregate($values, $hint = null) {
        if($hint == 'strict') {
            return array_reduce($values, function(&$state, $item) {
                if(is_numeric($item)) {
                    $state[0] += $item;
                } else {
                    $state[] = $item;
                }
                return $state;
            }, array(0));
        } else {
            return array(array_sum($values));
        }
    }

    function getInfo() {
        return array(
            'desc'=>'Sums up all items. Any item that does not have a clear numeric value (i.e. starts with a number) is counted as 0. If the \'strict\' hint is used, values that are not strictly numeric (i.e. contains only a number) are left intact.',
            'hint'=>'\'strict\' to leave non-numeric values',
            'tags'=>array('numeric')
        );
    }
}
