<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/types.php');
class query_sort_optional_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testPartiallyNumericSort() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
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
                'p' => array('person:carol'),
                'tax' => array('2%')
            ),
            array (
                'p' => array('person:alice'),
                'tax' => array('10%')
            ),
            array (
                'p' => array('person:bob'),
                'tax' => array('25%')
            )
        );

        $this->assertQueryResult($query, $expected, 'Partial numeric sort (numbers first, text second) unsupported');
    }

    function testNaturalSort() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
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
                'p' => array('person:carol'),
                'length' => array('4 ft 11 in')
            ),
            array (
                'p' => array('person:alice'),
                'length' => array('5 ft 5 in')
            ),
            array (
                'p' => array('person:bob'),
                'length' => array('5 ft 10 in')
            )
        );

        $this->assertQueryResult($query, $expected, 'Full natural sort unsupported');
    }

    function testUnicodeSort() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
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
                'p' => array('person:alice'),
                'id' => array('α')
            ),
            array (
                'p' => array('person:bob'),
                'id' => array('Β')
            ),
            array (
                'p' => array('person:carol'),
                'id' => array('γ')
            )
        );

        $this->assertQueryResult($query, $expected, 'Case insensitive unicode sort unsupported');
    }

}
