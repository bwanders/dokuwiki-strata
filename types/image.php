<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * The image type.
 */
class plugin_strata_type_image extends plugin_strata_type {
    function normalize($value, $hint) {
        global $ID;

        // resolve page id with respect to selected base
        resolve_mediaid(getNS($ID),$value,$exists);

        return $value;
    }

    function render($mode, &$R, &$T, $value, $hint) {
        if($hint==null) {
            $width = null;
        } else {
            $width = (int)$hint;
        }

        // render internal media
        // (':' is prepended to make sure we use an absolute pagename,
        // internalmedia resolves media ids, but the name is already resolved.)
        $R->internalmedia(':'.$value,null,null,$width);
    }

    function getInfo() {
        return array(
            'desc'=>'Displays an image. The optional hint is treated as the width to scale the image to.',
            'hint'=>'width to scale the image to'
        );
    }
}
