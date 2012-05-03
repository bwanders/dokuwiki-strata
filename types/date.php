<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Brend Wanders <b.wanders@utwente.nl>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die('Meh.');

/**
 * The date type.
 */
class plugin_strata_type_date extends plugin_strata_type {
    function render($mode, &$R, &$triples, $value, $hint) {
        if($mode == 'xhtml') {
            if(is_numeric($value)) {
                // use the hint if available
                $format = $hint ?: 'Y-m-d';

                // construct representation
                $date = new DateTime();
                $date->setTimestamp((int)$value);

                // render
                $R->doc .= $R->_xmlEntities($date->format($format));
            } else {
                $R->doc .= $R->_xmlEntities($value);
            }
            return true;
        }

        return false;
    }

    function normalize($value, $hint) {
        // use hint if available
        // (prefix with '!' te reset all fields to the unix epoch)
        $format = '!'. ($hint ?: 'Y-m-d');

        // try and parse the value
        $date = date_create_from_format($format, $value);

        // handle failure in a non-intrusive way
        if($date === false) {
            return $value;
        } else {
            return $date->getTimestamp();
        }
    }
        
    function getInfo() {
        return array(
            'desc'=>'Stores and displays dates in the YYYY-MM-DD format. The optional hint can give a different format to use.',
            'tags'=>array('numeric'),
            'hint'=>'different date format'
        );
    }
}
