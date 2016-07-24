<?php
/**
 * DokuWiki Plugin strata (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_INC')) die('meh.');

/**
 * Helper to construct and handle syntax fragments.
 */
class helper_plugin_strata_syntax_RegexHelper {
    /**
     * Regular expression fragment table. This is used for interpolation of
     * syntax patterns, and should be without captures. Do not assume any
     * specific delimiter.
     */
    var $regexFragments = array(
        'variable'  => '(?:\?[^\s:\(\)\[\]\{\}\<\>\|\~\!\@\#\$\%\^\&\*\?\="]+)',
        'predicate' => '(?:[^:\(\)\[\]\{\}\<\>\|\~\!\@\#\$\%\^\&\*\?\="]+)',
        'reflit'    => '(?:\[\[[^]]*\]\])',
        'type'      => '(?:\[\s*[a-z0-9]+\s*(?:::[^\]]*)?\])',
        'aggregate' => '(?:@\s*[a-z0-9]+(?:\([^\)]*\))?)',
        'operator'  => '(?:!=|>=|<=|>|<|=|!~>|!~|!\^~|!\$~|\^~|\$~|~>|~)',
        'any'       => '(?:.+?)'
    );

    /**
     * Patterns used to extract information from captured fragments. These patterns
     * are used with '/' as delimiter, and should contain at least one capture group.
     */
    var $regexCaptures = array(
        'variable'  => array('\?(.*)', array('name')),
        'aggregate' => array('@\s*([a-z0-9]+)(?:\(([^\)]*)\))?', array('aggregate','hint')),
        'type'      => array('\[\s*([a-z0-9]+)\s*(?:::([^\]]*))?\]', array('type', 'hint')),
        'reflit'    => array('\[\[(.*)\]\]',array('reference'))
    );

    /**
     * Grabs the syntax fragment.
     */
    function __get($name) {
        if(array_key_exists($name, $this->regexFragments)) {
            return $this->regexFragments[$name];
        } else {
            $trace = debug_backtrace();
            trigger_error("Undefined syntax fragment '$name' on {$trace[0]['file']}:{$trace[0]['line']}", E_USER_NOTICE);
        }
    }

    /**
     * Extracts information from a fragment, based on the type.
     */
    function __call($name, $arguments) {
        if(array_key_exists($name, $this->regexCaptures)) {
            list($pattern, $names) = $this->regexCaptures[$name];
            $result = preg_match("/^{$pattern}$/", $arguments[0], $match);
            if($result === 1) {
                array_shift($match);
                $shortest = min(count($names), count($match));
                return new helper_plugin_strata_syntax_RegexHelperCapture(array_combine(array_slice($names,0,$shortest), array_slice($match, 0, $shortest)));
            } else {
                return null;
            }
        } else {
            $trace = debug_backtrace();
            trigger_error("Undefined syntax capture '$name' on {$trace[0]['file']}:{$trace[0]['line']}", E_USER_NOTICE);
        }
    }
}

/**
 * A single capture. Used as a return value for the RegexHelper's
 * capture methods.
 */
class helper_plugin_strata_syntax_RegexHelperCapture implements ArrayAccess {
    function __construct($values) {
        $this->values = $values;
    }

    function __get($name) {
        if(array_key_exists($name, $this->values)) {
            return $this->values[$name];
        } else {
            return null;
        }
    }

    function offsetExists($offset) {
        // the index is valid iff:
        //   it is an existing field name
        //   it is a correct nummeric index (with 0 being the first name and count-1 the last)
        return isset($this->values[$offset]) || ($offset >= 0 && $offset < count($this->values));
    }

    function offsetGet($offset) {
        // return the correct offset
        if (isset($this->values[$offset])) {
            return $this->values[$offset];
        } else {
            // or try the numeric offsets
            if(is_numeric($offset) && $offset >= 0 && $offset < count($this->values)) {
                // translate numeric offset to key
                $keys = array_keys($this->values);
                return $this->values[$keys[intval($offset)]];
            } else {
                // offset unknown, return without value
                return;
            }
        }
    }
    
    function offsetSet($offset, $value) {
        // noop
        $trace = debug_backtrace();
        trigger_error("Syntax fragment fields are read-only on {$trace[0]['file']}:{$trace[0]['line']}", E_USER_NOTICE);
    }

