<?php
/**
 * Strata Basic, table plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
 
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Table syntax for basic query handling.
 */
class syntax_plugin_strata_table extends syntax_plugin_strata_select {
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<table'.$this->helper->fieldsShortPattern().'* *>\s*?\n.+?\n\s*?</table>',$mode, 'plugin_strata_table');
    }

    function getUISettingUI($hasUIBlock) {
        return array('choices' => array('none' => array('none', 'no', 'n'), 'generic' => array('generic', 'g'), 'table' => array('table', 't')), 'default' => 'table');
    }

    function handleHeader($header, &$result, &$typemap) {
        return preg_replace('/(^<table)|( *>$)/','',$header);
    }

    function render($mode, Doku_Renderer $R, $data) {
        if($data == array() || isset($data['error'])) {
            if($mode == 'xhtml') {
                $R->table_open();
                $R->tablerow_open();
                $R->tablecell_open();
                $this->displayError($R, $data);
                $R->tablecell_close();
                $R->tablerow_close();
                $R->table_close();
            }
            return;
        }

        $query = $this->prepareQuery($data['query']);

        // execute the query
        $result = $this->triples->queryRelations($query);

        // prepare all columns
        foreach($data['fields'] as $meta) {
            $fields[] = array(
                'variable'=>$meta['variable'],
                'caption'=>$meta['caption'],
                'type'=>$this->util->loadType($meta['type']),
                'typeName'=>$meta['type'],
                'hint'=>$meta['hint'],
                'aggregate'=>$this->util->loadAggregate($meta['aggregate']),
                'aggregateHint'=>$meta['aggregateHint']
            );
        }

        if($mode == 'xhtml') {
            // render header
            $this->ui_container_open($mode, $R, $data, array('strata-container', 'strata-container-table'));
            $R->table_open();
            $R->doc .= '<thead>'.DOKU_LF;
            $R->tablerow_open();

            // render all columns
            foreach($fields as $f) {
                $R->tableheader_open();
                $R->doc .= '<span class="strata-caption" data-field="'.hsc($f['variable']).'">';
                $R->doc .= $R->_xmlEntities($f['caption']);
                $R->doc .= '</span>'.DOKU_LF;
                $R->tableheader_close();
            }
            $R->tablerow_close();
            $R->doc .= '</thead>'.DOKU_LF;

            if($result != false) {
                // render each row
                $itemcount = 0;
                foreach($result as $row) {
                    $R->doc .= '<tbody class="strata-item" data-strata-order="'.($itemcount++).'">'.DOKU_LF;
                    $R->tablerow_open();
                    foreach($fields as $f) {
                        $R->tablecell_open();
                        $this->util->renderField($mode, $R, $this->triples, $f['aggregate']->aggregate($row[$f['variable']],$f['aggregateHint']), $f['typeName'], $f['hint'], $f['type'], $f['variable']);
                        $R->tablecell_close();
                    }
                    $R->tablerow_close();
                    $R->doc .= '</tbody>'.DOKU_LF;
                }
                $result->closeCursor();
            } else {
                $R->tablecell_open(count($fields));
                $R->emphasis_open();
                $R->doc .= $R->_xmlEntities(sprintf($this->helper->getLang('content_error_explanation'),'Strata table'));
                $R->emphasis_close();
                $R->tablecell_close();
            }

            $R->table_close();
            $this->ui_container_close($mode, $R);

            return true;
        } elseif($mode == 'metadata') {
            if($result == false) return;

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
