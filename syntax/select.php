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

    function getUISettings($numFields, $hasUIBlock) {
        $sort_choices = array(
            'y' => array('default', 'yes', 'y'),
            'l' => array('left to right', 'ltr', 'l'),
            'r' => array('right to left', 'rtl', 'r'),
            'n' => array('none', 'no', 'n')
        );
        $filter_choices = array(
            't' => array('text', 't'),
            's' => array('select', 's'),
            'p' => array('prefix select', 'ps'),
            'e' => array('suffix select', 'ss'),
            'n' => array('none', 'no', 'n')
        );
        $globalProperties = array(
            'ui' => $this->getUISettingUI($hasUIBlock),
            'sort' => array('choices' => $sort_choices, 'minOccur' => $numFields, 'maxOccur' => $numFields, 'default' => 'yes'),
            'filter' => array('choices' => $filter_choices, 'minOccur' => $numFields, 'maxOccur' => $numFields, 'default' => 'none')
        );
        $groupProperties = array(
            'sort' => array('choices' => $sort_choices),
            'filter' => array('choices' => $filter_choices),
        );
        return array($globalProperties, $groupProperties);
    }

    function getUISettingUI($hasUIBlock) {
        return array('choices' => array('none' => array('none', 'no', 'n'), 'generic' => array('generic', 'g')), 'default' => ($hasUIBlock ? 'generic' : 'none'));
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        try {
            $result = array();
            $typemap = array();
    
            // allow subclass handling of the whole match
            $match = $this->preprocess($match, $state, $pos, $handler, $result, $typemap);
    
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

            // parse UI group
            $this->handleUI($tree, $result, $typemap);
    
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

    function handleUI(&$tree, &$result, &$typemap) {
        $trees = $this->helper->extractGroups($tree, 'ui');

        list($globalProperties, $groupProperties) = $this->getUISettings(count($result['fields']), count($trees));

        // Extract column settings which are set as a group

        // Extract named column settings
        $namedGroupSettings = array();
        foreach ($result['fields'] as $i => $f) {
            if(isset($namedGroupSettings[$f['caption']])) continue;
            $groups = array();
            foreach($trees as &$t) {
                $groups = array_merge($groups, $this->helper->extractGroups($t, $f['caption']));
            }
            $namedGroupSettings[$f['caption']] = $this->helper->setProperties($groupProperties, $groups);
        }

        // Extract numbered column settings
        $groupsettings = array();
        foreach ($result['fields'] as $i => $f) {
            $groups = array();
            foreach ($trees as &$t) {
                $groups = array_merge($groups, $this->helper->extractGroups($t, '#' . ($i+1)));
            }

            // process settings for this column
            $groupsettings[$i] = $this->helper->setProperties($groupProperties, $groups);

            // fill in unset properties from named settings
            foreach($namedGroupSettings[$f['caption']] as $k=>$v) {
                if(!isset($groupsettings[$i][$k])) {
                    $groupsettings[$i][$k] = $v;
                }
            }
        }

        // Extract global settings
        $result['strata-ui'] = $this->helper->setProperties($globalProperties, $trees);

        // Merge column settings into global ones
        foreach ($groupsettings as $i => $s) {
            foreach ($s as $p => $v) {
                $result['strata-ui'][$p][$i] = $v[0];
            }
        }
    }

    /**
     * Handles the whole match. This method is called before any processing
     * is done by the actual class.
     * 
     * @param match string the complete match
     * @param state the parser state
     * @param pos the position in the source
     * @param handler object the parser handler
     * @param result array the result array passed to the render method
     * @param typemap array the type map
     * @return a preprocessed string
     */
    function preprocess($match, $state, $pos, &$handler, &$result, &$typemap) {
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
    function render($mode, Doku_Renderer $R, $data) {
        return false;
    }

    /**
     * This method renders the container for any strata select.
     *
     * The open tag will contain all give classes plus additional metadata, e.g., generated by the ui group
     *
     * @param mode the render mode
     * @param R the renderer
     * @param data the custom data from the handle phase
     * @param additionalClasses array containing classes to be set on the generated container
     */
    function ui_container_open($mode, &$R, $data, $additionalClasses=array()) {
        if($mode != 'xhtml') return;

        $p = $data['strata-ui'];
        $c = array();

        // Default sort: rtl for suffix and ltr otherwise
        for ($i = 0; $i < count($p['sort']); $i++) {
            if ($p['sort'][$i] == 'y') {
                $p['sort'][$i] = ($p['filter'][$i] == 'e' ? 'r' : 'l');
            }
        }

        if (trim(implode($p['sort']), 'n') != '') {
            $c[] = 'strata-ui-sort';
        }
        if (trim(implode($p['filter']), 'n') != '') {
            $c[] = 'strata-ui-filter';
        }

        $classes = implode(' ', array_merge($c, $additionalClasses));
        $properties = implode(' ', array_map(
            function($k, $v) {
                if (empty($v)) {
                    return '';
                } else {
                    return 'data-strata-ui-' . $k . '="' . implode($v) . '"';
                }
            }, array_keys($p), $p)
        );

        $R->doc .= '<div class="' . $classes . '" ' . $properties . '>' . DOKU_LF;
    }

    function ui_container_close($mode, &$R) {
        if($mode != 'xhtml') return;
        $R->doc .= '</div>' . DOKU_LF;
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
