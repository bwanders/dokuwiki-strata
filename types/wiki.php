<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_INC.'inc/parser/parser.php');

/**
 * The 'render as wiki text' type.
 */
class plugin_strata_type_wiki extends plugin_strata_type {
    function normalize($value, $hint) {
        $ins = $this->_instructions($value);

        $value = "\n".str_replace("\r\n","\n",$value)."\n";

        for($i=0;$i<count($ins);$i++) {
            switch($ins[$i][0]) {
                case 'internallink':
                    $replacement = $this->_normalize_internallink($ins[$i][1]);
                    break;
                case 'locallink':
                    $replacement = $this->_normalize_locallink($ins[$i][1]);
                    break;
                case 'internalmedia':
                    $replacement = $this->_normalize_media($ins[$i][1]);
                    break;
                case 'externallink':
                    $replacement = $this->_linkSyntax($ins[$i][1], $ins[$i][1][0]);
                    break;
                default:
                    continue 2;
            }

            $value = substr_replace($value, $replacement, $ins[$i][2], $ins[$i+1][2] - $ins[$i][2]);
        }

        // strip off only the inserted newlines
        return substr($value,1,-1);
    }

    /**
     * Normalizes an internal link.
     */
    function _normalize_internallink($instruction) {
        global $ID;

        // split off query string
        $parts = explode('?', $instruction[0] ,2);

        $id = $parts[0];
        
        // normalize selflink
        if($id === '') {
            $id = $ID;
        }

        // actually resolve the page
        resolve_pageid(getNS($ID), $id, $exists);

        // render the link
        return $this->_linkSyntax($instruction, $id);
    }

    /**
     * Normalizes a local link.
     */
    function _normalize_locallink($instruction) {
        global $ID;

        // simply prefix the current page
        return $this->_linkSyntax($instruction, $ID.'#'.$instruction[0]); 
    }

    /**
     * Normalizes a media array.
     */
    function _normalize_media($instruction) {
        global $ID;

        // construct media structure based on input
        if(isset($instruction['type'])) {
            $media = $instruction;
        } else {
            list($src, $title, $align, $width, $height, $cache, $linking) = $instruction;
            $media = compact('src','title','align','width','height');
            $media['type']= 'internalmedia';
        }

        // normalize internal media links
        if($media['type'] == 'internalmedia') {
            list($src,$hash) = explode('#',$media['src'],2);
            resolve_mediaid(getNS($ID),$src, $exists);
            if($hash) $src.='#'.$hash;
            $media['src'] = ':'.$src;
        }

        // render the media structure
        return $this->_mediaSyntax($media);
    }

    /**
     * Renders the media syntax.
     */
    function _mediaSyntax($media) {
        // the source
        $src = $media['src'];

        // the resizing part
        if(isset($media['width'])) {
            $size = '?'.$media['width'];
            if(isset($media['height'])) {
                $size .= 'x'.$media['height'];
            }
        } else {
            $size = '';
        }

        // the title part
        if(isset($media['title'])) {
            $title = '|'.$media['title'];
        } else {
            $title = '';
        }

        // the alignment parts
        if(isset($media['align'])) {
            switch($media['align']) {
            case 'left':
                $al = ''; $ar = ' '; break;
            case 'right':
                $al = ' '; $ar = ''; break;
            case 'center':
                $al = ' '; $ar = ' '; break;
            }
        }

        // construct the syntax
        return '{{'.$al.$src.$size.$ar.$title.'}}';
    }
    
    /**
     * Renders the link syntax, invoking media normalization
     * if required.
     */
    function _linkSyntax($instruction, $newLink) {
        // fetch params from old link
        $parts = explode('?', $instruction[0],2);
        if(count($parts) === 2) {
            $params = '?'.$parts[1];
        } else {
            $params = '';
        }

        // determine title
        $title = '';
        if(isset($instruction[1])) {
            if(is_array($instruction[1])) {
                if($instruction[1]['type'] == 'internalmedia') {
                    $title='|'.$this->_normalize_media($instruction[1]);
                } else {
                    $title='|'.$this->_mediaSyntax($instruction[1]);
                }
            } else {
                $title = '|'.$instruction[1];
            }
        }

        // construct a new link string
        return '[['.$newLink.$params.$title.']]';

    }

    function render($mode, &$R, &$T, $value, $hint) {
        // though this breaks backlink functionality, we really do not want
        // metadata renders of included pieces of wiki.
        if($mode == 'xhtml') {
            $instructions = $this->_instructions($value);
            $instructions = array_slice($instructions, 2, -2);
            $R->nest($instructions);
        }
    }

    function getInfo() {
        return array(
            'desc'=>'Allows the use of normal dokuwiki syntax. The hint is ignored.',
            'tags'=>array('experimental')
        );
    }

    function _instructions($text) {
        // determine all parser modes that are allowable as inline modes
        // (i.e., those that are allowed inside a table cell, minus those
        // that have a paragraph type other than 'normal')

        // determine all modes allowed inside a table cell or list item
        global $PARSER_MODES;
        $allowedModes = array_merge (
            $PARSER_MODES['formatting'],
            $PARSER_MODES['substition'],
            $PARSER_MODES['disabled'],
            $PARSER_MODES['protected']
        );

        // determine all modes that are not allowed either due to paragraph
        // handling, or because they're blacklisted as they don't make sense.
        $blockHandler = new Doku_Handler_Block();
        $disallowedModes = array_merge(
            $blockHandler->blockOpen,
            $blockHandler->stackOpen,
            array('notoc', 'nocache')
        );

        $allowedModes = array_diff($allowedModes, $disallowedModes);

        $parser = new Doku_Parser();
        $parser->Handler = new Doku_Handler();

        foreach(p_get_parsermodes() as $mode) {
            if(!in_array($mode['mode'], $allowedModes)) continue;

            $parser->addMode($mode['mode'], $mode['obj']); 
        }

        trigger_event('PARSER_WIKITEXT_PREPROCESS', $text);
        $p = $parser->parse($text);
        return $p;
    }
}
