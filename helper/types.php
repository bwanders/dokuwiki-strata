<?php
/**
 * DokuWiki Plugin stratastorage (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The types helper is used to cached types and aggregates.
 */
class helper_plugin_stratastorage_types extends DokuWiki_Plugin {
    /**
     * The currently loaded types.
     */
    var $loaded = array();

    function __construct() {
        $this->loaded['type'] = array();
        $this->loaded['aggregate'] = array();
    }

    function getMethods() {
        $result = array();
        $result[] = array(
            'name'=> 'loadType',
            'desc'=> 'Loads a type and returns it.',
            'params'=> array(
                'type'=>'string'
            ),
            'return' => array('type'=>'object')
        );

        $result[] = array(
            'name'=> 'loadAggregate',
            'desc'=> 'Loads an aggregate and returns it.',
            'params'=> array(
                'type'=>'string'
            ),
            'return' => array('type'=>'object')
        );

        $result[] = array(
            'name'=> 'parseType',
            'desc'=> "Parses a 'name(hint)' pattern",
            'params'=> array(
                'text'=>'string'
            ),
            'return'=>array('parsed'=>'array')
        );

        $result[] = array(
            'name'=> 'getDefaultType',
            'desc'=> 'Determines the default type name and hint',
            'params'=> array(
            ),
            'return' => array('type'=>'array')
        );

        $result[] = array(
            'name'=> 'getDefaultPredicateType',
            'desc'=> 'Determines the default predicate type name and hint',
            'params'=> array(
            ),
            'return' => array('type'=>'array')
        );

        return $result;
    }

    /**
     * Loads something.
     */
    function _load($kind, $name, $default) {
        // handle null value
        if($name == null) {
            $name = $default;
        }

        // use cache if possible
        if(!isset($this->loaded[$kind][$name])) {
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

    var $configTypes = array();

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

    function _parseConfigType($key) {
        // laze load
        if($this->defaultType == null) {
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
     * Returns the configured default type.
     */
    function getDefaultType() {
        return $this->_parseConfigType('default_type');
    }

    /**
     * Returns the configured predicate type.
     */
    function getPredicateType() {
        return $this->_parseConfigType('predicate_type');
    }
}
