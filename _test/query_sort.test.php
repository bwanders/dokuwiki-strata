<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
class query_sort_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testNumericSort() {
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
                    'text' => 'is rated'
                ),
                'object' => array (
                    'type' => 'variable',
                    'text' => 'rating'
                )
            ),
            'projection' => array (
                'p',
                'rating'
            ),
            'ordering' => array (
                array (
                    'variable' => 'rating',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => 'person:carol',
                'rating' => '1'
            ),
            array (
                'p' => 'person:bob',
                'rating' => '8'
            ),
            array (
                'p' => 'person:alice',
                'rating' => '10'
            )
        );

        $this->assertQueryResult($query, $expected);
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

        // Result might vary depending on database backed, only require that it does not fail
        $this->assertTrue($this->_triples->queryRelations($query));
    }

}
