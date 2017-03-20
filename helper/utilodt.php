<?php
/**
 * DokuWiki Plugin strata (Helper Component for ODT)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  LarsDW223
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * This utility helper offers methods for configuration handling
 * type and aggregator loading, and rendering.
 * 
 * All parts except rendering are inherited from class
 * helper_plugin_strata_util in util.php. The rendering is format
 * specific an creates output for the ODT renderer/format.
 */
class helper_plugin_strata_utilodt extends helper_plugin_strata_util {
    var $stylesCreated = false;

    /**
     * Constructor.
     */
    function __construct() {
        parent::__construct();
    }

    function getMethods() {
        $result = array();
        return $result;
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
        if (!$this->stylesCreated) {
            $this->createSpanStyle($R, 'strata-field', 'class="strata-field"', 'strata-field');
            $this->createSpanStyle($R, 'strata-value', 'class="strata-value"', 'strata-value');
            $this->stylesCreated = true;
        }

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
        if (!$this->stylesCreated) {
            $this->createSpanStyle($R, 'strata-field', 'class="strata-field"', 'strata-field');
            $this->createSpanStyle($R, 'strata-value', 'class="strata-field"', 'strata-field');
            $this->stylesCreated = true;
        }

        // arrayfication of values (if a single value is given)
        if(!is_array($values)) $values = array($values);

        // load type if needed
        if($type == null) $type = $this->loadType($typename);

        // render values
        $firstValue = true;
        $this->openField($mode, $R, $field);
        foreach($values as $value) {
            if(!$firstValue) $R->cdata($separator);
            $this->renderValue($mode, $R, $T, $value, $typename, $hint, $type);
            $firstValue = false;
        }
        $this->closeField($mode, $R);
    }

    function openField($mode, &$R, $field=null) {
        $this->openSpan($R, 'Plugin_Strata_Span_strata-field');
    }

    function closeField($mode, &$R) {
        $this->closeSpan($R);
    }

    function openValue($mode, &$R, $typename) {
        $this->openSpan($R, 'Plugin_Strata_Span_strata-value');
    }

    function closeValue($mode, &$R) {
        $this->closeSpan($R);
    }   

    function renderCaptions($mode, &$R, $fields) {
        foreach($fields as $f) {
            $R->cdata($f['caption']);
        }
    }

    function createSpanStyle (&$R, $name, $attr, $class) {
        $properties = array ();

        if ( method_exists ($R, 'getODTPropertiesFromElement') === true ) {
            // Get CSS properties for ODT export.
            // Set parameter $inherit=false to prevent changiung the font-size and family!
            $R->getODTPropertiesNew ($properties, 'span', $attr, NULL, false);
        } else if ( method_exists ($R, 'getODTProperties') === true ) {
            // Get CSS properties for ODT export (deprecated version).
            $R->getODTProperties ($properties, 'span', $class, NULL);

            if ( empty($properties ['background-image']) === false ) {
                $properties ['background-image'] =
                    $R->replaceURLPrefix ($properties ['background-image'], DOKU_INC);
            }
        } else {
            // To old ODT plugin version.
            return;
        }

        // Newer version create our own common styles.
        $properties ['font-size'] = NULL;
        
        // Create parent style to group the others beneath it        
        if (!$R->styleExists('Plugin_Strata_Spans')) {
            $parent_properties = array();
            $parent_properties ['style-parent'] = NULL;
            $parent_properties ['style-class'] = 'Plugin Strata Spans';
            $parent_properties ['style-name'] = 'Plugin_Strata_Spans';
            $parent_properties ['style-display-name'] = 'Plugin Strata';
            $R->createTextStyle($parent_properties);
        }

        $style_name = 'Plugin_Strata_Span_'.$name;
        if (!$R->styleExists($style_name)) {
            $properties ['style-parent'] = 'Plugin_Strata_Spans';
            $properties ['style-class'] = NULL;
            $properties ['style-name'] = $style_name;
            $properties ['style-display-name'] = $name;
            $R->createTextStyle($properties);
        }
    }

    function openSpan(&$R, $styleName) {
        if ( method_exists ($R, '_odtSpanOpen') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $R->_odtSpanOpen($styleName);
    }

    function closeSpan(&$R) {
        if ( method_exists ($R, '_odtSpanClose') === false ) {
            // Function is not supported by installed ODT plugin version, return.
            return;
        }
        $R->_odtSpanClose();
    }   
}
