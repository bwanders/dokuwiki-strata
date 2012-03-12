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
    function syntax_plugin_stratabasic_select() {
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
        $this->Lexer->addSpecialPattern('<select'.$this->helper->fieldsShortPattern().'* *>\n.+?\n</select>',$mode, 'plugin_stratabasic_select');
    }

    function handle($match, $state, $pos, &$handler) {
        // split into lines and remove header and footer
        $lines = explode("\n",$match);
        $header = array_shift($lines);
        $footer = array_pop($lines);

        $result = array();

        $typemap = array();


        // allow subclass header handling
        $header = $this->handleHeader($header, $result, $typemap);

        // parse projection information in 'short syntax' if available
        if(trim($header) != '') {
            $result['fields'] = $this->helper->parseFieldsShort($header, $typemap);
        }


        // allow subclass body handling
        $lines = $this->handleBody($lines, $result, $typemap);

        // extract the projection information in 'long syntax' if available
        list($fields, $lines) = $this->helper->extractBlock($lines, 'fields');

        // parse 'long syntax' if we don't have projection information yet
        if(count($fields)) {
            if(count($result['fields'])) {
                msg('Strata basic: Query contains both \'fields\' group and normal selection.',-1);
                return array();
            } else {
                $result['fields'] = $this->helper->parseFieldsLong($fields, $typemap);
                if(!$result['fields']) return array();
            }
        }

        if(empty($result['fields']) || count($result['fields']) == 0) {
            msg('Strata basic: I don\'t know which fields to select!',-1);
            return array();
        }

        // parse the query itself
        list($result['query'], $variables) = $this->helper->parseQuery($lines, $typemap, array_keys($result['fields']));
        if(!$result['query']) return array();


        // allow subclass footer handling
        $footer = $this->handleFooter($footer, &$result, &$typemap, &$variable);

        // check projected variables and load types
        foreach($result['fields'] as $var=>$f) {
            if(!in_array($var, $variables)) {
                msg('Strata basic: Query selects unknown field \''.hsc($var).'\'',-1);
                return array();
            }

            if(empty($f['type'])) {
                if(!empty($typemap[$var])) {
                    $result['fields'][$var] = array_merge($result['fields'][$var],$typemap[$var]);
                } else {
                    $result['fields'][$var]['type'] = $this->types->getConf('default_type');
                }
            }
        }

        return $result;
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
        return preg_replace('/(^<select)|( *>$)/','',$header);
    }

    /**
     * Handles the body of the syntax. This method is called before any
     * of the body is handled.
     *
     * @param lines array a list of lines in the body
     * @param result array the result array passed to the render method
     * @param typemap array the type map
     * @return an array containing all unhandled lines
     */
    function handleBody($lines, &$result, &$typemap) {
        return $lines;
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

    function render($mode, &$R, $data) {
        if($data == array()) {
            return;
        }

        // execute the query
        $result = $this->triples->queryRelations($data['query']);

        // prepare all columns
        foreach($data['fields'] as $field=>$meta) {
            $fields[] = array(
                'name'=>$field,
                'caption'=>$meta['caption'],
                'type'=>$this->types->loadType($meta['type']),
                'hint'=>$meta['hint']
            );
        }

        if($mode == 'xhtml') {
            // render header
            $R->table_open();
            $R->tablerow_open();

            // render all columns
            foreach($fields as $f) {
                $R->tableheader_open();
                $R->doc .= $R->_xmlEntities($f['caption']);
                $R->tableheader_close();
            }
            $R->tablerow_close();

            // render each row
            foreach($result as $row) {
                $R->tablerow_open();
                    foreach($fields as $f) {
                        $R->tablecell_open();
                        if($row[$f['name']] != null) {
                            $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                        }
                        $R->tablecell_close();
                    }
                $R->tablerow_close();
            }
            $result->closeCursor();

            $R->table_close();

            return true;
        } elseif($mode == 'metadata') {
            // render all rows in metadata mode to enable things like backlinks
            foreach($result as $row) {
                foreach($fields as $f) {
                    if($row[$f['name']] != null) {
                        $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                    }
                }
            }
            $result->closeCursor();

            return true;
        }

        return false;
    }
}
