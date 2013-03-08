<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The reference link type.
 */
class plugin_strata_type_ref extends plugin_strata_type_page {
    function __construct() {
        $this->util =& plugin_load('helper', 'strata_util');
        parent::__construct();
    }

    function render($mode, &$R, &$T, $value, $hint) {
        $heading = null;

        // only use heading if allowed by configuration
        if(useHeading('content')) {
            $titles = $T->fetchTriples($value, $this->util->getTitleKey());
            if($titles) {
                $heading = $titles[0]['object'];
            }
        }

        // render internal link
        // (':' is prepended to make sure we use an absolute pagename,
        // internallink resolves page names, but the name is already resolved.)
        $R->internallink(':'.$value, $heading);
    }

    function getInfo() {
        return array(
            'desc'=>'References another piece of data or wiki page, and creates a link. The optional hint is used as namespace for the link. If the hint ends with a #, all values will be treated as fragments.',
            'hint'=>'namespace'
        );
    }
}
