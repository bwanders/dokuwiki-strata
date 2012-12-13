<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The verbatim string type.
 */
class plugin_strata_type_text extends plugin_strata_type {
    // uses base functionality
    function getInfo() {
        return array(
            'desc'=>'Verbatim text. Does not format, ignores hint.'
        );
    }
}
