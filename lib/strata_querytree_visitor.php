<?php
/**
 * DokuWiki Plugin stratastorage (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Brend Wanders <b.wanders@utwente.nl>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die('Meh.');

class strata_querytree_visitor {
    /**
     * Visits a triple pattern.
     */
    function visit_tp(&$tp) {
    }

    /**
     * Visit a filter pattern.
     */
    function visit_fp(&$fp) {
    }

    /**
     * Visit an optional operation.
     */
    function visit_opt(&$query) {
        $this->dispatch($query['lhs']);
        $this->dispatch($query['rhs']);
    }

    /**
     * Visit an and operation.
     */
    function visit_and(&$query) {
        $this->dispatch($query['lhs']);
        $this->dispatch($query['rhs']);
    }

    /**
     * Visit a filter operation.
     */
    function visit_filter(&$query) {
        $this->dispatch($query['lhs']);
        foreach($query['rhs'] as &$filter) {
            $this->visit_fp($filter);
        }
    }

    /**
     * Visit minus operation.
     */
    function visit_minus(&$query) {
        $this->dispatch($query['lhs']);
        $this->dispatch($query['rhs']);

    }

    /**
     * Visit union operation.
     */
    function visit_union(&$query) {
        $this->dispatch($query['lhs']);
        $this->dispatch($query['rhs']);

    }

    /**
     * Visit projection and ordering.
     */
    function visit_select(&$query) {
        $this->dispatch($query['group']);
    }

    function dispatch(&$query) {
        switch($query['type']) {
            case 'select':
                return $this->visit_select($query);
            case 'union':
                return $this->visit_union($query);
            case 'minus':
                return $this->visit_minus($query);
            case 'optional':
                return $this->visit_opt($query);
            case 'filter':
                return $this->visit_filter($query);
            case 'triple':
                return $this->visit_tp($query);
            case 'and':
                return $this->visit_and($query);
            default:
        }
    }

    /**
     * Visits an abstract query tree to SQL.
     */
    function visit(&$query) {
        $this->dispatch($query);
    }
}
