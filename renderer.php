<?php
/**
 * DokuWiki Plugin Strata (Metadata Preview Renderer Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

require_once DOKU_INC . 'inc/parser/metadata.php';

class renderer_plugin_strata extends Doku_Renderer_metadata {
    function getFormat() {
        return 'preview_metadata';
    }

    function document_start() {
        global $ID;
        if(!@file_exists(wikiFN($ID))) {
            $this->persistent['date']['created'] = time();
        }

        parent::document_start();
    }

    function document_end() {
        global $ID;
        $this->meta['date']['modified'] = time();
        parent::document_end();
    }
}
