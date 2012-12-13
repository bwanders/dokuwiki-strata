<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/types.php');
class query_operators_numeric_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testGtLte() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'filter',
                'lhs' => array (
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
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'rating'
                        ),
                        'operator' => '>',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '1'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'rating'
                        ),
                        'operator' => '<=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '10'
                        )
                    )
            )),
            'projection' => array (
                'p',
                'rating'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:alice'),
                'rating' => array('10')
            ),
            array (
                'p' => array('person:bob'),
                'rating' => array('8')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testGteLt() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'filter',
                'lhs' => array (
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
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'rating'
                        ),
                        'operator' => '>=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '1'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'rating'
                        ),
                        'operator' => '<',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '10'
                        )
                    )
            )),
            'projection' => array (
                'p',
                'rating'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:bob'),
                'rating' => array('8')
            ),
            array (
                'p' => array('person:carol'),
                'rating' => array('1')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPartiallyNumeric() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'filter',
                'lhs' => array (
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
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'tax'
                        ),
                        'operator' => '>',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '2'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'tax'
                        ),
                        'operator' => '<=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '15'
                        )
                    )
            )),
            'projection' => array (
                'p',
                'tax'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                )
            )
        );

        // Result might vary depending on database backed, only require that it does not fail
        $this->assertTrue($this->_triples->queryRelations($query));
    }

    function testNonNumeric() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'filter',
                'lhs' => array (
                    'type' => 'triple',
                    'subject' => array (
                        'type' => 'variable',
                        'text' => 'p'
                    ),
                    'predicate' => array (
                        'type' => 'literal',
                        'text' => 'class'
                    ),
                    'object' => array (
                        'type' => 'variable',
                        'text' => 'person'
                    )
                ),
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'p'
                        ),
                        'operator' => '>',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '2'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'p'
                        ),
                        'operator' => '<=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '25'
                        )
                    )
            )),
            'projection' => array (
                'p'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                )
            )
        );

        // Result might vary depending on database backed, only require that it does not fail
        $this->assertTrue($this->_triples->queryRelations($query));
    }

}
