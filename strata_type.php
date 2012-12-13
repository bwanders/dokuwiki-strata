<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * This base class defines the methods required by Strata types.
 *
 * There are two kinds of types: normal types and synthetic types.
 * Normal types are meant for users, while synthetic types exist to
 * keep the plugin working. (i.e., unloadable types are faked by a 
 * synthetic type, and non-user types should be synthetic).
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
     * @return true if the mode was handled, false if it was not
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
     * @return the normalized value
     */
    function normalize($value, $hint) {
        return $value;
    }

    /**
     * Returns meta-data on the type. This method returns an array with
     * the following keys:
     *   - desc: A human-readable description of the type
     *   - synthetic: an optional boolean indicating that the type is synthethic
     *                (see class docs for definition of synthetic types)
     *   - hint: an optional string indicating what the type hint's function is
     *   - tags: an array op applicable tags
     *
     * @return an array containing
     */
    function getInfo() {
        return array(
            'desc'=>'The generic type.',
            'hint'=>false,
            'synthetic'=>true,
            'tags'=>array()
        );
    }
}
