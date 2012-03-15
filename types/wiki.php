<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The 'render as wiki text' type.
 */
class plugin_strata_type_wiki extends plugin_strata_type {
    function render($mode, &$R, &$T, $value, $hint) {
        // though this breaks backlink functionality, we really do not want
        // metadata renders of included pieces of wiki.
        if($mode == 'xhtml') {
            // FIXME: This type has a problem with relative links
            // (we might try and normalize relative things, but this will certainly
            //  not captured any specialized syntax from plugins. Though we would grab
            //  [[ ]] and {{ }} links -- those are the main source of linkage anyway.)
            $instructions = p_get_instructions($value);
            $instructions = array_slice($instructions, 2, -2);
            $R->nest($instructions);
        }
    }

    function getInfo() {
        return array(
            'desc'=>'Allows the use of normal dokuwiki syntax. The hint is ignored.'
        );
    }
}
