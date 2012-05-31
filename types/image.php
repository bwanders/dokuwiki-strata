<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The image type.
 */
class plugin_strata_type_image extends plugin_strata_type {
    function isExternalMedia($value) {
        return preg_match('#^(https?|ftp)#i', $value);
    }

    function normalize($value, $hint) {
        global $ID;

        // strip leading {{ and closing }}
        $value = preg_replace(array('/^\{\{/','/\}\}$/u'), '', $value);

        // drop any title and alignment spacing whitespace
        $value = explode('|', $value, 2);
        $value = trim($value[0]);

        if($this->isExternalMedia($value)) {
            // external image
            // we don't do anything else here
        } else {
            // internal image

            // discard size string and other options
            $pos = strrpos($value, '?');
            if($pos !== false ) {
                $value = substr($value, 0, $pos);
            }

            // resolve page id with respect to selected base
            resolve_mediaid(getNS($ID),$value,$exists);
        }

        return $value;
    }

    function render($mode, &$R, &$T, $value, $hint) {
        if(preg_match('/([0-9]+)(?:x([0-9]+))?/',$hint,$captures)) {
            if(!empty($captures[1])) $width = (int)$captures[1];
            if(!empty($captures[2])) $height = (int)$captures[2];
        }

        if($this->isExternalMedia($value)) {
            // render external media
            $R->externalmedia($value,null,null,$width,$height);
        } else {
            // render internal media
            // (':' is prepended to make sure we use an absolute pagename,
            // internalmedia resolves media ids, but the name is already resolved.)
            $R->internalmedia(':'.$value,null,null,$width,$height);
        }
    }

    function getInfo() {
        return array(
            'desc'=>'Displays an image. The optional hint is treated as the size to scale the image to. Give the hint in WIDTHxHEIGHT format.',
            'hint'=>'size to scale the image to'
        );
    }
}
