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
 * The types helper is used to cached types.
 */
class helper_plugin_stratastorage_types extends DokuWiki_Plugin {
    /**
     * The currently loaded types.
     */
    var $loaded = array();

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
            'name'=> 'getDefaultType',
            'desc'=> 'Determines the default type name',
            'params'=> array(
            ),
            'return' => array('type'=>'string')
        );

        $result[] = array(
            'name'=> 'getDefaultTypeHint',
            'desc'=> 'Determines the default type hint',
            'params'=> array(
            ),
            'return' => array('type'=>'string')
        );


        return $result;
    }

    /**
     * Loads a type.
     */
    function loadType($type) {
        // handle null type
        if($type == null) {
            list($type,) = $this->getDefaultType();
        }

        // use cached if possible
        if(!isset($this->loaded[$type])) {
            $class = "plugin_strata_type_".$type;
            $this->loaded[$type] = new $class();
        }

        return $this->loaded[$type];
    }

    var $configTypes = array();

    function _parseConfigType($key) {
        if($this->defaultType == null) {
            if(preg_match('/^([a-z0-9]+)(?:\(([^\)]*)\))?$/',$this->getConf($key),$match)) {
                $this->configTypes[$key] = array(
                    $match[1],
                    $match[2]
                );
            } else {
                msg('Strata storage: Invalid '.$key.' type configuration, falling back to <code>text</code>',-1);
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
