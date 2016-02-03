<?php
/**
 * Strata, data entry plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */

if (!defined('DOKU_INC')) die('Meh.');
 
/**
 * Data entry syntax for dedicated data blocks.
 */
class syntax_plugin_strata_entry extends DokuWiki_Syntax_Plugin {
    protected static $previewMetadata = array();

    function __construct() {
        $this->syntax =& plugin_load('helper', 'strata_syntax');
        $this->util =& plugin_load('helper', 'strata_util');
        $this->triples =& plugin_load('helper', 'strata_triples');
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
        if($this->getConf('enable_entry')) {
            $this->Lexer->addSpecialPattern('<data(?: +[^#>]+?)?(?: *#[^>]*?)?>\s*?\n(?:.*?\n)*?\s*?</data>',$mode, 'plugin_strata_entry');
        }
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        $result = array(
            'entry'=>'',
            'data'=> array(
                $this->util->getIsaKey(false) => array(),
                $this->util->getTitleKey(false) => array()
            )
        );

        // allow for preprocessing by a subclass
        $match = $this->preprocess($match, $state, $pos, $handler, $result);

        $lines = explode("\n",$match);
        $header = trim(array_shift($lines));
        $footer = trim(array_pop($lines));


        // allow subclasses to mangle header
        $header = $this->handleHeader($header, $result);

        // extract header, and match it to get classes and fragment
        preg_match('/^( +[^#>]+)?(?: *#([^>]*?))?$/', $header, $header);

        // process the classes into triples
        foreach(preg_split('/\s+/',trim($header[1])) as $class) {
            if($class == '') continue;
            $result['data'][$this->util->getIsaKey(false)][] = array('value'=>$class,'type'=>'text', 'hint'=>null);
        }

        // process the fragment if necessary
        $result['entry'] = $header[2];
        $result['position'] = $pos;
        if($result['entry'] != '') {
            $result['title candidate'] = array('value'=>$result['entry'], 'type'=>'text', 'hint'=>null);
        }

        // parse tree
        $tree = $this->syntax->constructTree($lines,'data entry');

        // allow subclasses first pick in the tree
        $this->handleBody($tree, $result);
        
        // fetch all lines
        $lines = $this->syntax->extractText($tree);

        // sanity check
        if(count($tree['cs'])) {
            msg(sprintf($this->syntax->getLang('error_entry_block'), ($tree['cs'][0]['tag']?sprintf($this->syntax->getLang('named_group'),utf8_tohtml(hsc($tree['cs'][0]['tag']))):$this->syntax->getLang('unnamed_group')), utf8_tohtml(hsc($result['entry']))),-1);
            return array();
        }

        $p = $this->syntax->getPatterns();

        // now handle all lines
        foreach($lines as $line) {
            $line = $line['text'];
            // match a "property_type(hint)*: value" pattern
            // (the * is only used to indicate that the value is actually a comma-seperated list)
            // [grammar] ENTRY := PREDICATE TYPE? '*'? ':' ANY
            if(preg_match("/^({$p->predicate})\s*({$p->type})?\s*(\*)?\s*:\s*({$p->any}?)$/",$line,$parts)) {
                // assign useful names
                list(, $property, $ptype, $multi, $values) = $parts;
                list($type,$hint) = $p->type($ptype);

                // trim property so we don't get accidental 'name   ' keys
                $property = utf8_trim($property);

                // lazy create key bucket
                if(!isset($result['data'][$property])) {
                    $result['data'][$property] = array();
                }

                // determine values, splitting on commas if necessary
                $values = ($multi == '*') ? explode(',',$values) : array($values);

                // generate triples from the values
                foreach($values as $v) {
                    $v = utf8_trim($v);
                    if($v == '') continue;
                    // replace the [[]] quasi-magic token with the empty string
                    if($v == '[[]]') $v = '';
                    if(!isset($type) || $type == '') {
                        list($type, $hint) = $this->util->getDefaultType();
                    }
                    $result['data'][$property][] = array('value'=>$v,'type'=>$type,'hint'=>($hint?:null));
                }
            } else {
                msg(sprintf($this->syntax->getLang('error_entry_line'), utf8_tohtml(hsc($line))),-1);
            }
        }

        // normalize data:
        // - Normalize all values
        $buckets = $result['data'];
        $result['data'] = array();

        foreach($buckets as $property=>&$bucket) {
            // normalize the predicate
            $property = $this->util->normalizePredicate($property);

            // process all triples
            foreach($bucket as &$triple) {
                // normalize the value
                $type = $this->util->loadType($triple['type']);
                $triple['value'] = $type->normalize($triple['value'], $triple['hint']);

                // lazy create property bucket
                if(!isset($result['data'][$property])) {
                    $result['data'][$property] = array();
                }

                $result['data'][$property][] = $triple;
            }
        }

        
        // normalize title candidate
        if(!empty($result['title candidate'])) {
            $type = $this->util->loadType($result['title candidate']['type']);
            $result['title candidate']['value'] = $type->normalize($result['title candidate']['value'], $result['title candidate']['hint']);
        }

        $footer = $this->handleFooter($footer, $result);

        return $result;
    }

