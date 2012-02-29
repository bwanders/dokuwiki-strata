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
        $this->Lexer->addSpecialPattern('<select(?:\s+\?[a-zA-Z0-9]+(?:\s*\([^_\)]*(?:_[a-z0-9]+(?:\([^\)]*\))?)?\))?)*>\n.+?\n</select>',$mode, 'plugin_stratabasic_select');
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
            if(preg_match_all('/(?:\?([a-zA-Z0-9]+))(?:\s*(\()([^_)]*)(?:_([a-z0-9]+)(?:\(([^)]*)\))?)?\))?/',$header,$match, PREG_SET_ORDER)) {
                foreach($match as $m) {
                    list($_, $variable, $parenthesis, $caption, $type, $hint) = $m;
                    if(!$parenthesis || (!$parenthesis && !$caption && !$type)) $caption = ucfirst($variable);
                    $this->helper->update_typemap($typemap, $variable, $type, $hint);
                    $result['fields'][$variable] = array('caption'=>$caption);
                }
            }
        }

        list($fields, $lines) = $this->helper->extract_block($lines, 'fields');

        if(count($fields)) {
            if(count($result['fields'])) {
                msg('Strata basic: Query contains both \'fields\' group and column.',-1);
                return array();
            } else {
                foreach($fields as $line) {
                    $line = trim($line);
                    if($this->helper->ignorable_line($line)) {
                        continue;
                    } elseif(preg_match('/^([^_]*)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?:\s*\?([a-zA-Z0-9]+)$/S',$line, $match)) {
                        list($_, $caption, $type, $hint, $variable) = $match;
                        if(!$caption && !$type) $caption = ucfirst($variable);
                        $this->helper->update_typemap($typemap, $variable, $type, $hint);
                        $result['fields'][$variable] = array('caption'=>$caption);
                    } else {
                        msg('Strata basic: Weird line in fields group.', -1);
                        return array();
                    }
                }
            }
        }

        $result['query'] = $this->helper->parse_query($lines, $typemap, array_keys($result['fields']));
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
        global $ID;


        if($mode == 'xhtml') {
            $R->preformatted(print_r($data,1));
            return true;
        } elseif($mode == 'metadata') {
            return false;
        }

        return false;
    }
}
