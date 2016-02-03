<?php
/**
 * Strata Basic, data entry plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if (!defined('DOKU_INC')) die('Meh.');

/**
 * List syntax for basic query handling.
 */
class syntax_plugin_strata_list extends syntax_plugin_strata_select {
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<list'.$this->helper->fieldsShortPattern().'* *>\s*?\n.+?\n\s*?</list>',$mode, 'plugin_strata_list');
    }

    function handleHeader($header, &$result, &$typemap) {
        return preg_replace('/(^<list)|( *>$)/','',$header);
    }

    function render($mode, Doku_Renderer $R, $data) {
        if($data == array() || isset($data['error'])) {
            if($mode == 'xhtml') {
                $R->listu_open();
                $R->listitem_open(1);
                $R->listcontent_open();
                $this->displayError($R, $data);
                $R->listcontent_close();
                $R->listitem_close();
                $R->listu_close();
            }

            return;
        }

        $query = $this->prepareQuery($data['query']);

        // execute the query
        $result = $this->triples->queryRelations($query);

        if($result == false) {
            if($mode == 'xhtml') {
                $R->listu_open();
                $R->listitem_open(1);
                $R->listcontent_open();
                $R->emphasis_open();
                $R->doc .= $R->_xmlEntities(sprintf($this->helper->getLang('content_error_explanation'),'Strata list'));
                $R->emphasis_close();
                $R->listcontent_close();
                $R->listitem_close();
                $R->listu_close();
            }


            return;
        }
    
        // prepare all 'columns'
        $fields = array();
        foreach($data['fields'] as $meta) {
            $fields[] = array(
                'variable'=>$meta['variable'],
                'caption'=>$meta['caption'],
                'type'=>$this->util->loadType($meta['type']),
                'typeName'=>$meta['type'],
                'hint'=>$meta['hint'],
                'aggregate'=>$this->util->loadAggregate($meta['aggregate']),
                'aggergateHint'=>$meta['aggregateHint']
            );
        }


        if($mode == 'xhtml') {
            // render header
            $this->ui_container_open($mode, $R, $data, array('strata-container', 'strata-container-list'));

            $this->util->renderCaptions($mode, $R, $fields);

            $R->listu_open();

            // render each row
            $itemcount = 0;
            foreach($result as $row) {
                $R->doc .= '<li class="level1 strata-item" data-strata-order="'.($itemcount++).'">'.DOKU_LF;
                $R->listcontent_open();

                $fieldCount = 0;

                foreach($fields as $f) {
                    $values = $f['aggregate']->aggregate($row[$f['variable']], $f['aggregateHint']);
                    if(!count($values)) continue;
                    if($fieldCount>1) $R->doc .= '; ';
                    if($fieldCount==1) $R->doc .= ' (';
                    $this->util->renderField($mode, $R, $this->triples, $values, $f['typeName'], $f['hint'], $f['type'], $f['variable']);
                    $fieldCount++;
                }

                if($fieldCount>1) $R->doc .= ')';

                $R->listcontent_close();
                $R->doc.= '</li>'.DOKU_LF;
            }
            $result->closeCursor();

            $R->listu_close();
            $this->ui_container_close($mode, $R);

            return true;
        } elseif($mode == 'metadata') {
            // render all rows in metadata mode to enable things like backlinks
            foreach($result as $row) {
                foreach($fields as $f) {
                    $this->util->renderField($mode, $R, $this->triples, $f['aggregate']->aggregate($row[$f['variable']],$f['aggregateHint']), $f['typeName'], $f['hint'], $f['type'], $f['variable']);
                }
            }
            $result->closeCursor();

            return true;
        }

        return false;
    }
}
