<?php
/**
 * DokuWiki Plugin stratastorage (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

/**
 * This action component exists to allow the definition of
 * the type autoloader.
 */
class action_plugin_stratastorage extends DokuWiki_Action_Plugin {

    /**
     * Register function called by DokuWiki to allow us
     * to register events we're interested in.
     *
     * @param controller object the controller to register with
     */
    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE', $this, '_remove_data');
    }

    /**
     * Removes all data for a page.
     * 
     * @param event array the event that triggers this hook
     */
    public function _remove_data(&$event, $param) {
        // get triples helper
        $triples =& plugin_load('helper', 'stratastorage_triples');
        $triples->initialize();

        // only remove triples if page is a new revision, or if it is removed
        if($event->data[3] == false || $event->data[0][1] == '') {
            $id = ltrim($event->data[1].':'.$event->data[2],':');
            $triples->removeTriples($id);
            $triples->removeTriples($id.'#%');
        }
    }

}

/**
 * Strata 'pluggable' autoloader. This function is responsible
 * for autoloading classes that should be pluggable by external
 * plugins.
 *
 * @param fullname string the name of the class to load
 */
function plugin_stratastorage_autoload($fullname) {
    // only load matching components
    if(!preg_match('/^plugin_strata_(type)_(.*)$/',$fullname, $matches)) {
        return false;
    }

    // use descriptive names
    $kind = $matches[1];
    $name = $matches[2];

    // load base class
    require_once("strata_{$kind}.php");

    // glob to find the required file
    $filenames = glob(DOKU_PLUGIN."*/{$kind}s/{$name}.php");

    if(count($filenames) == 0) {
        // if we have no file, fake an implementation
        eval("class $fullname extends plugin_strata_{$kind} { };");
    } else {
        // include the file
        require_once $filenames[0];
    }

    return true;
}

// register autoloader with SPL loader stack
spl_autoload_register('plugin_stratastorage_autoload');

