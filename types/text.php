<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The verbatim string type.
 */
class plugin_strata_type_text extends plugin_strata_type {
    // uses base functionality
    function getInfo() {
        return array(
            'desc'=>'Verbatim text. Does not format.'
        );
    }
}
