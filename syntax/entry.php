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
        $this->_triples =& plugin_load('helper', 'stratastorage_triples');
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
        $this->Lexer->addSpecialPattern('<data(?: [_a-zA-Z0-9 ]+?)?(?: ?#[^>]*?)?>\n.+?\n</data>',$mode, 'plugin_stratabasic_entry');
    }

    function handle($match, $state, $pos, &$handler) {
        $result = array(
            'entry'=>'',
            'data'=>array()
        );

        $lines = explode("\n",$match);

        preg_match('/^<data( [_a-zA-Z0-9 ]+)?(?: ?#([^>]*?))?>/', array_shift($lines), $header);

        foreach(preg_split('/\s+/',trim($header[1])) as $class) {
            if($class == '') continue;
            $result['data'][] = array('key'=>'class','value'=>$class,'type'=>'string', 'hint'=>null);
        }

        $result['entry'] = $header[2];

        if($result['entry'] != '') $result['data'][] = array('key'=>'title','value'=>$result['entry'], 'type'=>'string', 'hint'=>null);

        foreach($lines as $line) {
            if($line == '</data>') break;
            if(preg_match('/^([-a-zA-Z0-9 ]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?(\*)?:(.*)$/',$line,$parts)) {
                if($parts[4] == '*') {
                    $values = array_map('trim',explode(',',$parts[5]));
                } else {
                    $values = array(trim($parts[5]));
                }
                foreach($values as $v) {
                    if($v == '') continue;
                    if(!isset($parts[2]) || $parts[2] == '') {
                        $parts[2] = $this->_types->getConf('default_type');
                    }
                    $result['data'][] = array('key'=>$parts[1],'value'=>$v,'type'=>$parts[2],'hint'=>($parts[3]?:null));
                }
            } else {
                msg('I don\'t understand data entry \''.htmlentities($line).'\'.', -1);
            }
        }

        foreach($result['data'] as &$triple) {
            $type = $this->_types->loadType($triple['type']);
            $triple['type'] = $type;
            $triple['value'] = $type->normalize($triple['value'], $triple['hint']);
        }

        return $result;
    }

    function render($mode, &$R, $data) {
        global $ID;


        if($mode == 'xhtml') {
            $keys = array();
            foreach($data['data'] as $t) {
                if(!isset($keys[$t['key']])) $keys[$t['key']] = array();
                $keys[$t['key']][] = $t;
            }


            $R->table_open();
            $R->tablerow_open();
            $R->tableheader_open(2);
            $heading = '';
            if(isset($keys['title'])) {
                $heading = $keys['title'][0]['value'];
            } elseif (useHeading('content')) {
                $heading = p_get_first_heading($ID);
            } else {
                $heading = noNS($ID);
            }
            $R->doc .= $R->_xmlEntities($heading);
            if(isset($keys['class'])) {
                $R->emphasis_open();
                $R->doc .= ' (';
                $values = $keys['class'];
                for($i=0;$i<count($values);$i++) {
                    $triple =& $values[$i];
                    if($i!=0) $R->doc .= ', ';
                    $triple['type']->render($mode, $R, $triple['value'], $triple['hint']);
                }
                $R->doc .= ')';
                $R->emphasis_close();
            }
            $R->tableheader_close();
            $R->tablerow_close();

            foreach($keys as $key=>$values) {
                if($key == 'title' || $key == 'class') continue;
                $R->tablerow_open();
                $R->tableheader_open();
                $R->doc .= $R->_xmlEntities($key);
                $R->tableheader_close();
                $R->tablecell_open();
                for($i=0;$i<count($values);$i++) {
                    $triple =& $values[$i];
                    if($i!=0) $R->doc .= ', ';
                    $triple['type']->render($mode, $R, $triple['value'], $triple['hint']);
                }
                $R->tablecell_close();
                $R->tablerow_open();
           }

            $R->tablerow_close();
            $R->table_close();
            
            return true;
        } elseif($mode == 'metadata') {
            $triples = array();
            $subject = $ID.'#'.$data['entry'];
            resolve_pageid(getNS($ID),$subject,$exists);
            foreach($data['data'] as $triple) {
                $triple['type']->render($mode, $R, $normalized, $triple['hint']);
                $triples[] = array('subject'=>$subject, 'predicate'=>$triple['key'], 'object'=>$triple['value']);
            }

            $this->_triples->addTriples($triples);
            return true;
        }

        return false;
    }
}
