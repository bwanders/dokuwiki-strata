<?php
/**
 * DokuWiki Plugin stratabasic
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

class strata_exception extends Exception {
    protected $data;

    /**
     * Constructor with message and data.
     */
    public function __construct($message, $data) {
        parent::__construct($message);
        $this->data =& $data;
    }

    public function getData() {
        return $this->data;
    }
}