    /**
     * Handles the whole match. This method is called before any processing
     * is done by the actual class.
     * 
     * @param match string the complete match
     * @param state the parser state
     * @param pos the position in the source
     * @param the handler object
     * @param result array the result array passed to the render method
     * @return a preprocessed string
     */
    function preprocess($match, $state, $pos, &$handler, &$result) {
        return $match;
    }

    /**
     * Handles the header of the syntax. This method is called before
     * the header is handled.
     *
     * @param header string the complete header
     * @param result array the result array passed to the render method
     * @return a string containing the unhandled parts of the header
     */
    function handleHeader($header, &$result) {
        // remove prefix and suffix
        return preg_replace('/(^<data)|( *>$)/','',$header);
    }

    /**
     * Handles the body of the syntax. This method is called before any
     * of the body is handled.
     *
     * @param tree array the parsed tree
     * @param result array the result array passed to the render method
     */
    function handleBody(&$tree, &$result) {
    }

    /**
     * Handles the footer of the syntax. This method is called after the
     * data has been parsed and normalized.
     * 
     * @param footer string the footer string
     * @param result array the result array passed to the render method
     * @return a string containing the unhandled parts of the footer
     */
    function handleFooter($footer, &$result) {
        return '';
    }


    protected function getPositions($data) {
        global $ID;

        // determine positions of other data entries
        // (self::$previewMetadata is only filled if a preview_metadata was run)
        if(isset(self::$previewMetadata[$ID])) {
            $positions = self::$previewMetadata[$ID]['strata']['positions'];
        } else {
            $positions = p_get_metadata($ID, 'strata positions');
        }

        // only read positions if we have them
        if(is_array($positions) && isset($positions[$data['entry']])) {
            $positions = $positions[$data['entry']];
            $currentPosition = array_search($data['position'],$positions);
            $previousPosition = isset($positions[$currentPosition-1])?'data_fragment_'.$positions[$currentPosition-1]:null;
            $nextPosition = isset($positions[$currentPosition+1])?'data_fragment_'.$positions[$currentPosition+1]:null;
            $currentPosition = 'data_fragment_'.$positions[$currentPosition];
        }

        return array($currentPosition, $previousPosition, $nextPosition);
    }

