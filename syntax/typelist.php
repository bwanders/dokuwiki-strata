<?php
/**
 * DokuWiki Plugin stratastorage (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

/**
 * Simple plugin to list the available types. This plugin uses
 * the same syntax as the info plugin, but only accepts a specific
 * info category.
 */
class syntax_plugin_stratastorage_typelist extends DokuWiki_Syntax_Plugin {
    public function syntax_plugin_stratastorage_typelist() {
        $this->_types =& plugin_load('helper', 'stratastorage_types');
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
        $this->Lexer->addSpecialPattern('~~INFO:stratatypes~~',$mode,'plugin_stratastorage_typelist');
    }

    public function handle($match, $state, $pos, &$handler){
        $data = array();

        // get a list of all types...
        foreach(glob(DOKU_PLUGIN."*/types/*.php") as $type) {
            if(preg_match('@/([^/]+)/types/([^/]+)\.php@',$type,$matches)) {
                // ...load each type...
                $meta = $this->_types->loadType($matches[2])->getInfo();

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

        usort($data, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $data;
    }

    public function render($mode, &$R, $data) {
        if($mode == 'xhtml') {
            // render a list of types. Each type lists it's name, source plugin
            // and a short description of the type.
            $R->listu_open();
            foreach($data as $data){
                $R->listitem_open(1);
                $R->listcontent_open();
                $R->strong_open();
                $R->doc .= $data['name'];
                $R->strong_close();
                $R->doc .=' (in '.$data['plugin'].' plugin)';
                $R->linebreak();
                $R->doc .= $R->_xmlEntities($data['meta']['desc']);
                $R->listcontent_close();
                $R->listitem_close();
            }
            $R->listu_close();
            return true;
        }

        return false;
    }
}