    function offsetUnset($offset) {
        // noop
        $trace = debug_backtrace();
        trigger_error("Syntax fragment fields are read-only on {$trace[0]['file']}:{$trace[0]['line']}", E_USER_NOTICE);
    }
}

/**
 * Helper plugin for common syntax parsing.
 */
class helper_plugin_strata_syntax extends DokuWiki_Plugin {
    public static $patterns;

    /**
     * Static initializer called directly after class declaration.
     *
     * This static method exists because we want to keep the static $patterns
     * and its initialization close together.
     */
    static function initialize() {
        self::$patterns = new helper_plugin_strata_syntax_RegexHelper();
    }

    /**
     * Constructor.
     */
    function __construct() {
        $this->util =& plugin_load('helper', 'strata_util');
        $this->error = '';
        $this->regions = array();
    }

    /**
     * Returns an object describing the pattern fragments.
     */
    function getPatterns() {
        return self::$patterns;
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

    function _fail($message, $regions=array()) {
        msg($message,-1);

        if($this->isGroup($regions) || $this->isText($regions)) {
            $regions = array($regions);
        }

        $lines = array();
        foreach($regions as $r) $lines[] = array('start'=>$r['start'], 'end'=>$r['end']);
        throw new strata_exception($message, $lines);
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
        $p = $this->getPatterns();

        $result = array(
            'type'=>'select',
            'group'=>array(),
            'projection'=>$projection,
            'ordering'=>array(),
            'grouping'=>false,
            'considering'=>array()
        );

        // extract sort groups
        $ordering = $this->extractGroups($root, 'sort');

        // extract grouping groups
        $grouping = $this->extractGroups($root, 'group');

        // extract additional projection groups
        $considering = $this->extractGroups($root, 'consider');

        // transform actual group
        $where = $this->extractGroups($root, 'where');
        $tree = null;
        if(count($where)==0) {
            $tree =& $root;
        } elseif(count($where)==1) {
            $tree =& $where[0];
            if(count($root['cs'])) {
                $this->_fail($this->getLang('error_query_outofwhere'), $root['cs']);
            }
        } else {
            $this->_fail($this->getLang('error_query_singlewhere'), $where);
        }

        list($group, $scope) = $this->transformGroup($tree, $typemap);
        $result['group'] = $group;
        if(!$group) return false;

        // handle sort groups
        if(count($ordering)) {
            if(count($ordering) > 1) {
                $this->_fail($this->getLang('error_query_multisort'), $ordering);
            }
   
            // handle each line in the group 
            foreach($ordering[0]['cs'] as $line) {
                if($this->isGroup($line)) {
                    $this->_fail($this->getLang('error_query_sortblock'), $line);
                }

                if(preg_match("/^({$p->variable})\s*(?:\((asc|desc)(?:ending)?\))?$/S",utf8_trim($line['text']),$match)) {
                    $var = $p->variable($match[1]);
                    if(!in_array($var->name, $scope)) {
                        $this->_fail(sprintf($this->getLang('error_query_sortvar'),utf8_tohtml(hsc($var->name))), $line);
                    }

                    $result['ordering'][] = array('variable'=>$var->name, 'direction'=>($match[2]?:'asc'));
                } else {
                    $this->_fail(sprintf($this->getLang('error_query_sortline'), utf8_tohtml(hsc($line['text']))), $line);
                }
            }
        }

        //handle grouping
        if(count($grouping)) {
            if(count($grouping) > 1) {
                $this->_fail($this->getLang('error_query_multigrouping'), $grouping);
            }

            // we have a group, so we want grouping
            $result['grouping'] = array();

            foreach($grouping[0]['cs'] as $line) {
                if($this->isGroup($line)) {
                    $this->_fail($this->getLang('error_query_groupblock'), $line);
                }

                if(preg_match("/({$p->variable})$/",utf8_trim($line['text']),$match)) {
                    $var = $p->variable($match[1]);
                    if(!in_array($var->name, $scope)) {
                        $this->_fail(sprintf($this->getLang('error_query_groupvar'),utf8_tohtml(hsc($var->name))), $line);
                    }

                    $result['grouping'][] = $var->name;
                } else {
                    $this->_fail(sprintf($this->getLang('error_query_groupline'), utf8_tohtml(hsc($line['text']))), $line);
                }
            }
        }

        //handle considering
        if(count($considering)) {
            if(count($considering) > 1) {
                $this->_fail($this->getLang('error_query_multiconsidering'), $considering);
            }

            foreach($considering[0]['cs'] as $line) {
                if($this->isGroup($line)) {
                    $this->_fail($this->getLang('error_query_considerblock'), $line);
                }

                if(preg_match("/^({$p->variable})$/",utf8_trim($line['text']),$match)) {
                    $var = $p->variable($match[1]);
                    if(!in_array($var->name, $scope)) {
                        $this->_fail(sprintf($this->getLang('error_query_considervar'),utf8_tohtml(hsc($var->name))), $line);
                    }

                    $result['considering'][] = $var->name;
                } else {
                    $this->_fail(sprintf($this->getLang('error_query_considerline'), utf8_tohtml(hsc($line['text']))), $line);
                }
            }
        }

        foreach($projection as $var) {
            if(!in_array($var, $scope)) {
                $this->_fail(sprintf($this->getLang('error_query_selectvar'), utf8_tohtml(hsc($var))));
            }
        }

        // return final query structure
        return array($result, $scope);
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
            $this->_fail(sprintf($this->getLang('error_query_group'),( isset($root['cs'][0]['tag']) ? sprintf($this->getLang('named_group'), utf8_tohtml(hsc($root['cs'][0]['tag']))) : $this->getLang('unnamed_group'))), $root['cs']);
        }

        // split patterns into triples and filters
        list($patterns, $filters, $scope) = $this->transformPatterns($patterns, $typemap);

        // convert each union into a pattern
        foreach($unions as $union) {
            list($u, $s) = $this->transformUnion($union, $typemap);
            $scope = array_merge($scope, $s);
            $patterns[] = $u;
        }

        if(count($patterns) == 0) {
            $this->_fail(sprintf($this->getLang('error_query_grouppattern')), $root);
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


        // add all filters; these are a bit weird, as only a single FILTER is really supported
        // (we have defined multiple filters as being a conjunction)
        if(count($filters)) {
            foreach($filters as $f) {
                $line = $f['_line'];
                unset($f['_line']);
                if($f['lhs']['type'] == 'variable' && !in_array($f['lhs']['text'], $scope)) {
                    $this->_fail(sprintf($this->getLang('error_query_filterscope'),utf8_tohtml(hsc($f['lhs']['text']))), $line);
                }
                if($f['rhs']['type'] == 'variable' && !in_array($f['rhs']['text'], $scope)) {
                    $this->_fail(sprintf($this->getLang('error_query_filterscope'),utf8_tohtml(hsc($f['rhs']['text']))), $line);
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
            $this->_fail($this->getLang('error_query_unionblocks'), $root['cs']);
        }

        if(count($subs) < 2) {
            $this->_fail($this->getLang('error_query_unionreq'), $root);
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

        // we need patterns
        $p = $this->getPatterns();

        // result holders
        $scope = array();
        $triples = array();
        $filters = array();

        foreach($lines as $lineNode) {
            $line = trim($lineNode['text']);

            // [grammar] TRIPLEPATTERN := (VARIABLE|REFLIT) ' ' (VARIABLE|PREDICATE) TYPE? : ANY
            if(preg_match("/^({$p->variable}|{$p->reflit})\s+({$p->variable}|{$p->predicate})\s*({$p->type})?\s*:\s*({$p->any})$/S",$line,$match)) {
                list(, $subject, $predicate, $type, $object) = $match;

                $subject = utf8_trim($subject);
                if($subject[0] == '?') {
                    $subject = $this->variable($subject);
                    $scope[] = $subject['text'];
                    $this->updateTypemap($typemap, $subject['text'], 'ref');
                } else {
                    global $ID;
                    $subject = $p->reflit($subject)->reference;
                    $subject = $this->util->loadType('ref')->normalize($subject,null);
                    $subject = $this->literal($subject);
                }

                $predicate = utf8_trim($predicate);
                if($predicate[0] == '?') {
                    $predicate = $this->variable($predicate);
                    $scope[] = $predicate['text'];
                    $this->updateTypemap($typemap, $predicate['text'], 'text');
                } else {
                    $predicate = $this->literal($this->util->normalizePredicate($predicate));
                }

                $object = utf8_trim($object);
                if($object[0] == '?') {
                    // match a proper type variable
                    if(preg_match("/^({$p->variable})\s*({$p->type})?$/",$object,$captures)!=1) {
                        $this->_fail($this->getLang('error_pattern_garbage'),$lineNode);
                    }
                    list(, $var, $vtype) = $captures;

                    // create the object node
                    $object = $this->variable($var);
                    $scope[] = $object['text'];

                    // try direct type first, implied type second
                    $vtype = $p->type($vtype);
                    $type = $p->type($type);
                    $this->updateTypemap($typemap, $object['text'], $vtype->type, $vtype->hint);
                    $this->updateTypemap($typemap, $object['text'], $type->type, $type->hint);
                } else {
                    // check for empty string token
                    if($object == '[[]]') {
                        $object='';
                    }
                    if(!$type) {
                        list($type, $hint) = $this->util->getDefaultType();
                    } else {
                        $type = $p->type($type);
                        $hint = $type->hint;
                        $type = $type->type;
                    }
                    $type = $this->util->loadType($type);
                    $object = $this->literal($type->normalize($object,$hint));
                }

                $triples[] = array('type'=>'triple','subject'=>$subject, 'predicate'=>$predicate, 'object'=>$object);

            // [grammar] FILTER := VARIABLE TYPE? OPERATOR VARIABLE TYPE?
            } elseif(preg_match("/^({$p->variable})\s*({$p->type})?\s*({$p->operator})\s*({$p->variable})\s*({$p->type})?$/S",$line, $match)) {
                list(,$lhs, $ltype, $operator, $rhs, $rtype) = $match;

                $lhs = $this->variable($lhs);
                $rhs = $this->variable($rhs);

                if($operator == '~>' || $operator == '!~>') $operator = str_replace('~>','^~',$operator);

                // do type information propagation
                $rtype = $p->type($rtype);
                $ltype = $p->type($ltype);

                if($ltype) {
                    // left has a defined type, so update the map
                    $this->updateTypemap($typemap, $lhs['text'], $ltype->type, $ltype->hint);

                    // and propagate to right if possible
                    if(!$rtype) {
                        $this->updateTypemap($typemap, $rhs['text'], $ltype->type, $lhint->hint);
                    }
                }
                if($rtype) {
                    // right has a defined type, so update the map
                    $this->updateTypemap($typemap, $rhs['text'], $rtype->type, $rtype->hint);

                    // and propagate to left if possible
                    if(!$ltype) {
                        $this->updateTypemap($typemap, $lhs['text'], $rtype->type, $rtype->hint);
                    }
                }

                $filters[] = array('type'=>'filter', 'lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs, '_line'=>$lineNode);

            // [grammar] FILTER := VARIABLE TYPE? OPERATOR ANY
            } elseif(preg_match("/^({$p->variable})\s*({$p->type})?\s*({$p->operator})\s*({$p->any})$/S",$line, $match)) {

                // filter pattern
                list(, $lhs,$ltype,$operator,$rhs) = $match;

                $lhs = $this->variable($lhs);

                // update typemap if a type was defined
                list($type,$hint) = $p->type($ltype);
                if($type) {
                    $this->updateTypemap($typemap, $lhs['text'],$type,$hint);
                } else {
                    // use the already declared type if no type was defined
                    if(!empty($typemap[$lhs['text']])) {
                        extract($typemap[$lhs['text']]);
                    } else {
                        list($type, $hint) = $this->util->getDefaultType();
                    }
                }

                // check for empty string token
                if($rhs == '[[]]') {
                    $rhs = '';
                }

                // special case: the right hand side of the 'in' operator always normalizes with the 'text' type
                if($operator == '~>' || $operator == '!~>') {
                    $operator = str_replace('~>','^~', $operator);
                    $type = 'text';
                    unset($hint);
                }

                // normalize
                $type = $this->util->loadType($type);
                $rhs = $this->literal($type->normalize($rhs,$hint));

                $filters[] = array('type'=>'filter','lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs, '_line'=>$lineNode);

            // [grammar] FILTER := ANY OPERATOR VARIABLE TYPE?
            } elseif(preg_match("/^({$p->any})\s*({$p->operator})\s*({$p->variable})\s*({$p->type})?$/S",$line, $match)) {
                list(, $lhs,$operator,$rhs,$rtype) = $match;

                $rhs = $this->variable($rhs);

                // update typemap if a type was defined
                list($type, $hint) = $p->type($rtype);
                if($type) {
                    $this->updateTypemap($typemap, $rhs['text'],$type,$hint);
                } else {
                    // use the already declared type if no type was defined
                    if(!empty($typemap[$rhs['text']])) {
                        extract($typemap[$rhs['text']]);
                    } else {
                        list($type, $hint) = $this->util->getDefaultType();
                    }
                }

                // check for empty string token
                if($lhs == '[[]]') {
                    $lhs = '';
                }

                // special case: the left hand side of the 'in' operator always normalizes with the 'page' type
                if($operator == '~>' || $operator == '!~>') {
                    $operator = str_replace('~>','^~', $operator);
                    $type = 'page';
                    unset($hint);
                }

                // normalize
                $type = $this->util->loadType($type);
                $lhs = $this->literal($type->normalize($lhs,$hint));

                $filters[] = array('type'=>'filter','lhs'=>$lhs, 'operator'=>$operator, 'rhs'=>$rhs, '_line'=>$lineNode);
            } else {
                // unknown lines are fail
                $this->_fail(sprintf($this->getLang('error_query_pattern'),utf8_tohtml(hsc($line))), $lineNode);
            }
        }

        return array($triples, $filters, $scope);
    }

    function getFields(&$tree, &$typemap) {
        $fields = array();

        // extract the projection information in 'long syntax' if available
        $fieldsGroups = $this->extractGroups($tree, 'fields');

        // parse 'long syntax' if we don't have projection information yet
        if(count($fieldsGroups)) {
            if(count($fieldsGroups) > 1) {
                $this->_fail($this->getLang('error_query_fieldsgroups'), $fieldsGroups);
            }

            $fieldsLines = $this->extractText($fieldsGroups[0]);
            if(count($fieldsGroups[0]['cs'])) {
                $this->_fail(sprintf($this->getLang('error_query_fieldsblock'),( isset($fieldsGroups[0]['cs'][0]['tag']) ? sprintf($this->getLang('named_group'),hsc($fieldsGroups[0]['cs'][0]['tag'])) : $this->getLang('unnamed_group'))), $fieldsGroups[0]['cs']);
            }
            $fields = $this->parseFieldsLong($fieldsLines, $typemap);
            if(!$fields) return array();
        }
    
        return $fields;
    }

    /**
     * Parses a projection group in 'long syntax'.
     */
    function parseFieldsLong($lines, &$typemap) {
        $p = $this->getPatterns();
        $result = array();

        foreach($lines as $lineNode) {
            $line = trim($lineNode['text']);
            // FIELDLONG := VARIABLE AGGREGATE? TYPE? (':' ANY)?
            if(preg_match("/^({$p->variable})\s*({$p->aggregate})?\s*({$p->type})?(?:\s*(:)\s*({$p->any})?\s*)?$/S",$line, $match)) {
                list(, $var, $vaggregate, $vtype, $nocaphint, $caption) = $match;
                $variable = $p->variable($var)->name;
                if(!$nocaphint || (!$nocaphint && !$caption)) $caption = ucfirst($variable);

                list($type,$hint) = $p->type($vtype);
                list($agg,$agghint) = $p->aggregate($vaggregate);

                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[] = array('variable'=>$variable,'caption'=>$caption, 'aggregate'=>$agg, 'aggregateHint'=>$agghint, 'type'=>$type, 'hint'=>$hint);
            } else {
                $this->_fail(sprintf($this->getLang('error_query_fieldsline'),utf8_tohtml(hsc($line))), $lineNode);
            }
        }

        return $result;
    }

    /**
     * Parses a projection group in 'short syntax'.
     */
    function parseFieldsShort($line, &$typemap) {
        $p = $this->getPatterns();
        $result = array();

        // FIELDSHORT := VARIABLE AGGREGATE? TYPE? CAPTION?
        if(preg_match_all("/\s*({$p->variable})\s*({$p->aggregate})?\s*({$p->type})?\s*(?:(\")([^\"]*)\")?/",$line,$match, PREG_SET_ORDER)) {
            foreach($match as $m) {
                list(, $var, $vaggregate, $vtype, $caption_indicator, $caption) = $m;
                $variable = $p->variable($var)->name;
                list($type, $hint) = $p->type($vtype);
                list($agg, $agghint) = $p->aggregate($vaggregate);
                if(!$caption_indicator) $caption = ucfirst($variable);
                $this->updateTypemap($typemap, $variable, $type, $hint);
                $result[] = array('variable'=>$variable,'caption'=>$caption, 'aggregate'=>$agg, 'aggregateHint'=>$agghint, 'type'=>$type, 'hint'=>$hint);
            }
        }

        return $result;
    }

    /**
     * Returns the regex pattern used by the 'short syntax' for projection. This methods can
     * be used to get a dokuwiki-lexer-safe regex to embed into your own syntax pattern.
     *
     * @param captions boolean Whether the pattern should include caption matching (defaults to true)
     */
    function fieldsShortPattern($captions = true) {
        $p = $this->getPatterns();
        return "(?:\s*{$p->variable}\s*{$p->aggregate}?\s*{$p->type}?".($captions?'\s*(?:"[^"]*")?':'').")";
    }

    /**
     * Constructs a tagged tree from the given list of lines.
     *
     * @return a tagged tree
     */
    function constructTree($lines, $what) {
        $root = array(
            'tag'=>'',
            'cs'=>array(),
            'start'=>1,
            'end'=>1
        );

        $stack = array();
        $stack[] =& $root;
        $top = count($stack)-1;
        $lineCount = 0;

        foreach($lines as $line) {
            $lineCount++;
            if($this->ignorableLine($line)) continue;

            if(preg_match('/^([^\{]*) *{$/',utf8_trim($line),$match)) {
                list(, $tag) = $match;
                $tag = utf8_trim($tag);

                $stack[$top]['cs'][] = array(
                    'tag'=>$tag?:null,
                    'cs'=>array(),
                    'start'=>$lineCount,
                    'end'=>0
                );
                $stack[] =& $stack[$top]['cs'][count($stack[$top]['cs'])-1];
                $top = count($stack)-1;

            } elseif(preg_match('/^}$/',utf8_trim($line))) {
                $stack[$top]['end'] = $lineCount;
                array_pop($stack);
                $top = count($stack)-1;

            } else {
                $stack[$top]['cs'][] = array(
                    'text'=>$line,
                    'start'=>$lineCount,
                    'end'=>$lineCount
                );
            }
        }

        if(count($stack) != 1 || $stack[0] != $root) {
            msg(sprintf($this->getLang('error_syntax_braces'),$what),-1);
        }

        $root['end'] = $lineCount;

        return $root;
    }

    /**
     * Renders a debug display of the syntax.
     *
     * @param lines array the lines that form the syntax
     * @param region array the region to highlight
     * @return a string with markup
     */
    function debugTree($lines, $regions) {
        $result = '';
        $lineCount = 0;
        $count = 0;

        foreach($lines as $line) {
            $lineCount++;

            foreach($regions as $region) {
                if($lineCount == $region['start']) {
                    if($count == 0) $result .= '<div class="strata-debug-highlight">';
                    $count++;
                }

                if($lineCount == $region['end']+1) {
                    $count--;

                    if($count==0) $result .= '</div>';
                }
            }

            if($line != '') {
                $result .= '<div class="strata-debug-line">'.hsc($line).'</div>'."\n";
            } else {
                $result .= '<div class="strata-debug-line"><br/></div>'."\n";
            }
        }

        if($count > 0) {
            $result .= '</div>';
        }

        return '<div class="strata-debug">'.$result.'</div>';
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
        $to_remove = array();
        foreach($root['cs'] as $i=>&$tree) {
            if(!$this->isGroup($tree)) continue;
            if($tree['tag'] == $tag || (($tag=='' || $tag==null) && $tree['tag'] == null) ) {
                $result[] =& $tree;
                $to_remove[] = $i;
            }
        }
        // invert order of to_remove to always remove higher indices first
        rsort($to_remove);
        foreach($to_remove as $i) {
            array_splice($root['cs'],$i,1);
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
        $to_remove = array();
        foreach($root['cs'] as $i=>&$tree) {
            if(!$this->isText($tree)) continue;
            $result[] =& $tree;
            $to_remove[] = $i;
        }
        // invert order of to_remove to always remove higher indices first
        rsort($to_remove);
        foreach($to_remove as $i) {
            array_splice($root['cs'],$i,1);
        }
        return $result;
    }

    /**
     * Returns whether the given node is a line.
     */
    function isText(&$node) {
        return array_key_exists('text', $node);
    }

    /**
     * Returns whether the given node is a group.
     */
    function isGroup(&$node) {
        return array_key_exists('tag', $node);
    }

    /**
     * Sets all properties given as '$properties' to the values parsed from '$trees'.
     *
     * The property array has as keys all possible properties, which are specified by its
     * values. Such specification is an array that may have the following keys, with the
     * described values:
     * - choices: array of possible values, where the keys are the internally used values
     *     and the values specify synonyms for the choice, of which the first listed one
     *     is most common. For example: 'true' => array('yes', 'yeah') specifies that the
     *     user can choose 'yes' or 'yeah' (of which 'yes' is the commonly used value) and
     *     that the return value will contain 'true' if this choice was chosen.
     * - pattern: regular expression that defines all possible values.
     * - pattern_desc: description used for errors when a pattern is specified.
     * - minOccur: positive integer specifying the minimum number of values, defaults to 1.
     * - maxOccur: integer greater than or equal to minOccur, which specifies the maximum
     *     number of values, defaults to minOccur.
     * - default: the default value (which must be a value the user is allowed to set).
     *     When default is given, this method guarantees that the property is always set,
     *     otherwise the property may not be set since all properties are optional.
     * Either 'choices' or 'pattern' must be set (not both), all other values are optional.
     *
     * An example property array is as follows:
     * array(
     *   'example boolean' => array(
     *     'choices' => array('y' => array('yes', 'yeah'), 'n' => array('no', 'nay')),
     *     'minOccur' => 1,
     *     'maxOccur' => 3,
     *     'default' => 'yes'
     *   ),
     *   'example natural number' => array(
     *     'pattern' => '/^[0-9]+$/',
     *     'pattern_desc' => $this->getLang('property_Z*')
     *   )
     * )
     *
     * @param $properties The properties that can be set.
     * @param $trees The trees that contain the values for these properties.
     * @return An array with as indices the property names and as value a list of all values given for that property.
     */
    function setProperties($properties, $trees) {
        $propertyValues = array();
        $p = $this->getPatterns();

        foreach ($trees as $tree) {
            $text = $this->extractText($tree);
            foreach($text as $lineNode) {
                $line = utf8_trim($lineNode['text']);
                if (preg_match('/^('.$p->predicate.')(\*)?\s*:\s*('.$p->any.')$/', $line, $match)) {
                    list(, $variable, $multi, $value) = $match;
                    $this->_setPropertyValue($properties, $tree['tag'], $lineNode, $variable, !empty($multi), $value, $propertyValues);
                } else {
                    $this->emitError($lineNode, 'error_property_weirdgroupline', hsc($tree['tag']), hsc($line));
                }
            }
            // Warn about unknown groups
            foreach ($tree['cs'] as $group) {
                $this->emitError($group, 'error_property_unknowngroup', hsc($trees[0]['tag']), hsc($group['tag']));
            }
        }

        // Set property defaults
        foreach ($properties as $name => $p) {
            if (!isset($propertyValues[$name]) && isset($p['default'])) {
                $this->_setPropertyValue($properties, 'default value', null, $name, false, $p['default'], $propertyValues);
            }
        }

        // Show errors, if any
        $this->showErrors();

        return $propertyValues;
    }

    function _setPropertyValue($properties, $group, $region, $variable, $isMulti, $value, &$propertyValues) {
        if (!isset($properties[$variable])) {
            // Unknown property: show error
            $property_title_values = $this->getLang('property_title_values');
            $propertyList = implode(array_map(function($n, $p) use($property_title_values) {
                $values = implode(array_map(function($c) { return $c[0]; }, $p['choices']), ', ');
                $title = sprintf($property_title_values, $values);
                return '\'<code title="' . hsc($title) . '">' . hsc($n) . '</code>\'';
            }, array_keys($properties), $properties), ', ');
            $this->emitError($region, 'error_property_unknownproperty', hsc($group), hsc($variable), $propertyList);
        } else if (isset($propertyValues[$variable])) {
            // Property is specified more than once: show error
            $this->emitError($region, 'error_property_multi', hsc($group), hsc($variable));
        } else {
            $p = $properties[$variable];
            $minOccur = isset($p['minOccur']) ? $p['minOccur'] : 1;
            $maxOccur = isset($p['maxOccur']) ? $p['maxOccur'] : $minOccur;

            if ($isMulti) {
                $values = array_map('utf8_trim', split(',', $value));
            } else if ($minOccur == 1 || $minOccur == $maxOccur) {
                // Repeat the given value as often as we expect it
                $values = array_fill(0, $minOccur, $value);
            } else {
                // A single value was given, but multiple were expected
                $this->emitError($region, 'error_property_notmulti', hsc($group), hsc($variable), $minOccur);
                return;
            }

            if (count($values) < $minOccur || count($values) > $maxOccur) {
                // Number of values given differs from expected number
                if ($minOccur == $maxOccur) {
                    $this->emitError($region, 'error_property_occur', hsc($group), hsc($variable), $minOccur, count($values));
                } else {
                    $this->emitError($region, 'error_property_occurrange', hsc($group), hsc($variable), $minOccur, $maxOccur, count($values));
                }
            } else if (isset($p['choices'])) { // Check whether the given property values are valid choices
                // Create a mapping from choice to normalized value of the choice
                $choices = array();
                $choicesInfo = array(); // For nice error messages
                foreach ($p['choices'] as $nc => $c) {
                    if (is_array($c)) {
                        $choices = array_merge($choices, array_fill_keys($c, $nc));
                        $title = sprintf($this->getLang('property_title_synonyms'), implode($c, ', '));
                        $choicesInfo[] = '\'<code title="' . hsc($title) . '">' . hsc($c[0]) . '</code>\'';
                    } else {
                        $choices[$c] = $c;
                        $choicesInfo[] = '\'<code>' . hsc($c) . '</code>\'';
                    }
                }
                if (!isset($choices['']) && isset($p['default'])) {
                    $choices[''] = $choices[$p['default']];
                }

                $incorrect = array_diff($values, array_keys($choices)); // Find all values that are not a valid choice
                if (count($incorrect) > 0) {
                    unset($choices['']);
                    foreach (array_unique($incorrect) as $v) {
                        $this->emitError($region, 'error_property_invalidchoice', hsc($group), hsc($variable), hsc($v), implode($choicesInfo, ', '));
                    }
                } else {
                    $propertyValues[$variable] = array_map(function($v) use ($choices) { return $choices[$v]; }, $values);
                }
            } else if (isset($p['pattern'])) { // Check whether the given property values match the pattern
                $incorrect = array_filter($values, function($v) use ($p) { return !preg_match($p['pattern'], $v); });
                if (count($incorrect) > 0) {
                    foreach (array_unique($incorrect) as $v) {
                        if (isset($p['pattern_desc'])) {
                            $this->emitError($region, 'error_property_patterndesc', hsc($group), hsc($variable), hsc($v), $p['pattern_desc']);
                        } else {
                            $this->emitError($region, 'error_property_pattern', hsc($group), hsc($variable), hsc($v), hsc($p['pattern']));
                        }
                    }
                } else {
                    $propertyValues[$variable] = $values;
                }
            } else { // Property value has no requirements
                $propertyValues[$variable] = $values;
            }
        }
    }

    /**
     * Generates a html error message, ensuring that all utf8 in arguments is escaped correctly.
     * The generated messages might be accumulated until showErrors is called.
     *
     * @param region The region at which the error occurs.
     * @param msg_id The id of the message in the language file.
     */
    function emitError($region, $msg_id) {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        $args = array_map('strval', $args); // convert everything to strings first
        $args = array_map('utf8_tohtml', $args); // Escape args
        $msg = vsprintf($this->getLang($msg_id), $args);
        msg($msg, -1);
        $this->error .= "<br />\n" . $msg;
        $this->regions[] = $region;
    }

    /**
     * Ensures that all emitted errors are shown.
     */
    function showErrors() {
        if (!empty($this->error)) {
            $error = $this->error;
            $regions = $this->regions;
            $this->error = '';
            $this->regions = array();
            throw new strata_exception($error, $regions);
        }
    }
}

// call static initiliazer (PHP doesn't offer this feature)
helper_plugin_strata_syntax::initialize();
