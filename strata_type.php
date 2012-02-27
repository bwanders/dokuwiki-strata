<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * This base class defines the methods required by Strata types.
 */
class plugin_strata_type {
    /**
     * Renders the value.
     *
     * @param mode string output format being rendered
     * @param renderer ref reference to the current renderer object
     * @param triples ref reference to the current triples helper
     * @param value string the value to render (this should be a normalized value)
     * @param hint string a type hint
     */
    function render($mode, &$renderer, &$triples, $value, $hint) {
        if($mode == 'xhtml') {
            $renderer->doc .= $renderer->_xmlEntities($value);
            return true;
        }

        return false;
    }

    /**
     * Normalizes the given value
     *
     * @param value string the value to normalize
     * @param hint string a type hint
     */
    function normalize($value, $hint) {
        return $value;
    }

    function getInfo() {
        return array(
            'desc'=>'The generic type.',
            'synthetic'=>true
        );
    }
}
