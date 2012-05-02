<?php
/**
 * Strata Basic, data entry plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * Select syntax for basic query handling.
 */
class syntax_plugin_stratabasic_select extends DokuWiki_Syntax_Plugin {
    function __construct() {
        $this->helper =& plugin_load('helper', 'stratabasic');
        $this->types =& plugin_load('helper', 'stratastorage_types');
        $this->triples =& plugin_load('helper', 'stratastorage_triples', false);
        $this->triples->initialize();
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
        $this->Lexer->addSpecialPattern('<table'.$this->helper->fieldsShortPattern().'* *>\n.+?\n</table>',$mode, 'plugin_stratabasic_select');
    }

    function handle($match, $state, $pos, &$handler) {
        $result = array();
        $typemap = array();

        // allow subclass handling of the whole match
        $match = $this->preprocess($match, $handler, $result, $typemap);

        // split into lines and remove header and footer
        $lines = explode("\n",$match);
        $header = array_shift($lines);
        $footer = array_pop($lines);

        // allow subclass header handling
        $header = $this->handleHeader($header, $result, $typemap);

        // parse projection information in 'short syntax' if available
        if(trim($header) != '') {
            $result['fields'] = $this->helper->parseFieldsShort($header, $typemap);
        }

        $tree = $this->helper->constructTree($lines,'query');

        // allow subclass body handling
        $this->handleBody($tree, $result, $typemap);

        // extract the projection information in 'long syntax' if available
        $fieldsGroups = $this->helper->extractGroups($tree, 'fields');

        // parse 'long syntax' if we don't have projection information yet
        if(count($fieldsGroups)) {
            if(count($result['fields'])) {
                msg($this->helper->getLang('error_query_bothfields'),-1);
                return array();
            } else {
                if(count($fieldsGroups) > 1) {
                    msg($this->helper->getLang('error_query_fieldsgroups'),-1);
                    return array();
                }

                $fieldsLines = $this->helper->extractText($fieldsGroups[0]);
                if(count($fieldsGroups[0]['cs'])) {
                    msg(sprintf($this->helper->getLang('error_query_fieldsblock'),( isset($fieldsGroups[0]['cs'][0]['tag']) ? sprintf($this->helper->getLang('named_group'),hsc($fieldsGroups[0]['cs'][0]['tag'])) : $this->helper->getLang('unnamed_group'))),-1);
                    return array();
                }
                $result['fields'] = $this->helper->parseFieldsLong($fieldsLines, $typemap);
                if(!$result['fields']) return array();
            }
        }

        if(empty($result['fields']) || count($result['fields']) == 0) {
            msg($this->helper->getLang('error_query_noselect'),-1);
            return array();
        }

        // determine the variables to project
        $projection = array();
        foreach($result['fields'] as $f) $projection[] = $f['variable'];
        $projection = array_unique($projection);

        // parse the query itself
        list($result['query'], $variables) = $this->helper->constructQuery($tree, $typemap, $projection);
        if(!$result['query']) return array();


        // allow subclass footer handling
        $footer = $this->handleFooter($footer, &$result, &$typemap, &$variable);

        // check projected variables and load types
        foreach($result['fields'] as $i=>$f) {
            $var = $f['variable'];
            if(!in_array($var, $variables)) {
                msg(sprintf($this->helper->getLang('error_query_unknownselect'),utf8_tohtml(hsc($var))),-1);
                return array();
            }

            if(empty($f['type'])) {
                if(!empty($typemap[$var])) {
                    $result['fields'][$i] = array_merge($result['fields'][$i],$typemap[$var]);
                } else {
                    list($type, $hint) = $this->types->getDefaultType();
                    $result['fields'][$i]['type'] = $type;
                    $result['fields'][$i]['hint'] = $hint;
                }
            }
        }

        return $result;
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
        // remove prefix and suffix
        return preg_replace('/(^<table)|( *>$)/','',$header);
    }

    /**
     * Handles the body of the syntax. This method is called before any
     * of the body is handled.
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
        return '';
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
        trigger_event('STRATABASIC_PREPARE_QUERY', $query);

        // return the (possibly modified) query
        return $query;
    }

    function render($mode, &$R, $data) {
        if($data == array()) {
            return;
        }

        $query = $this->prepareQuery($data['query']);

        // execute the query
        $result = $this->triples->queryRelations($query);

        if($result == false) {
            return;
        }

        // prepare all columns
        foreach($data['fields'] as $meta) {
            $fields[] = array(
                'variable'=>$meta['variable'],
                'caption'=>$meta['caption'],
                'type'=>$this->types->loadType($meta['type']),
                'hint'=>$meta['hint'],
                'aggregate'=>$this->types->loadAggregate($meta['aggregate']),
                'aggregateHint'=>$meta['aggregateHint']
            );
        }

        if($mode == 'xhtml') {
            // render header
            $R->doc .= '<div class="stratabasic-table">'.DOKU_LF;
            $R->table_open();
            $R->doc .= '<thead>'.DOKU_LF;
            $R->tablerow_open();

            // render all columns
            foreach($fields as $f) {
                $R->tableheader_open();
                $R->doc .= $R->_xmlEntities($f['caption']);
                $R->tableheader_close();
            }
            $R->tablerow_close();
            $R->doc .= '</thead>'.DOKU_LF;

            $R->doc .= '<tbody>'.DOKU_LF;
            // render each row
            foreach($result as $row) {
                $R->tablerow_open();
                    foreach($fields as $f) {
                        $R->tablecell_open();
                        $first = true;
                        foreach($f['aggregate']->aggregate($row[$f['variable']],$f['aggregateHint']) as $value) {
                            if(!$first) $R->doc .= ', ';
                            $f['type']->render($mode, $R, $this->triples, $value, $f['hint']);
                            $first = false;
                        }
                        $R->tablecell_close();
                    }
                $R->tablerow_close();
            }
            $result->closeCursor();
            $R->doc .= '</tbody>'.DOKU_LF;

            $R->table_close();
            $R->doc .= '</div>'.DOKU_LF;

            return true;
        } elseif($mode == 'metadata') {
            // render all rows in metadata mode to enable things like backlinks
            foreach($result as $row) {
                foreach($fields as $f) {
                    foreach($f['aggregate']->aggregate($row[$f['variable']],$f['aggregateHint']) as $value) {
                        $f['type']->render($mode, $R, $this->triples, $value, $f['hint']);
                    }
                }
            }
            $result->closeCursor();

            return true;
        }

        return false;
    }
}
