<?php
/**
 * DokuWiki Plugin stratabasic (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * Helper plugin for common syntax parsing.
 */
class helper_plugin_stratabasic extends DokuWiki_Plugin {
    function helper_plugin_stratabasic() {
        $this->_types =& plugin_load('helper', 'stratastorage_types');
    }

    /**
     * Determines whether a line can be ignored.
     */
    function ignorableLine($line) {
        return $line == '' || substr(ltrim($line),0,2) == '--';
    }

    /**
     * Updates the given typemap with new information.
     *
     * @param typemap array a typemap
     * @param var string the name of the variable
     * @param type string the type of the variable
     * @param hint string the type hint of the variable
     */
    function updateTypemap(&$typemap, $var, $type, $hint=null) {
        if(empty($typemap[$var]) && $type) {
            $typemap[$var] = array('type'=>$type,'hint'=>$hint);
            return true;
        }

        return false;
    }

    /**
     * Constructs a literal with the given text.
     */
    function literal($val) {
        return array('type'=>'literal', 'text'=>$val);
    }

    /**
     * Constructs a variable with the given name.
     */
    function variable($var) {
        if($var[0] == '?') $var = substr($var,1);
        return array('type'=>'variable', 'text'=>$var);
    }

    /**
     * Extracts a block from the array of lines. The block
     * should not contain nested blocks.
     * 
     * @param lines array the lines to extract from
     * @param blockname string the name of the blocka
     * @return an array with two members: the lines in the block, and the 
     *         lines outside of the block
     */
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

    /**
     * Parses a query. Uses the given typemap, and optionally
     * uses the already determined projection.
     * 
     * @param lines array a list of lines
     * @param typemap array the typemap to use
     * @param select array an optional array with projection information
     * @return an array with the abstract query tree and a list of used variables
     */
    function parseQuery($lines, &$typemap, $select = null) {
        $result = array(
            'select'=>array(),
            'where'=>array(),
            'sort'=>array(),
            'optionals'=>array(),
            'minus'=>array()
        );

        $variables = array();

        if($select) $result['select'] = $select;

        // start with the base group
        $block =& $result['where'];
        $blockid = 'where';

        foreach($lines as $line) {
            // we only parse useful lines
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
                    $variables[] = $subject['text'];
                    $this->updateTypemap($typemap, $subject['text'], 'ref');
                } else {
                    global $ID;
                    $subject = substr($subject,2,-2);
                    resolve_pageid(getNS($ID), $subject, $exists);
                    $subject = $this->literal($subject);
                }

                if($predicate[0] == '?') {
                    $predicate = $this->variable($predicate);
                    $variables[] = $predicate['text'];
                    $this->updateTypemap($typemap, $predicate['text'], 'string');
                } else {
                    $predicate = $this->literal($predicate);
                }

                if($object[0] == '?') {
                    $object = $this->variable($object);
                    $variables[] = $object['text'];
                    $this->updateTypemap($typemap, $object['text'], $type, $hint);
                } else {
                    if(!$type) $type = $this->_types->getConf('default_type');
                    $type = $this->_types->loadType($type);
                    $object = $this->literal($type->normalize($object,$hint));
                }

                $block[] = array('type'=>'triple','subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object);

            } elseif(in_array($blockid, array('where','optional','minus')) &&
                     preg_match('/^(?:\?([a-zA-Z0-9]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*(!=|>=|<=|>|<|=|!~|\^~|\$~|~)\s*(.+?)\s*$/S',$line, $match)) {
                // filter pattern
                list($_, $lhs,$type,$hint,$operator,$rhs) = $match;

                $lhs = $this->variable($lhs);

                if($rhs[0] == '?') {
                    $rhs = $this->variable($rhs);
                    $variables[] = $rhs['text'];
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

        $variables = array_unique($variables);

        return array($result, $variables);
    }

    /**
     * Parses a projection group in 'long syntax'.
     */
    function parseFieldsLong($lines, &$typemap) {
        $result = array();

        foreach($lines as $line) {
            $line = trim($line);
            if($this->ignorableLine($line)) {
                continue;
            } elseif(preg_match('/^(?:([^_]*)(?:_([a-z0-9]*)(?:\(([^)]+)\))?)?(:))?\s*\?([a-zA-Z0-9]+)$/S',$line, $match)) {
                list($_, $caption, $type, $hint, $nocaphint, $variable) = $match;
                if(!$nocaphint || (!$nocaphint && !$caption && !$type)) $caption = ucfirst($variable);
                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[$variable] = array('caption'=>$caption);
            } else {
                msg('Strata basic: Weird line \'<code>'.hsc($line).'</code>\' in \'<code>fields</code>\' group.', -1);
                return false;
            }
        }

        return $result;
    }

    /**
     * Parses a projection group in 'short syntax'.
     */
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

    /**
     * Returns the regex pattern used by the 'short syntax' for projection. This methods can
     * be used to get a dokuwiki-lexer-safe regex to embed into your own syntax pattern.
     */
    function fieldsShortPattern() {
        return '(?:\s+\?[a-zA-Z0-9]+(?:\s*\([^_\)]*(?:_[a-z0-9]*(?:\([^\)]*\))?)?\))?)';
    }
}
