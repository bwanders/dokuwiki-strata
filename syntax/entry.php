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
 * Data entry syntax for dedicated data blocks.
 */
class syntax_plugin_stratabasic_entry extends DokuWiki_Syntax_Plugin {
    function syntax_plugin_stratabasic_entry() {
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
        $this->Lexer->addSpecialPattern('<data(?: [_a-zA-Z0-9 ]+?)?(?: ?#[^>]*?)?>\n(?:.+?\n)*?</data>',$mode, 'plugin_stratabasic_entry');
    }

    function handle($match, $state, $pos, &$handler) {
        $result = array(
            'entry'=>'',
            'data'=>array()
        );

        $lines = explode("\n",$match);

        // extract header, and match it to get classes and fragment
        preg_match('/^<data( [_a-zA-Z0-9 ]+)?(?: ?#([^>]*?))?>/', array_shift($lines), $header);

        // process the classes into triples
        foreach(preg_split('/\s+/',trim($header[1])) as $class) {
            if($class == '') continue;
            $result['data'][] = array('key'=>'class','value'=>$class,'type'=>'text', 'hint'=>null);
        }

        // process the fragment if necessary
        $result['entry'] = $header[2];
        if($result['entry'] != '') {
            $result['data'][] = array('key'=>'title','value'=>$result['entry'], 'type'=>'text', 'hint'=>null);
        }

        // now handle all other lines
        foreach($lines as $line) {
            // abort if this is the closing line
            if($line == '</data>') break;

            // ignore line if it's a comment
            if($this->helper->ignorableLine($line)) continue;

            // match a "property_type(hint)*: value" pattern
            // (the * is only used to indicate that the value is actually a comma-seperated list)
            if(preg_match('/^([-a-zA-Z0-9 ]+)(?:_([a-z0-9]+)(?:\(([^)]+)\))?)?(\*)?\s*:(.*)$/',$line,$parts)) {
                // assign useful names
                list($match, $property, $type, $hint, $multi, $values) = $parts;

                // determine values, splitting on commas if necessary
                if($multi == '*') {
                    $values = array_map('trim',explode(',',$values));
                } else {
                    $values = array(trim($values));
                }

                // generate triples from the values
                foreach($values as $v) {
                    if($v == '') continue;
                    // replace the [[]] quasi-magic token with the empty string
                    if($v == '[[]]') $v = '';
                    if(!isset($type) || $type == '') {
                        $type = $this->types->getConf('default_type');
                    }
                    $result['data'][] = array('key'=>$property,'value'=>$v,'type'=>$type,'hint'=>($hint?:null));
                }
            } else {
                msg('I don\'t understand data entry line \'<code>'.htmlentities($line).'</code>\'.', -1);
            }
        }

        // normalize data
        foreach($result['data'] as &$triple) {
            $type = $this->types->loadType($triple['type']);
            $triple['value'] = $type->normalize($triple['value'], $triple['hint']);
        }

        return $result;
    }

    function render($mode, &$R, $data) {
        global $ID;


        if($mode == 'xhtml') {
            // group data by key (to support display of comma-separated list)
            $keys = array();
            foreach($data['data'] as $t) {
                if(!isset($keys[$t['key']])) $keys[$t['key']] = array();
                $keys[$t['key']][] = $t;
            }

            // render table header
            $R->table_open();
            $R->tablerow_open();
            $R->tableheader_open(2);

            // determine actual header text
            $heading = '';
            if(isset($keys['title'])) {
                // use title triple of possible
                $heading = $keys['title'][0]['value'];
            } elseif (useHeading('content')) {
                // fall back to page title, depending on wiki configuration
                $heading = p_get_first_heading($ID);
            } else {
                // use page id if all else fails
                $heading = noNS($ID);
            }
            $R->doc .= $R->_xmlEntities($heading);

            // display a comma-separated list of classes if the entry has classes
            if(isset($keys['class'])) {
                $R->emphasis_open();
                $R->doc .= ' (';
                $values = $keys['class'];
                for($i=0;$i<count($values);$i++) {
                    $triple =& $values[$i];
                    if($i!=0) $R->doc .= ', ';
                    $type = $this->types->loadType($triple['type']);
                    $type->render($mode, $R, $this->triples, $triple['value'], $triple['hint']);
                }
                $R->doc .= ')';
                $R->emphasis_close();
            }
            $R->tableheader_close();
            $R->tablerow_close();

            // render a row for each key, displaying the values as comma-separated list
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
                    $type = $this->types->loadType($triple['type']);
                    $type->render($mode, $R, $this->triples, $triple['value'], $triple['hint']);
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

            // resolve the subject to normalize everything
            resolve_pageid(getNS($ID),$subject,$exists);

            foreach($data['data'] as $triple) {
                // render values for things like backlinks
                $type = $this->types->loadType($triple['type']);
                $type->render($mode, $R, $this->triples, $triple['value'], $triple['hint']);

                // prepare triples for storage
                $triples[] = array('subject'=>$subject, 'predicate'=>$triple['key'], 'object'=>$triple['value']);
            }

            // batch-store triples
            $this->triples->addTriples($triples);
            return true;
        }

        return false;
    }
}
