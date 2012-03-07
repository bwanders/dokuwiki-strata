<?php
/**
 * Strata Basic, data entry plugin
 *
 * The syntax is a work in progress.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_stratabasic_select extends DokuWiki_Syntax_Plugin {
    function syntax_plugin_stratabasic_select() {
        $this->helper =& plugin_load('helper', 'stratabasic');
        $this->_types =& plugin_load('helper', 'stratastorage_types');
        $this->_triples =& plugin_load('helper', 'stratastorage_triples', false);
        $this->_triples->initialize();
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
        // ')' between  [^ and ] escaped to work around dokuwiki's pattern handling
        // (The lexer uses ( and ) as delimiter patterns)
        $this->Lexer->addSpecialPattern('<select'.$this->helper->fieldsShortPattern().'*>\n.+?\n</select>',$mode, 'plugin_stratabasic_select');
    }

    function handle($match, $state, $pos, &$handler) {
        $lines = explode("\n",$match);
        $header = array_shift($lines);
        $footer = array_pop($lines);

        $result = array(
            'fields'=>array()
        );

        $typemap = array();

        if($header != '<select>') {
            $result['fields'] = $this->helper->parseFieldsShort($header,$typemap);
        }

        list($fields, $lines) = $this->helper->extractBlock($lines, 'fields');

        if(count($fields)) {
            if(count($result['fields'])) {
                msg('Strata basic: Query contains both \'fields\' group and column.',-1);
                return array();
            } else {
                $result['fields'] = $this->helper->parseFieldsLong($fields, $typemap);
                if(!$result['fields']) return array();
            }
        }

        $result['query'] = $this->helper->parseQuery($lines, $typemap, array_keys($result['fields']));
        if(!$result['query']) return array();

        foreach($result['fields'] as $var=>$f) {
            if(empty($f['type'])) {
                if(!empty($typemap[$var])) {
                    $result['fields'][$var] = array_merge($result['fields'][$var],$typemap[$var]);
                } else {
                    $result['fields'][$var]['type'] = $this->_types->getConf('default_type');
                }
            }
        }

        return $result;
    }

    function render($mode, &$R, $data) {
        $result = $this->_triples->queryRelations($data['query']);

        if($mode == 'xhtml') {
            $R->table_open();
            $R->tablerow_open();
            $fields = array();
            foreach($data['fields'] as $field=>$meta) {
                $fields[] = array(
                    'name'=>$field,
                    'type'=>$this->_types->loadType($meta['type'])
                );
                $R->tableheader_open();
                $R->doc .= $R->_xmlEntities($meta['caption']);
                $R->tableheader_close();
            }
            $R->tablerow_close();
            foreach($result as $row) {
                $R->tablerow_open();
                    foreach($fields as $f) {
                        $R->tablecell_open();
                        $f['type']->render($mode, $R, $this->_triples, $row[$f['name']], $f['hint']);
                        $R->tablecell_close();
                    }
                $R->tablerow_close();
            }
            $R->table_close();
            return true;
        } elseif($mode == 'metadata') {
            return false;
        }

        return false;
    }
}
