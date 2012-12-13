<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The multi-purpose link type.
 */
class plugin_strata_type_link extends plugin_strata_type {
    function render($mode, &$renderer, &$triples, $value, $hint) {
        if(preg_match('/^[a-zA-Z0-9\.]+>{1}.*$/u',$value)) {
            // Interwiki
            $interwiki = explode('>',$value,2);
            $renderer->interwikilink($value,$hint, strtolower($interwiki[0]), $interwiki[1]);

        } elseif(preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u',$value)) {
            $renderer->windowssharelink($value,$hint);

        } elseif(preg_match('#^([a-z0-9\-\.+]+?)://#i',$value)) {
            $renderer->externallink($value,$hint);

        } elseif(preg_match('<'.PREG_PATTERN_VALID_EMAIL.'>',$value)) {
            $renderer->emaillink($value,$hint);

        } else {
            $renderer->internallink(':'.$value, $hint);
        }

        return true;
    }

    function normalize($value, $hint) {
        // strip off leading [[ and trailing ]] to offer a more
        // user-friendly syntax.
        if(substr($value,0,2) == '[[' && substr($value,-2) == ']]') {
            $value = substr($value,2,-2);
        }

        if(!preg_match('/^[a-zA-Z0-9\.]+>{1}.*$/u',$value)
           && !preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u',$value)
           && !preg_match('#^([a-z0-9\-\.+]+?)://#i',$value)
           && !preg_match('<'.PREG_PATTERN_VALID_EMAIL.'>',$value)) {
            $page = new plugin_strata_type_page();
            return $page->normalize($value,null);
        }

        return $value;
    }
        
    function getInfo() {
        return array(
            'desc'=>'Creates a link. This type is multi-purpose: it handles external links, interwiki links, email addresses, windows shares and normal wiki links (basically any link DokuWiki knows of). The optional hint will be used as link title.',
            'hint'=>'The link title'
        );
    }
}
