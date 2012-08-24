<?php
/**
 * DokuWiki Plugin stratabasic (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

class action_plugin_stratabasic extends DokuWiki_Action_Plugin {

    /**
     * Register function called by DokuWiki to allow us
     * to register events we're interested in.
     *
     * @param controller object the controller to register with
     */
    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('PARSER_METADATA_RENDER', 'AFTER', $this, '_parser_metadata_render');
        $controller->register_hook('STRATASTORAGE_PREVIEW_METADATA_RENDER', 'AFTER', $this, '_parser_metadata_render');
    }

    /**
     * Triggered whenever metadata has been rendered.
     * We check the fixTitle flag, and if it is present, we
     * add the entry title.
     */
    public function _parser_metadata_render(&$event, $param) {
        $id = $event->data['page'];

        $current =& $event->data['current'];

        if(isset($current['stratabasic']['fixTitle']) && $current['stratabasic']['fixTitle']) {
            // get triples helper
            $triples =& plugin_load('helper', 'stratastorage_triples');

            $types =& plugin_load('helper', 'stratastorage_types');

            $helper =& plugin_load('helper', 'stratabasic');

            $titleKey = $helper->normalizePredicate($triples->getTitleKey());

            $title = $current['title'];
            if(!$title) {
                $title = noNS($id);
            }

            $type = $types->loadType('text');
            $title = $type->normalize($title,'');

            $triples->addTriple($id, $titleKey, $title, $id);
        }
    }
}
