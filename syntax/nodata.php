<?php
/**
 * DokuWiki Plugin stratastorage (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

/**
 * Simple plugin that sets the 'no data' flag.
 */
class syntax_plugin_stratastorage_nodata extends DokuWiki_Syntax_Plugin {
    public function __construct() {
    }

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        // sort at same level as notoc
        return 30;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~NODATA~~',$mode,'plugin_stratastorage_nodata');
    }

    public function handle($match, $state, $pos, &$handler){
        return array();
    }

    public function render($mode, &$R, $data) {
        if($mode == 'metadata') {
            $R->info['data'] = false;
            return true;
        }

        return false;
    }
}

