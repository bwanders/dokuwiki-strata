<?php
/**
 * DokuWiki Plugin strata (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Simple plugin to list the available types. This plugin uses
 * the same syntax as the info plugin, but only accepts a specific
 * info category.
 */
class syntax_plugin_strata_info extends DokuWiki_Syntax_Plugin {
    public function __construct() {
        $this->util =& plugin_load('helper', 'strata_util');
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        // sort just before info plugin
        return 154;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~INFO:stratatypes~~',$mode,'plugin_strata_info');
        $this->Lexer->addSpecialPattern('~~INFO:strataaggregates~~',$mode,'plugin_strata_info');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler){
        $data = array();
        preg_match('/~~INFO:strata(type|aggregate)s~~/',$match, $captures);
        list(,$kind) = $captures;

        // get a list of all types...
        foreach(glob(DOKU_PLUGIN."*/${kind}s/*.php") as $type) {
            if(preg_match("@/([^/]+)/${kind}s/([^/]+)\.php@",$type,$matches)) {
                // ...load each type...
                switch($kind) {
                    case 'type': $meta = $this->util->loadType($matches[2])->getInfo(); break;
                    case 'aggregate': $meta = $this->util->loadAggregate($matches[2])->getInfo(); break;
                }

                // ...and check if it's synthetic (i.e., not user-usable)
                if(!isset($meta['synthetic']) || !$meta['synthetic']) {
                    $data[] = array(
                        'name'=>$matches[2],
                        'plugin'=>$matches[1],
                        'meta'=>$meta
                    );
                }
            }
        }

        usort($data, array($this,'_compareNames'));

        return array($kind,$data);
    }

    function _compareNames($a, $b) {
        return strcmp($a['name'], $b['name']);
    }

    public function render($mode, Doku_Renderer $R, $data) {
        if($mode == 'xhtml') {
            list($kind, $items) = $data;

            $R->listu_open();
            foreach($items as $data){
                $R->listitem_open(1);
                $R->listcontent_open();

                $R->strong_open();
                $R->doc .= $data['name'];
                $R->strong_close();

                if($data['meta']['hint']) {
                    $R->doc .= ' ('.$kind.' hint: '.$R->_xmlEntities($data['meta']['hint']).')';
                }

                $R->linebreak();
                $R->doc .= $R->_xmlEntities($data['meta']['desc']);

                if(count($data['meta']['tags'])) {
                    $R->doc .=' (';
                    $R->emphasis_open();
                    $R->doc .= $R->_xmlEntities(hsc(implode(', ',$data['meta']['tags'])));
                    $R->emphasis_close();
                    $R->doc .= ')';
                }
                // $R->doc .= ' in '.$data['plugin'].' plugin';

                $R->listcontent_close();
                $R->listitem_close();
            }
            $R->listu_close();
            return true;
        }

        return false;
    }
}

