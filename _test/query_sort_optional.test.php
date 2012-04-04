<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
class query_sort_optional_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testPartiallyNumericSort() {
        $query = array (
            'type' => 'select',
            'group' => array (
                'type' => 'triple',
                'subject' => array (
                    'type' => 'variable',
                    'text' => 'p'
                ),
                'predicate' => array (
                    'type' => 'literal',
                    'text' => 'tax rate'
                ),
                'object' => array (
                    'type' => 'variable',
                    'text' => 'tax'
                )
            ),
            'projection' => array (
                'p',
                'tax'
            ),
            'ordering' => array (
                array (
                    'variable' => 'tax',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => 'person:carol',
                'tax' => '2%'
            ),
            array (
                'p' => 'person:alice',
                'tax' => '10%'
            ),
            array (
                'p' => 'person:bob',
                'tax' => '25%'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testNaturalSort() {
        $query = array (
            'type' => 'select',
            'group' => array (
                'type' => 'triple',
                'subject' => array (
                    'type' => 'variable',
                    'text' => 'p'
                ),
                'predicate' => array (
                    'type' => 'literal',
                    'text' => 'has length'
                ),
                'object' => array (
                    'type' => 'variable',
                    'text' => 'length'
                )
            ),
            'projection' => array (
                'p',
                'length'
            ),
            'ordering' => array (
                array (
                    'variable' => 'length',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => 'person:carol',
                'length' => '4 ft 11 in'
            ),
            array (
                'p' => 'person:alice',
                'length' => '5 ft 5 in'
            ),
            array (
                'p' => 'person:bob',
                'length' => '5 ft 10 in'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testUnicodeSort() {
        $query = array (
            'type' => 'select',
            'group' => array (
                'type' => 'triple',
                'subject' => array (
                    'type' => 'variable',
                    'text' => 'p'
                ),
                'predicate' => array (
                    'type' => 'literal',
                    'text' => 'identifier'
                ),
                'object' => array (
                    'type' => 'variable',
                    'text' => 'id'
                )
            ),
            'projection' => array (
                'p',
                'id'
            ),
            'ordering' => array (
                array (
                    'variable' => 'id',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => 'person:alice',
                'id' => 'α'
            ),
            array (
                'p' => 'person:bob',
                'id' => 'Β'
            ),
            array (
                'p' => 'person:carol',
                'id' => 'γ'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

}
