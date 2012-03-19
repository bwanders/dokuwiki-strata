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
 * List syntax for basic query handling.
 */
class syntax_plugin_stratabasic_list extends syntax_plugin_stratabasic_select {
    function syntax_plugin_stratabasic_select() {
        parent::__construct();
   }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<list'.$this->helper->fieldsShortPattern().'* *>\n.+?\n</list>',$mode, 'plugin_stratabasic_list');
    }

    function handleHeader($header, &$result, &$typemap) {
        return preg_replace('/(^<list)|( *>$)/','',$header);
    }

    function render($mode, &$R, $data) {
        if($data == array()) {
            return;
        }

        // execute the query
        $result = $this->triples->queryRelations($data['query']);

        if($result == false) {
            return;
        }
    
        // prepare all 'columns'
        $fields = array();
        foreach($data['fields'] as $field=>$meta) {
            $fields[] = array(
                'name'=>$field,
                'type'=>$this->types->loadType($meta['type']),
                'hint'=>$meta['hint']
            );
        }


        if($mode == 'xhtml') {
            // render header
            $R->listu_open();

            // render each row
            foreach($result as $row) {
                $R->listitem_open(1);
                $R->listcontent_open();
                foreach($fields as $f) {
                    if($row[$f['name']] != null) {
                        $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                        $R->doc .= ' ';
                    }
                }
                $R->listcontent_close();
                $R->listitem_close();
            }
            $result->closeCursor();

            $R->listu_close();

            return true;
        } elseif($mode == 'metadata') {
            // render all rows in metadata mode to enable things like backlinks
            foreach($result as $row) {
                foreach($fields as $f) {
                    if($row[$f['name']] != null) {
                        $f['type']->render($mode, $R, $this->triples, $row[$f['name']], $f['hint']);
                    }
                }
            }
            $result->closeCursor();

            return true;
        }

        return false;
    }
}
