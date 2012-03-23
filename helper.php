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

if (!defined('STRATABASIC_PREDICATE')) define('STRATABASIC_PREDICATE','[^_:\(\)\[\]\{\}\<\>\|\~\!\@\#\$\%\^\&\*\?\=]+');
if (!defined('STRATABASIC_VARIABLE')) define('STRATABASIC_VARIABLE','[^ _:\(\)\[\]\{\}\<\>\|\~\!\@\#\$\%\^\&\*\?\=]+');

/**
 * Helper plugin for common syntax parsing.
 */
class helper_plugin_stratabasic extends DokuWiki_Plugin {
    function helper_plugin_stratabasic() {
        $this->types =& plugin_load('helper', 'stratastorage_types');
    }

    /**
     * Normalizes a predicate.
     */
    function normalizePredicate($p) {
        list($type, $hint) = $this->types->getPredicateType();
        return $this->types->loadType($type)->normalize($p, $hint);
    }

    /**
     * Renders a predicate.
     */
    function renderPredicate($mode, &$R, &$T, $p) {
        list($type, $hint) = $this->types->getPredicateType();
        return $this->types->loadType($type)->render($mode, $R, $T, $p, $hint);
    }

    /**
     * Determines whether a line can be ignored.
     */
    function ignorableLine($line) {
        $line = utf8_trim($line);
        return $line == '' || utf8_substr($line,0,2) == '--';
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

    function _fail($message) {
        msg($message,-1);
        throw new Exception();
    }

    /**
     * Constructs a query from the give tree.
     *
     * @param root array the tree to transform
     * @param typemap array the type information collected so far
     * @param projection array the variables to project
     * @return a query structure
     */
    function constructQuery(&$root, &$typemap, $projection) {
        try {
            $result = array(
                'type'=>'select',
                'group'=>array(),
                'projection'=>$projection,
                'ordering'=>array()
            );
    
            // extract sort groups
            $ordering = $this->extractGroups($root, 'sort');
    
            // transform actual group
            $where = $this->extractGroups($root, 'where');
            $tree = null;
            if(count($where)==0) {
                $tree =& $root;
            } elseif(count($where)==1) {
                $tree =& $where[0];
                if(count($root['cs'])) {
                    $this->_fail('Strata basic: I don\'t know what to do with things outside of the <code>where</code> group.');
                }
            } else {
                $this->_fail('Strata basic: A query should contain at most a single <code>where</code> group.');
            }

            list($group, $scope) = $this->transformGroup($tree, $typemap);
            $result['group'] = $group;
            if(!$group) return false;
    
            // handle sort groups
            if(count($ordering)) {
                if(count($ordering) > 1) {
                    $this->_fail('Strata basic: I don\'t know what to do with multiple <code>sort</code> groups.',-1);
                }
   
                // handle each line in the group 
                foreach($ordering[0]['cs'] as $line) {
                    if(is_array($line)) {
                        $this->_fail('Strata basic: I can\'t handle groups in a <code>sort</code> group.',-1);
                    }
    
                    if(preg_match('/^\?('.STRATABASIC_VARIABLE.')\s*(?:\((asc|desc)(?:ending)?\))?$/S',utf8_trim($line),$match)) {
                        if(!in_array($match[1], $scope)) {
                            $this->_fail('Strata basic: <code>sort</code> group uses out-of-scope variable \'<code>'.utf8_tohtml(hsc($match[1])).'</code>\'.',-1);
                        }
    
                        $result['ordering'][] = array('variable'=>$match[1], 'direction'=>($match[2]?:'asc'));
                    } else {
                        $this->_fail('Strata basic: I can\'t handle line \'<code>'.utf8_tohtml(hsc($line)).'</code>\' in the <code>sort</code> group.',-1);
                    }
                }
            }
    
            foreach($projection as $var) {
                if(!in_array($var, $scope)) {
                    $this->_fail('Strata basic: selected variable \'<code>'.utf8_tohtml(hsc($var)).'</code>\' is out-of-scope.',-1);
                }
            }
    
            // return final query structure
            return array($result, $scope);
        } catch(Exception $e) {
            // we failed somewhere in the transformation
            return false;
        }
    }

    /**
     * Transforms a full query group.
     * 
     * @param root array the tree to transform
     * @param typemap array the type information
     * @return the transformed group and a list of in-scope variables
     */
    function transformGroup(&$root, &$typemap) {
        // extract patterns and split them in triples and filters
        $patterns = $this->extractText($root);

        // extract union groups
        $unions = $this->extractGroups($root, 'union');

        // extract minus groups
        $minuses = $this->extractGroups($root,'minus');

        // extract optional groups
        $optionals = $this->extractGroups($root,'optional');

        // check for leftovers
        if(count($root['cs'])) {
            $this->_fail('Strata basic: Invalid '.( isset($root['cs'][0]['tag']) ? 'group \'<code>'.utf8_tohtml(hsc($root['cs'][0]['tag'])).'</code>\'' : 'unnamed group').' in query.',-1);
        }

        // split patterns into triples and filters
        list($patterns, $filters, $scope) = $this->transformPatterns($patterns, $typemap);

        // convert each union into a pattern
        foreach($unions as $union) {
            list($u, $s) = $this->transformUnion($union, $typemap);
            $scope = array_merge($scope, $s);
            $patterns[] = $u;
        }

        // chain all patterns with ANDs 
        $result = array_shift($patterns);
        foreach($patterns as $pattern) {
            $result = array(
                'type'=>'and',
                'lhs'=>$result,
                'rhs'=>$pattern
            );
        }

        // add all filters; these are a bit weird, as only a single FILTER is really supported
        // (we have defined multiple filters as being a conjunction)
        if(count($filters)) {
            foreach($filters as $f) {
                if($f['lhs']['type'] == 'variable' && !in_array($f['lhs']['text'], $scope)) {
                    $this->_fail('Strata basic: filter uses out-of-scope variable \'<code>'.utf8_tohtml(hsc($f['lhs']['text'])).'<code>\'.');
                }
                if($f['rhs']['type'] == 'variable' && !in_array($f['rhs']['text'], $scope)) {
                    $this->_fail('Strata basic: filter uses out-of-scope variable \'<code>'.utf8_tohtml(hsc($f['rhs']['text'])).'</code>\'.');
                }
            }

            $result = array(
                'type'=>'filter',
                'lhs'=>$result,
                'rhs'=>$filters
            );
        }

        // apply all minuses
        if(count($minuses)) {
            foreach($minuses as $minus) {
                // convert each minus, and discard their scope
                list($minus, $s) = $this->transformGroup($minus, $typemap);
                $result = array(
                    'type'=>'minus',
                    'lhs'=>$result,
                    'rhs'=>$minus
                );
            }
        }

        // apply all optionals
        if(count($optionals)) {
            foreach($optionals as $optional) {
                // convert eacfh optional
                list($optional, $s) = $this->transformGroup($optional, $typemap);
                $scope = array_merge($scope, $s);
                $result = array(
                    'type'=>'optional',
                    'lhs'=>$result,
                    'rhs'=>$optional
                );
            }
        }

        return array($result, $scope);
    }

    /**
     * Transforms a union group with multiple subgroups
     * 
     * @param root array the union group to transform
     * @param typemap array the type information
     * @return the transformed group and a list of in-scope variables
     */
    function transformUnion(&$root, &$typemap) {
        // fetch all child patterns
        $subs = $this->extractGroups($root,null);

        // do sanity checks
        if(count($root['cs'])) {
            $this->_fail('Strata basic: Lines or named groups inside a <code>union</code> group; I can only handle unnamed groups inside a <code>union</code> group.',-1);
        }

        if(count($subs) < 2) {
            $this->_fail('Strata basic: I need at least 2 unnamed groups inside a <code>union</code> group.',-1);
        }

        // transform the first group
        list($result,$scope) = $this->transformGroup(array_shift($subs), $typemap);

        // transform each subsequent group
        foreach($subs as $sub) {
            list($rhs, $s) = $this->transformGroup($sub, $typemap);
            $scope = array_merge($scope, $s);
            $result = array(
                'type'=>'union',
                'lhs'=>$result,
                'rhs'=>$rhs
            );
        }

        return array($result, $scope);
    }

    /**
     * Transforms a list of patterns into a list of triples and a
     * list of filters.
     *
     * @param lines array a list of lines to transform
     * @param typemap array the type information
     * @return a list of triples, a list of filters and a list of in-scope variables
     */
    function transformPatterns(&$lines, &$typemap) {
        // we need this to resolve things
        global $ID;

        // result holders
        $scope = array();
        $triples = array();
        $filters = array();

        foreach($lines as $line) {
            $line = trim($line);

            if(preg_match('/^((?:\?'.STRATABASIC_VARIABLE.')|(?:\[\[[^]]*\]\]))\s+(?:((?:'.STRATABASIC_PREDICATE.')|(?:\?'.STRATABASIC_VARIABLE.'))(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*:\s*(.+?)\s*$/S',$line,$match)) {
                // triple pattern
                list($_, $subject, $predicate, $type, $hint, $object) = $match;

                if($subject[0] == '?') {
                    $subject = $this->variable($subject);
                    $scope[] = $subject['text'];
                    $this->updateTypemap($typemap, $subject['text'], 'ref');
                } else {
                    global $ID;
                    $subject = substr($subject,2,-2);
                    $subject = $this->types->loadType('ref')->normalize($subject,null);
                    $subject = $this->literal($subject);
                }

                if($predicate[0] == '?') {
                    $predicate = $this->variable($predicate);
                    $scope[] = $predicate['text'];
                    $this->updateTypemap($typemap, $predicate['text'], 'text');
                } else {
                    $predicate = $this->literal($this->normalizePredicate($predicate));
                }

                if($object[0] == '?') {
                    $object = $this->variable($object);
                    $scope[] = $object['text'];
                    $this->updateTypemap($typemap, $object['text'], $type, $hint);
                } else {
                    // check for empty string token
                    if($object == '[[]]') {
                        $object='';
                    }
                    if(!$type) {
                        list($type, $hint) = $this->types->getDefaultType();
                    }
                    $type = $this->types->loadType($type);
                    $object = $this->literal($type->normalize($object,$hint));
                }

                $triples[] = array('type'=>'triple','subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object);

            } elseif(preg_match('/^(?:\?('.STRATABASIC_VARIABLE.')(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*(!=|>=|<=|>|<|=|!~|!\^~|!\$~|\^~|\$~|~)\s*(?:\?('.STRATABASIC_VARIABLE.')(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*$/S',$line, $match)) {
                // var op var
                list(,$lhs, $ltype, $lhint, $operator, $rhs, $rtype, $rhint) = $match;

                $lhs = $this->variable($lhs);
                $rhs = $this->variable($rhs);

                // do type information propagation

                if($ltype) {
                    // left has a defined type, so update the map
                    $this->updateTypemap($typemap, $lhs['text'], $ltype, $lhint);

                    // and propagate to right if possible
                    if(!$rtype) {
                        $this->updateTypemap($typemap, $rhs['text'], $ltype, $lhint);
                    }
                }
                if($rtype) {
                    // right has a defined type, so update the map
                    $this->updateTypemap($typemap, $rhs['text'], $rtype, $rhint);

                    // and propagate to left if possible
                    if(!$ltype) {
                        $this->updateTypemap($typemap, $lhs['text'], $rtype, $rhint);
                    }
                }

                $filters[] = array('type'=>'filter', 'lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs);

            } elseif(preg_match('/^(?:\?('.STRATABASIC_VARIABLE.')(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*(!=|>=|<=|>|<|=|!~|!\^~|!\$~|\^~|\$~|~)\s*(.+?)\s*$/S',$line, $match)) {
                // var op lit

                // filter pattern
                list(, $lhs,$type,$hint,$operator,$rhs) = $match;

                $lhs = $this->variable($lhs);

                // update typemap if a type was defined
                if($type) {
                    $this->updateTypemap($typemap, $lhs['text'],$type,$hint);
                }

                // use the already declared type if no type was defined
                if(!$type) {
                    if(!empty($typemap[$lhs['text']])) {
                        extract($typemap[$lhs['text']]);
                    } else {
                        list($type, $hint) = $this->types->getDefaultType();
                    }
                }

                // check for empty string token
                if($rhs == '[[]]') {
                    $rhs = '';
                }

                $type = $this->types->loadType($type);
                $rhs = $this->literal($type->normalize($rhs,$hint));

                $filters[] = array('type'=>'filter','lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs);
            } elseif(preg_match('/^(.+?)\s*(!=|>=|<=|>|<|=|!~|!\^~|!\$~|\^~|\$~|~)\s*(?:\?('.STRATABASIC_VARIABLE.')(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?)\s*$/S',$line, $match)) {
                // lit op var

                // filter pattern
                list(, $lhs,$operator,$rhs,$type,$hint) = $match;

                $rhs = $this->variable($rhs);

                // update typemap if a type was defined
                if($type) {
                    $this->updateTypemap($typemap, $rhs['text'],$type,$hint);
                }

                // use the already declared type if no type was defined
                if(!$type) {
                    if(!empty($typemap[$rhs['text']])) {
                        extract($typemap[$rhs['text']]);
                    } else {
                        list($type, $hint) = $this->types->getDefaultType();
                    }
                }

                // check for empty string token
                if($lhs == '[[]]') {
                    $lhs = '';
                }

                $type = $this->types->loadType($type);
                $lhs = $this->literal($type->normalize($lhs,$hint));

                $filters[] = array('type'=>'filter','lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs);
            } else {
                // unknown lines are fail
                $this->_fail('Strata basic: Unknown triple pattern or filter \'<code>'.utf8_tohtml(hsc($line)).'</code>\'.',-1);
            }
        }

        return array($triples, $filters, $scope);
    }

    /**
     * Parses a projection group in 'long syntax'.
     */
    function parseFieldsLong($lines, &$typemap) {
        $result = array();

        foreach($lines as $line) {
            $line = trim($line);
            if(preg_match('/^(?:([^_]*)(?:_([a-z0-9]*)(?:\(([^)]+)\))?)?(:))?\s*\?('.STRATABASIC_VARIABLE.')$/S',$line, $match)) {
                list($_, $caption, $type, $hint, $nocaphint, $variable) = $match;
                if(!$nocaphint || (!$nocaphint && !$caption && !$type)) $caption = ucfirst($variable);
                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[$variable] = array('caption'=>$caption);
            } else {
                msg('Strata basic: Weird line \'<code>'.utf8_tohtml(hsc($line)).'</code>\' in \'<code>fields</code>\' group.', -1);
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

        if(preg_match_all('/\s*\?('.STRATABASIC_VARIABLE.')(?:\s*(\()([^_)]*)(?:_([a-z0-9]*)(?:\(([^)]*)\))?)?\))?/',$line,$match, PREG_SET_ORDER)) {
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
        return '(?:\s+\?'.STRATABASIC_VARIABLE.'(?:\s*\([^_\)]*(?:_[a-z0-9]*(?:\([^\)]*\))?)?\))?)';
    }

    /**
     * Constructs a tagged tree from the given list of lines.
     *
     * @return a tagged tree
     */
    function constructTree($lines) {
        $root = array(
            'tag'=>'',
            'cs'=>array()
        );

        $stack = array();
        $stack[] =& $root;
        $top = count($stack)-1;

        foreach($lines as $line) {
            if($this->ignorableLine($line)) continue;

            if(preg_match('/^([^\{]*) *{$/',utf8_trim($line),$match)) {
                list(, $tag) = $match;
                $tag = utf8_trim($tag);

                $stack[$top]['cs'][] = array(
                    'tag'=>$tag?:null,
                    'cs'=>array()
                );
                $stack[] =& $stack[$top]['cs'][count($stack[$top]['cs'])-1];
                $top = count($stack)-1;

            } elseif(preg_match('/^}$/',utf8_trim($line))) {
                array_pop($stack);
                $top = count($stack)-1;

            } else {
                $stack[$top]['cs'][] = $line;
            }
        }

        if(count($stack) != 1 || $stack[0] != $root) {
            msg('Strata basic: unmatched braces in syntax',-1);
        }

        return $root;
    }

    /**
     * Extract all occurences of tagged groups from the given tree.
     * This method does not remove the tagged groups from subtrees of
     * the given root.
     *
     * @param root array the tree to operate on
     * @param tag string the tag to remove
     * @return an array of groups
     */
    function extractGroups(&$root, $tag) {
        $result = array();
        foreach($root['cs'] as $i=>&$tree) {
            if($tree['tag'] == $tag || (($tag=='' || $tag==null) && $tree['tag'] == null) ) {
                $result[] =& $tree;
                array_splice($root['cs'],$i,1);
            }
        }
        return $result;
    }

    /**
     * Extracts all text elements from the given tree.
     * This method does not remove the text elements from subtrees
     * of the root.
     *
     * @param root array the tree to operate on
     * @return array an array of text elements
     */
    function extractText(&$root) {
        $result = array();
        foreach($root['cs'] as $i=>&$tree) {
            if(is_array($tree)) continue;
            $result[] =& $tree;
            array_splice($root['cs'],$i,1);
        }
        return $result;
    }
}
