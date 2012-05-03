<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * This base class defines the methods required by Strata aggregates.
 *
 * Aggregates are a bit peculiar, as they transform a set of values into
 * a set of values. This allows both normal aggregation (many->one), but
 * also opens up the option of having (many->many) and (one->many)
 * transformations.
 */
class plugin_strata_aggregate {
    /**
     * Aggregates the values and converts them to a new set of values.
     *
     * @param values array the set to aggregate
     * @param hint string the aggregation hint
     * @return an array containing the new values
     */
    function aggregate($values, $hint) {
        return $values;
    }

    /**
     * Returns meta-data on the aggregate. This method returns an array with
     * the following keys:
     *   - desc: A human-readable description of the aggregate
     *   - synthetic: an optional boolean indicating that the aggregate is synthethic
     *   - hint: an optional string indicating what the aggregate hint's function is
     *   - tags: an array op applicable tags
     *
     * @return an array containing the info
     */
    function getInfo() {
        return array(
            'desc'=>'The generic aggregator. It does nothing.',
            'hint'=>false,
            'synthetic'=>true,
            'tags'=>array()
        );
    }
}
