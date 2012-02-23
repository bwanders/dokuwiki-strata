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
class syntax_plugin_stratabasic_entry extends DokuWiki_Syntax_Plugin {
    function syntax_plugin_stratabasic_entry() {
        $this->_types =& plugin_load('helper', 'stratastorage_types');
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
        $this->Lexer->addSpecialPattern('<data(?: [_a-zA-Z0-9 ]+?)?(?: ?#[^>]*?)?>\n.+?\n</data>',$mode, 'plugin_stratabasic_entry');
    }

    function handle($match, $state, $pos, &$handler) {
        $result = array(
            'entry'=>'',
            'triples'=>array()
        );

        $lines = explode("\n",$match);

        preg_match('/^<data( [_a-zA-Z0-9 ]+)?(?: ?#([^>]*?))?>/', array_shift($lines), $header);

        foreach(preg_split('/\s+/',trim($header[1])) as $class) {
            $result['triples'][] = array('key'=>'class','value'=>$class,'type'=>'string', 'hint'=>null);
        }

        $result['entry'] = $header[2];

        foreach($lines as $line) {
            if($line == '</data>') break;
            if(preg_match('/^([-a-zA-Z0-9 ]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?(\*)?:(.*)$/',$line,$parts)) {
                if($parts[4] == '*') {
                    $values = array_map('trim',explode(',',$parts[5]));
                } else {
                    $values = array(trim($parts[5]));
                }
                foreach($values as $v) {
                    if(!isset($parts[2]) || $parts[2] == '') {
                        $parts[2] = $this->_types->getConf('default_type');
                    }
                    $result['triples'][] = array('key'=>$parts[1],'value'=>$v,'type'=>$parts[2],'hint'=>$parts[3]);
                }
            } else {
                msg('I don\'t understand data entry \''.htmlentities($line).'\'.', -1);
            }
        }

        return $result;
    }

    function render($mode, &$R, $data) {
        if($mode == 'xhtml') {
            $R->table_open();

            $R->tablerow_open();
            $R->tableheader_open(2);
            $R->doc .="Header";
            $R->tableheader_close();
            $R->tablerow_close();

            foreach($data['triples'] as $triple) {
                $R->tablerow_open();
                $R->tableheader_open();
                $R->doc .= $R->_xmlEntities($triple['key']. '::'.$triple['type'].((isset($triple['hint']) && $triple['hint']!='')?'('.$triple['hint'].')':''));
                $R->tableheader_close();
                $R->tablecell_open();
                $type = $this->_types->loadType($triple['type']);
                $normalized = $type->normalize($triple['value'], $triple['hint']);
                $type->render($mode, $R, $normalized, $triple['hint']);
                $R->tablecell_close();
                $R->tablerow_open();
           }

            $R->tablerow_close();
            $R->table_close();
            
            return true;
        }

        return false;
    }
}
