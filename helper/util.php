<?php
/**
 * DokuWiki Plugin strata (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * This utility helper offers methods for configuration handling
 * type and aggregator loading, and rendering.
 */
class helper_plugin_strata_util extends DokuWiki_Plugin {
    function getMethods() {
        $result = array();
        return $result;
    }

    /**
     * The loaded types and aggregates cache.
     */
    var $loaded = array();

    /**
     * Loads something.
     */
    private function _load($kind, $name, $default) {
        // handle null value
        if($name == null) {
            $name = $default;
        }

        // use cache if possible
        if(empty($this->loaded[$kind][$name])) {
            $class = "plugin_strata_${kind}_${name}";
            $this->loaded[$kind][$name] = new $class();
        }

        return $this->loaded[$kind][$name];

    }

    /**
     * Loads a type.
     */
    function loadType($type) {
        list($default,) = $this->getDefaultType();
        return $this->_load('type', $type, $default);
    }

    /**
     * Loads an aggregate.
     */
    function loadAggregate($aggregate) {
        return $this->_load('aggregate', $aggregate, 'all');
    }

    /**
     * Parses a 'name(hint)' pattern.
     *
     * @param string string the text to parse
     * @return an array with a name and hint, or false
     */
    function parseType($string) {
        if(preg_match('/^([a-z0-9]+)(?:\(([^\)]*)\))?$/',$string,$match)) {
            return array(
                $match[1],
                $match[2]
            );
        } else {
            return false;
        }
    }

    /**
     * The parsed configuration types.
     */
    var $configTypes = array();

    /**
     * Parses a type from configuration.
     */
    function _parseConfigType($key) {
        // lazy parse
        if(empty($this->configTypes[$key])) {
            // parse
            $this->configTypes[$key] = $this->parseType($this->getConf($key));

            // handle failed parse
            if($this->configTypes[$key] === false) {
                msg(sprintf($this->getLang('error_types_config'), $key), -1);
                $this->configTypes[$key] = array(
                    'text',
                    null
                );
            }
        }
        
        return $this->configTypes[$key];
    }

    /**
     * Returns the default type.
     */
    function getDefaultType() {
        return $this->_parseConfigType('default_type');
    }

    /**
     * Returns the type used for predicates.
     */
    function getPredicateType() {
        return $this->_parseConfigType('predicate_type');
    }

    /**
     * Returns the normalized value for the 'is a' predicate.
     */
    function getIsaKey() {
        return $this->normalizePredicate($this->getConf('isa_key'));
    }

    /**
     * Returns the normalized valued for the 'title' predicate.
     */
    function getTitleKey() {
        return $this->normalizePredicate($this->getConf('title_key'));
    }

    /**
     * Normalizes a predicate.
     * 
     * @param p the string to normalize
     */
    function normalizePredicate($p) {
        list($type, $hint) = $this->getPredicateType();
        return $this->loadType($type)->normalize($p, $hint);
    }

    /**
     * Renders a predicate.
     *
     * @param mode the rendering mode
     * @param R the renderer
     * @param T the triples helper
     * @param p the predicate
     */
    function renderPredicate($mode, &$R, &$T, $p) {
        list($type, $hint) = $this->getPredicateType();
        return $this->loadType($type)->render($mode, $R, $T, $p, $hint);
    }
}
