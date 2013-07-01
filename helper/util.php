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
    /**
     * Constructor.
     */
    function __construct() {
        // we can't depend on the syntax helper due to recursive dependencies.
        // Since we really only need the pattern helper anyway, we grab it
        // directly. (This isn't the nicest solution -- but depending on
        // a helper that depends on us isn't either)
        $this->patterns = helper_plugin_strata_syntax::$patterns;
    }

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
        $p = $this->patterns;
        if(preg_match("/^({$p->type})?$/", $string, $match)) {
            list($type, $hint) = $p->type($match[1]);
            return array($type, $hint);
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
    function getIsaKey($normalized=true) {
        $result = $this->getConf('isa_key');
        if($normalized) $result = $this->normalizePredicate($result);
        return $result;
    }

    /**
     * Returns the normalized valued for the 'title' predicate.
     */
    function getTitleKey($normalized=true) {
        $result = $this->getConf('title_key');
        if($normalized) $result = $this->normalizePredicate($result);
        return $result;
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
     * Renders a predicate as a full field.
     *
     * @param mode the rendering mode
     * @param R the renderer
     * @param T the triples helper
     * @param p the predicate
     */
    function renderPredicate($mode, &$R, &$T, $p) {
        list($typename, $hint) = $this->getPredicateType();
        $this->renderField($mode, $R, $T, $p, $typename, $hint);
    }

    /**
     * Renders a single value. If the mode is xhtml, this also surrounds the value with
     * the necessary <span> tag to allow styling of types and to ease extraction of values
     * with javascript.
     * 
     * @param mode the rendering mode
     * @param R the renderer
     * @param T the triples helper
     * @param value the value to render
     * @param typename name of the type
     * @param hint optional type hint
     * @param type optional type object, if omitted the typename will be used to get the type
     */
    function renderValue($mode, &$R, &$T, $value, $typename, $hint=null, &$type=null) {
        // load type if needed
        if($type == null)  $type = $this->loadType($typename);

        // render value
        $this->openValue($mode, $R, $typename);
        $type->render($mode, $R, $T, $value, $hint);
        $this->closeValue($mode, $R);
    }

    /**
     * Renders multiple values. If the mode is xhtml, this also surrounds the field with
     * the necessary <span> tag to allow styling of fields and to ease extraction of values
     * with javascript.
     * 
     * @param mode the rendering mode
     * @param R the renderer
     * @param T the triples helper
     * @param values a list of values to render, or optionally a single value
     * @param typename the name of the type
     * @param hint optional type hint
     * @param type optional type object, if omitted typename will be used
     * @param field the field name of this field
     * @param separator the seperation string to use in-between values
     */
    function renderField($mode, &$R, &$T, $values, $typename, $hint=null, &$type=null, $field=null, $separator=', ') {
        // arrayfication of values (if a single value is given)
        if(!is_array($values)) $values = array($values);

        // load type if needed
        if($type == null) $type = $this->loadType($typename);

        // render values
        $firstValue = true;
        $this->openField($mode, $R, $field);
        foreach($values as $value) {
            if(!$firstValue) $R->doc .= $separator;
            $this->renderValue($mode, $R, $T, $value, $typename, $hint, $type);
            $firstValue = false;
        }
        $this->closeField($mode, $R);
    }

    function openField($mode, &$R, $field=null) {
        if($mode == 'xhtml') $R->doc .= '<span class="strata-field" '.(!empty($field)?'data-field="'.hsc($field).'"':'').'>';
    }

    function closeField($mode, &$R) {
        if($mode == 'xhtml') $R->doc .= '</span>';
    }

    function openValue($mode, &$R, $typename) {
        if($mode == 'xhtml') $R->doc .= '<span class="strata-value strata-type-'.$typename.'">';
    }

    function closeValue($mode, &$R) {
        if($mode == 'xhtml') $R->doc .= '</span>';
    }   

    function renderCaptions($mode, &$R, $fields) {
        if($mode == 'xhtml') {
            foreach($fields as $f) {
                $R->doc .= '<div class="strata-caption hidden" data-field="'.hsc($f['variable']).'">';
                $R->doc .= $R->_xmlEntities($f['caption']);
                $R->doc .= '</div>'.DOKU_LF;
            }
        }
    }
}
