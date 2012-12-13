<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/types.php');
class query_operators_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testEquals() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '=',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testNotEquals() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '!=',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:alice')
            ),
            array (
                'p' => array('person:bob')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testLike() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '~',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testNotLike() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '!~',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:alice')
            ),
            array (
                'p' => array('person:bob')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBeginsWith() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '^~',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testEndsWith() {
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
                'rhs' => array (array (
                    'type' => 'operator',
                    'lhs' => array (
                        'type' => 'variable',
                        'text' => 'tax'
                    ),
                    'operator' => '$~',
                    'rhs' => array (
                        'type' => 'literal',
                        'text' => '2%'
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

        $expected = array (
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

}