    function render($mode, Doku_Renderer $R, $data) {
        global $ID;

        if($data == array()) {
            return false;
        }

        if($mode == 'xhtml') {
            list($currentPosition, $previousPosition, $nextPosition) = $this->getPositions($data);
            // render table header
            $R->doc .= '<div class="strata-entry" '.(isset($currentPosition)?'id="'.$currentPosition.'"':'').'>';
            $R->table_open();
            $R->tablerow_open();
            $R->tableheader_open(2);

            // determine actual header text
            $heading = '';
            if(isset($data['data'][$this->util->getTitleKey()])) {
                // use title triple if possible
                $heading = $data['data'][$this->util->getTitleKey()][0]['value'];
            } elseif (!empty($data['title candidate'])) {
                // use title candidate if possible
                $heading = $data['title candidate']['value'];
            } else {
                if(useHeading('content')) {
                    // fall back to page title, depending on wiki configuration
                    $heading = p_get_first_heading($ID);
                }

                if(!$heading) {
                    // use page id if all else fails
                    $heading = noNS($ID);
                }
            }
            $R->doc .= $R->_xmlEntities($heading);

            // display a comma-separated list of classes if the entry has classes
            if(isset($data['data'][$this->util->getIsaKey()])) {
                $R->emphasis_open();
                $R->doc .= ' (';
                $values = $data['data'][$this->util->getIsaKey()];
                $this->util->openField($mode, $R, $this->util->getIsaKey());
                for($i=0;$i<count($values);$i++) {
                    $triple =& $values[$i];
                    if($i!=0) $R->doc .= ', ';
                    $type = $this->util->loadType($triple['type']);
                    $this->util->renderValue($mode, $R, $this->triples, $triple['value'], $triple['type'], $type, $triple['hint']);
                }
                $this->util->closeField($mode, $R);
                $R->doc .= ')';
                $R->emphasis_close();
            }
            $R->tableheader_close();
            $R->tablerow_close();

            // render a row for each key, displaying the values as comma-separated list
            foreach($data['data'] as $key=>$values) {
                // skip isa and title keys
                if($key == $this->util->getTitleKey() || $key == $this->util->getIsaKey()) continue;
                
                // render row header
                $R->tablerow_open();
                $R->tableheader_open();
                $this->util->renderPredicate($mode, $R, $this->triples, $key);
                $R->tableheader_close();

                // render row content
                $R->tablecell_open();
                $this->util->openField($mode, $R, $key);
                for($i=0;$i<count($values);$i++) {
                    $triple =& $values[$i];
                    if($i!=0) $R->doc .= ', ';
                    $this->util->renderValue($mode, $R, $this->triples, $triple['value'], $triple['type'], $triple['hint']);
                }
                $this->util->closeField($mode, $R);
                $R->tablecell_close();
                $R->tablerow_close();
            }

            if($previousPosition || $nextPosition) {
                $R->tablerow_open();
                $R->tableheader_open(2);
                if($previousPosition) {
                    $R->doc .= '<span class="strata-data-fragment-link-previous">';
                    $R->locallink($previousPosition, $this->util->getLang('data_entry_previous'));
                    $R->doc .= '</span>';
                }
                $R->doc .= ' ';
                if($nextPosition) {
                    $R->doc .= '<span class="strata-data-fragment-link-next">';
                    $R->locallink($nextPosition, $this->util->getLang('data_entry_next'));
                    $R->doc .= '</span>';
                }
                $R->tableheader_close();
                $R->tablerow_close();
            }

            $R->table_close();
            $R->doc .= '</div>';
            
            return true;

        } elseif($mode == 'metadata' || $mode == 'preview_metadata') {
            $triples = array();
            $subject = $ID.'#'.$data['entry'];

            // resolve the subject to normalize everything
            resolve_pageid(getNS($ID),$subject,$exists);

            $titleKey = $this->util->getTitleKey();

            $fixTitle = false;

            // we only use the title determination if no explicit title was given
            if(empty($data['data'][$titleKey])) {
                if(!empty($data['title candidate'])) {
                    // we have a candidate from somewhere
                    $data['data'][$titleKey][] = $data['title candidate'];
                } else {
                    if(!empty($R->meta['title'])) {
                        // we do not have a candidate, so we use the page title
                        // (this is possible because fragments set the candidate)
                        $data['data'][$titleKey][] = array(
                            'value'=>$R->meta['title'],
                            'type'=>'text',
                            'hint'=>null
                        );
                    } else {
                        // we were added before the page title is known
                        // however, we do require a page title (iff we actually store data)
                        $fixTitle = true;
                    }
                }
            }

            // store positions information
            if($mode == 'preview_metadata') {
                self::$previewMetadata[$ID]['strata']['positions'][$data['entry']][] = $data['position'];
            } else {
                $R->meta['strata']['positions'][$data['entry']][] = $data['position'];
            }

            // process triples
            foreach($data['data'] as $property=>$bucket) {
                $this->util->renderPredicate($mode, $R, $this->triples, $property);

                foreach($bucket as $triple) {
                    // render values for things like backlinks
                    $type = $this->util->loadType($triple['type']);
                    $type->render($mode, $R, $this->triples, $triple['value'], $triple['hint']);

                    // prepare triples for storage
                    $triples[] = array('subject'=>$subject, 'predicate'=>$property, 'object'=>$triple['value']);
                }
            }

            // we're done if nodata is flagged.
            if(!isset($R->info['data']) || $R->info['data']==true) {
                // batch-store triples if we're allowed to store
                $this->triples->addTriples($triples, $ID);
                
                // set flag for title addendum
                if($fixTitle) {
                    $R->meta['strata']['fixTitle'] = true;
                }
            }

            return true;
        }

        return false;
    }
}
