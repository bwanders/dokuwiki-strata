<?php

/**
 * DokuWiki Plugin skeleton (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class helper_plugin_stratabasic extends DokuWiki_Plugin {
    function helper_plugin_stratabasic() {
        $this->_types =& plugin_load('helper', 'stratastorage_types');
    }

    function ignorableLine($line) {
        return $line == '' || substr($line,0,2) == '--';
    }

    function updateTypemap(&$typemap, $var, $type, $hint=null) {
        if(empty($typemap[$var]) && $type) {
            $typemap[$var] = array('type'=>$type,'hint'=>$hint);
            return true;
        }

        return false;
    }

    function literal($val) {
        return array('type'=>'literal', 'text'=>$val);
    }

    function variable($var) {
        if($var[0] == '?') $var = substr($var,1);
        return array('type'=>'variable', 'text'=>$var);
    }

    function extractBlock($lines, $blockname) {
        $block = array();
        $rest = array();

        $inblock = false;
        foreach($lines as $line) {
            if(preg_match('/^'.preg_quote($blockname).'\s*{$/',$line)) {
                $inblock = true;
            } elseif($inblock && $line == '}') {
                $inblock = false;
            } else {
                if($inblock) {
                    $block[] = $line;
                } else {
                    $rest[] = $line;
                }
            }
        }

        return array($block, $rest);
    }

    function parseQuery($lines, &$typemap, $select = null) {
        $result = array(
            'select'=>array(),
            'where'=>array(),
            'sort'=>array(),
            'optionals'=>array(),
            'minus'=>array()
        );

        if($select) $result['select'] = $select;

        $block =& $result['where'];
        $blockid = 'where';

        $lineno = 0;
        foreach($lines as $line) {
            $lineno++;
            $line = trim($line);
            if($this->ignorableLine($line)) continue;

            if(preg_match('/^([a-z]+)\s*\{$/S', $line, $match)) {
                // block opener
                switch($match[1]) {
                case 'sort':
                    if(count($result['sort'])) {
                        msg('Strata basic: Query contains double \'<code>sort</code>\' block.',-1);
                        return false;
                    }
                    $block =& $result['sort'];
                    break;
                case 'optional':
                    $new = array();
                    $block =& $new;
                    break;
                case 'minus':
                    $new = array();
                    $block =& $new;
                    break;
                default:
                    msg('Strata basic: Query contains weird block \'<code>'.$match[1].'</code>\'', -1);
                    return false;
                }
                $blockid = $match[1];

            } elseif(in_array($blockid, array('where','optional','minus')) && 
                     preg_match('/^((?:\?[a-zA-Z0-9]+)|(?:\[\[[^]]+\]\]))\s+(?:((?:[-a-zA-Z0-9 ]+)|(?:\?[a-zA-Z0-9]+))(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?):\s*(.+?)\s*$/S',$line,$match)) {
                // triple pattern
                list($_, $subject, $predicate, $type, $hint, $object) = $match;
                if($subject[0] == '?') {
                    $subject = $this->variable($subject);
                    $this->updateTypemap($typemap, $subject['text'], 'ref');
                } else {
                    global $ID;
                    $subject = substr($subject,2,-2);
                    resolve_pageid(getNS($ID), $subject, $exists);
                    $subject = $this->literal($subject);
                }

                if($predicate[0] == '?') {
                    $predicate = $this->variable($predicate);
                    $this->updateTypemap($typemap, $predicate['text'], 'string');
                } else {
                    $predicate = $this->literal($predicate);
                }

                if($object[0] == '?') {
                    $object = $this->variable($object);
                    $this->updateTypemap($typemap, $object['text'], $type, $hint);
                } else {
                    if(!$type) $type = $this->_types->getConf('default_type');
                    $type = $this->_types->loadType($type);
                    $object = $this->literal($type->normalize($object,$hint));
                }

                $block[] = array('type'=>'triple','subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object);

            } elseif(in_array($blockid, array('where','optional','minus')) &&
                     preg_match('/^(?:\?([a-zA-Z0-9]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*(=|!=|>|<|>=|<=|~|!~|\^~|\$~)\s*(.+?)\s*$/S',$line, $match)) {
                // filter pattern
                list($_, $lhs,$type,$hint,$operator,$rhs) = $match;

                $lhs = $this->variable($lhs);

                if($rhs[0] == '?') {
                    $rhs = $this->variable($rhs);
                    $this->updateTypemap($typemap, $rhs['text'], $type, $hint);
                } else {
                    if(!$type) {
                        if(!empty($typemap[$variable])) {
                            extract($typemap[$variable]);
                        } else {
                            $type = $this->_types->getConf('default_type');
                        }
                    }
                    $type = $this->_types->loadType($type);
                    $rhs = $this->literal($type->normalize($rhs,$hint));
                }

                $block[] = array('type'=>'filter','lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs);

            } elseif(in_array($blockid, array('sort')) &&
                     preg_match('/^\?([a-zA-Z0-9]+)\s*(?:\((asc|desc)(?:ending)?\))?$/S',$line,$match)) {
                // sort pattern
                $block[] = array('name'=>$match[1], 'order'=>($match[2]?:'asc'));

            } elseif($line == '}') {
                // block closer
                switch($blockid) {
                case 'optional':
                    $result['optionals'][] = $block;
                    break;
                case 'minus':
                    $result['minus'][] = $block;
                    break;
                case 'sort':
                    break;
                default:
                    msg('Strata basic: Query contains weird closing bracket.', -1);
                    return false;
                }
                $blockid = 'where';
                $block =& $result['where'];
            } else {
                msg('Strata basic: Query contains weird line \'<code>'.hsc($line).'</code>\'.',-1);
                return false;
            }
        }

        return $result;
    }

    function parseFieldsLong($lines, &$typemap) {
        $result = array();

        foreach($lines as $line) {
            $line = trim($line);
            if($this->ignorableLine($line)) {
                continue;
            } elseif(preg_match('/^([^_]*)(?:(_)([a-z0-9]*)(?:\(([^)]+)\))?)?:\s*\?([a-zA-Z0-9]+)$/S',$line, $match)) {
                list($_, $caption, $underscore, $type, $hint, $variable) = $match;
                if(!$underscore || (!$underscore && !$caption && !$type)) $caption = ucfirst($variable);
                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[$variable] = array('caption'=>$caption);
            } else {
                msg('Strata basic: Weird line \'<code>'.hsc($line).'</code>\' in \'<code>fields</code>\' group.', -1);
                return false;
            }
        }

        return $result;
    }

    function parseFieldsShort($line, &$typemap) {
        $result = array();

        if(preg_match_all('/\s*\?([a-zA-Z0-9]+)(?:\s*(\()([^_)]*)(?:_([a-z0-9]*)(?:\(([^)]*)\))?)?\))?/',$line,$match, PREG_SET_ORDER)) {
            foreach($match as $m) {
                list($_, $variable, $parenthesis, $caption, $type, $hint) = $m;
                if(!$parenthesis || (!$parenthesis && !$caption && !$type)) $caption = ucfirst($variable);
                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[$variable] = array('caption'=>$caption);
            }
        }

        return $result;
    }

    function fieldsShortPattern() {
        return '(?:\s+\?[a-zA-Z0-9]+(?:\s*\([^_\)]*(?:_[a-z0-9]*(?:\([^\)]*\))?)?\))?)';
    }
}
