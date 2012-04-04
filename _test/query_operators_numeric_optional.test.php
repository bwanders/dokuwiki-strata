<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
class query_operators_numeric_optional_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();
    }

    function testGtLtePartiallyNumeric() {
        $query = array (
            'type' => 'select',
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
                            'text' => '25'
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

        $expected = array (
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

    function testGteLtPartiallyNumeric() {
        $query = array (
            'type' => 'select',
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
                        'operator' => '>=',
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
                        'operator' => '<',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '25'
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

        $expected = array (
            array (
                'p' => 'person:alice',
                'tax' => '10%'
            ),
            array (
                'p' => 'person:carol',
                'tax' => '2%'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testGtLteNatural() {
        $query = array (
            'type' => 'select',
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
                        'text' => 'has length'
                    ),
                    'object' => array (
                        'type' => 'variable',
                        'text' => 'length'
                    )
                ),
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'length'
                        ),
                        'operator' => '>',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '4 ft'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'length'
                        ),
                        'operator' => '<=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '5 ft 5 in'
                        )
                    )
            )),
            'projection' => array (
                'p',
                'length'
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
                'p' => 'person:alice',
                'length' => '5 ft 5 in'
            ),
            array (
                'p' => 'person:carol',
                'length' => '4 ft 11 in'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testGteLtNatural() {
        $query = array (
            'type' => 'select',
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
                        'text' => 'has length'
                    ),
                    'object' => array (
                        'type' => 'variable',
                        'text' => 'length'
                    )
                ),
                'rhs' => array (
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'length'
                        ),
                        'operator' => '>=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '4 ft'
                        )
                    ),
                    array (
                        'type' => 'operator',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'length'
                        ),
                        'operator' => '<',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => '5 ft 10 in'
                        )
                    )
            )),
            'projection' => array (
                'p',
                'length'
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
                'p' => 'person:alice',
                'length' => '5 ft 5 in'
            ),
            array (
                'p' => 'person:carol',
                'length' => '4 ft 11 in'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

}
