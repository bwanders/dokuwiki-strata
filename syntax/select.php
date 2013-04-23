<?php
/**
 * Strata, data entry plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) die('Meh.');
 
/**
 * Select syntax for basic query handling.
 */
class syntax_plugin_strata_select extends DokuWiki_Syntax_Plugin {
    function __construct() {
        $this->helper =& plugin_load('helper', 'strata_syntax');
        $this->util =& plugin_load('helper', 'strata_util');
        $this->triples =& plugin_load('helper', 'strata_triples');
    }

    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'block';
    }

    function getSort() {
        return 450;
    }

    function connectTo($mode) {
    }

    function handle($match, $state, $pos, &$handler) {
        try {
            $result = array();
            $typemap = array();
    
            // allow subclass handling of the whole match
            $match = $this->preprocess($match, $handler, $result, $typemap);
    
            // split into lines and remove header and footer
            $lines = explode("\n",$match);
            $header = trim(array_shift($lines));
            $footer = trim(array_pop($lines));
    
            // allow subclass header handling
            $header = $this->handleHeader($header, $result, $typemap);
    
            // parse projection information in 'short syntax' if available
            if(trim($header) != '') {
                $result['fields'] = $this->helper->parseFieldsShort($header, $typemap);
            }
    
            $tree = $this->helper->constructTree($lines,'query');
    
            // parse long fields, if available
            $longFields = $this->helper->getFields($tree, $typemap);
    
            // check double data
            if(count($result['fields']) && count($longFields)) {
                $this->helper->_fail($this->getLang('error_query_bothfields'));
            }
    
            // assign longfields if necessary
            if(count($result['fields']) == 0) {
                $result['fields'] = $longFields;
            }
    
            // check no data
            if(count($result['fields']) == 0) {
                $this->helper->_fail($this->helper->getLang('error_query_noselect'));
            }
    
            // determine the variables to project
            $projection = array();
            foreach($result['fields'] as $f) $projection[] = $f['variable'];
            $projection = array_unique($projection);
    
            // allow subclass body handling
            $this->handleBody($tree, $result, $typemap);
    
            // parse the query itself
            list($result['query'], $variables) = $this->helper->constructQuery($tree, $typemap, $projection);
    
            // allow subclass footer handling
            $footer = $this->handleFooter($footer, $result, $typemap, $variable);
    
            // check projected variables and load types
            foreach($result['fields'] as $i=>$f) {
                $var = $f['variable'];
                if(!in_array($var, $variables)) {
                    $this->helper->_fail(sprintf($this->helper->getLang('error_query_unknownselect'),utf8_tohtml(hsc($var))));
                }
    
                if(empty($f['type'])) {
                    if(!empty($typemap[$var])) {
                        $result['fields'][$i] = array_merge($result['fields'][$i],$typemap[$var]);
                    } else {
                        list($type, $hint) = $this->util->getDefaultType();
                        $result['fields'][$i]['type'] = $type;
                        $result['fields'][$i]['hint'] = $hint;
                    }
                }
            }
    
            return $result;
        } catch(strata_exception $e) {
            return array('error'=>array(
                'message'=>$e->getMessage(),
                'regions'=>$e->getData(),
                'lines'=>$lines
            ));
        }
    }

    /**
     * Handles the whole match. This method is called before any processing
     * is done by the actual class.
     * 
     * @param match string the complete match
     * @param handler object the parser handler
     * @param result array the result array passed to the render method
     * @param typemap array the type map
     * @return a preprocessed string
     */
    function preprocess($match, &$handler, &$result, &$typemap) {
        return $match;
    }


    /**
     * Handles the header of the syntax. This method is called before
     * the header is handled.
     *
     * @param header string the complete header
     * @param result array the result array passed to the render method
     * @param typemap array the type map
     * @return a string containing the unhandled parts of the header
     */
    function handleHeader($header, &$result, &$typemap) {
        return $header;
    }

    /**
     * Handles the body of the syntax. This method is called before any
     * of the body is handled, but after the 'fields' groups have been processed.
     *
     * @param tree array the parsed tree
     * @param result array the result array passed to the render method
     * @param typemap array the type map
     */
    function handleBody(&$tree, &$result, &$typemap) {
    }

    /**
     * Handles the footer of the syntax. This method is called after the
     * query has been parsed, but before the typemap is applied to determine
     * all field types.
     * 
     * @param footer string the footer string
     * @param result array the result array passed to the render method
     * @param typemape array the type map
     * @param variables array of variables used in query
     * @return a string containing the unhandled parts of the footer
     */
    function handleFooter($footer, &$result, &$typemap, &$variables) {
        return $footer;
    }

    /**
     * This method performs just-in-time modification to prepare
     * the query for use.
     *
     * @param query array the query tree
     * @return the query tree to use
     */
    function prepareQuery($query) {
        // fire event
        trigger_event('STRATA_PREPARE_QUERY', $query);

        // return the (possibly modified) query
        return $query;
    }

    /**
     * This method renders the data view.
     *
     * @param mode the rendering mode
     * @param R the renderer
     * @param data the custom data from the handle phase
     */
    function render($mode, &$R, $data) {
        return false;
    }

    protected function displayError(&$R, $data) {
        $style = '';
        if(isset($data['error']['regions'])) $style = ' strata-debug-continued';
        $R->doc .= '<div class="strata-debug-message '.$style.'">';
        $R->doc .= $R->_xmlEntities($this->helper->getLang('content_error_explanation'));
        $R->doc .= ': '.$data['error']['message'];
        $R->doc .= '</div>';
        if(isset($data['error']['regions'])) $R->doc .= $this->helper->debugTree($data['error']['lines'], $data['error']['regions']);
    }
}
