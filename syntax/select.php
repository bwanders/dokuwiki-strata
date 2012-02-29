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
            'query'=>array(
                'select'=>array(),
                'where'=>array(),
                'sort'=>array(),
                'optionals'=>array(),
                'minus'=>array()
            ),
            'fields'=>array()
        );

        $typemap = array();

        if($header != '<select>') {
            if(preg_match_all('/(?:\?([a-zA-Z0-9]+))(?:\s*\(([^_)]*)(?:_([a-z0-9]+)(?:\(([^)]*)\))?)?\))?/',$header,$match, PREG_SET_ORDER)) {
                foreach($match as $m) {
                    list($_, $variable, $caption, $type, $hint) = $m;
                    $result['query']['select'][] = $variable;
                    $caption = $caption?:ucfirst($variable);
                    if($type) {
                        $typemap[$variable] = array('type'=>$type, 'hint'=>$hint);
                    }
                    $result['fields'][$variable] = array('caption'=>$caption);
                }
            }
        }

        $block =& $result['query']['where'];
        $blockid = 'where';

        $lineno = 0;
        foreach($lines as $line) {
            $lineno++;
            $line = trim($line);
            if($line == '' || substr($line,0,2) == '--') continue;

            if(preg_match('/^([a-z]+)\s*\{$/S', $line, $match)) {
                // block opener
                switch($match[1]) {
                case 'sort':
                    $block =& $result['query']['sort'];
                    break;
                case 'optional':
                    $new = array();
                    $block =& $new;
                    break;
                case 'minus':
                    $new = array();
                    $block =& $new;
                    break;
                case 'fields':
                default:
                    msg('Strata basic: Query contains weird block \''.$match[1].'\'', -1);
                    return array();
                }
                $blockid = $match[1];

            } elseif(in_array($blockid, array('where','optional','minus')) && 
                     preg_match('/^((?:\?[a-zA-Z0-9]+)|(?:\[\[[^]]+\]\]))\s+(?:((?:[-a-zA-Z0-9 ]+)|(?:\?[a-zA-Z0-9]+))(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?):\s*(.+?)\s*$/S',$line,$match)) {
                // triple pattern
                list($_, $subject, $predicate, $type, $hint, $object) = $match;
                if($subject[0] == '?') {
                    $subject = array('type'=>'variable','name'=>substr($subject,1));
                    if(empty($typemap[$subject['name']])) $typemap[$subject['name']] = array('type'=>'ref','hint'=>null);
                } else {
                    global $ID;
                    $subject = substr($subject,2,-2);
                    resolve_pageid(getNS($ID), $subject, $exists);
                    $subject = array('type'=>'literal', 'text'=>$subject);
                }

                if($predicate[0] == '?') {
                    $predicate = array('type'=>'variable','name'=>substr($predicate,1));
                    if(empty($typemap[$predicate['name']])) $typemap[$predicate['name']] = array('type'=>'string','hint'=>null);
                } else {
                    $predicate = array('type'=>'literal', 'text'=>$predicate);
                }

                if($object[0] == '?') {
                    $object = array('type'=>'variable', 'name'=>substr($object,1));
                    if(empty($typemap[$object['name']]) && $type) $typemap[$object['name']] = array('type'=>$type, 'hint'=>$hint);
                } else {
                    if(!$type) $type = $this->_types->getConf('default_type');
                    $type = $this->_types->loadType($type);
                    $object = array('type'=>'literal', 'text'=>$type->normalize($object,$hint));
                }
                $block[] = array('type'=>'triple','subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object);
            } elseif(in_array($blockid, array('where','optional','minus')) &&
                     preg_match('/^(?:\?([a-zA-Z0-9]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*(=|!=|>|<|>=|<=|~|!~|\^~|\$~)\s*(.+?)\s*$/S',$line, $match)) {
                // filter pattern
                list($_, $variable,$type,$hint,$operator,$rhs) = $match;

                if($rhs[0] == '?') {
                    $rhs = array('type'=>'variable', 'name'=>substr($rhs,1));
                    if(empty($typemap[$rhs['name']]) && $type) $typemap[$rhs['name']] = array('type'=>$type, 'hint'=>$hint);
                } else {
                    if(!$type) {
                        if(!empty($typemap[$variable])) {
                            extract($typemap[$variable]);
                        } else {
                            $type = $this->_types->getConf('default_type');
                        }
                    }
                    $type = $this->_types->loadType($type);
                    $rhs = array('type'=>'literal', 'text'=>$type->normalize($rhs,$hint));
                }


                $block[] = array('type'=>'filter','lhs'=>array('type'=>'variable','name'=>$variable), 'operator'=>$operator, 'rhs'=>$rhs);

            } elseif(in_array($blockid, array('sort')) &&
                     preg_match('/^\?([a-zA-Z0-9]+)\s*(?:\((asc|desc)(?:ending)?\))?$/S',$line,$match)) {
                // sort pattern
                $block[] = array('name'=>$match[1], 'order'=>($match[2]?:'asc'));

            } elseif($line == '}') {
                // block closer
                switch($blockid) {
                case 'optional':
                    $result['query']['optionals'][] = $block;
                    break;
                case 'minus':
                    $result['query']['minus'][] = $block;
                    break;
                case 'sort':
                case 'fields':
                    break;
                default:
                    msg('Strata basic: Query contains weird closing bracket.', -1);
                    return array();
                }
                $blockid = 'where';
                $block =& $result['query']['where'];
            } else {
                msg('Strata basic: Query contains weird line (line number '.$lineno.').',-1);
            }
        }

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
