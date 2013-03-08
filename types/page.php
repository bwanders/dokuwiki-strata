<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The page link type.
 */
class plugin_strata_type_page extends plugin_strata_type {
    function __construct() {
    }

    function normalize($value, $hint) {
        global $ID;

        // fragment reference special case
        if(!empty($hint) && substr($hint,-1) == '#') {
            $value = $hint.$value;
            resolve_pageid(getNS($hint),$value,$exists);
            return $value;
        }

        $base = ($hint?:getNS($ID));

        // check for local link, and prefix full page id
        // (local links don't get resolved by resolve_pageid)
        if(preg_match('/^#.+/',$value)) {
            $value = $ID.$value;
        }

        // resolve page id with respect to selected base
        resolve_pageid($base,$value,$exists);

        // if the value is empty after resolving, it is a reference to the
        // root starting page. (We can not put the emtpy string into the
        // database as a normalized reference -- this will create problems)
        if($value == '') {
            global $conf;
            $value = $conf['start'];
        }

        return $value;
    }

    function render($mode, &$R, &$T, $value, $hint) {
        // render internal link
        // (':' is prepended to make sure we use an absolute pagename,
        // internallink resolves page names, but the name is already resolved.)
        $R->internallink(':'.$value);
    }

    function getInfo() {
        return array(
            'desc'=>'Links to a wiki page. The optional hint is treated as namespace for the link. If the hint ends with a #, all values will be treated as fragments.',
            'hint'=>'namespace'
        );
    }
}
