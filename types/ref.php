<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The reference link type.
 */
class plugin_strata_type_ref extends plugin_strata_type_page {
    function render($mode, &$R, &$T, $value, $hint) {
        $heading = null;
        $titles = $T->fetchTriples($value, 'title');
        if($titles) {
            $heading = $titles[0]['object'];
        }

        // render internal link
        // (':' is prepended to make sure we use an absolute pagename,
        // internallink resolves page names, but the name is already resolved.)
        $R->internallink(':'.$value, $heading);
    }

    function getInfo() {
        return array(
            'desc'=>'The value is used as a reference to another piece of data or wiki page and linked accordingly. The optional hint is used as namespace for the link.'
        );
    }
}
