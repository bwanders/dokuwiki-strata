<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/types.php');
class query_test extends Strata_Query_UnitTestCase {

    function setup() {
        parent::setup();

        $this->_isPerson =  array (
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
                'type' => 'literal',
                'text' => 'person'
            )
        );
        $this->_personBob = array (
            'type' => 'triple',
            'subject' => array (
                'type' => 'variable',
                'text' => 'p'
            ),
            'predicate' => array (
                'type' => 'literal',
                'text' => 'name'
            ),
            'object' => array (
                'type' => 'literal',
                'text' => 'Bob'
            )
        );
        $this->_personAlice = array (
            'type' => 'triple',
            'subject' => array (
                'type' => 'variable',
                'text' => 'p'
            ),
            'predicate' => array (
                'type' => 'literal',
                'text' => 'name'
            ),
            'object' => array (
                'type' => 'literal',
                'text' => 'Alice'
            )
        );
        $this->_personCarol = array (
            'type' => 'triple',
            'subject' => array (
                'type' => 'variable',
                'text' => 'p'
            ),
            'predicate' => array (
                'type' => 'literal',
                'text' => 'name'
            ),
            'object' => array (
                'type' => 'literal',
                'text' => 'Carol'
            )
        );
        $this->_bobUnionAlice =  array (
            'type' => 'union',
            'lhs' => $this->_personBob,
            'rhs' => $this->_personAlice
        );
        $this->_personRating = array (
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
        );
        $this->_personKnows = array (
            'type' => 'triple',
            'subject' => array (
                'type' => 'variable',
                'text' => 'p'
            ),
            'predicate' => array (
                'type' => 'literal',
                'text' => 'knows'
            ),
            'object' => array (
                'type' => 'variable',
                'text' => 'knows'
            )
        );
        $this->_personLikes = array (
            'type' => 'triple',
            'subject' => array (
                'type' => 'variable',
                'text' => 'p'
            ),
            'predicate' => array (
                'type' => 'literal',
                'text' => 'likes'
            ),
            'object' => array (
                'type' => 'variable',
                'text' => 'likes'
            )
        );
        $this->_ratingMinusCarol =  array (
            'type' => 'minus',
            'lhs' => $this->_personRating,
            'rhs' => $this->_personCarol
        );
        $this->_knowsOptionalLikes =  array (
            'type' => 'optional',
            'lhs' => $this->_personKnows,
            'rhs' => $this->_personLikes
        );
    }

    function testAllPersonsOnce() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'and',
                            'lhs' => $this->_isPerson,
                            'rhs' => array (
                                'type' => 'triple',
                                'subject' => array (
                                    'type' => 'variable',
                                    'text' => 'p'
                                ),
                                'predicate' => array (
                                    'type' => 'literal',
                                    'text' => 'looks like'
                                ),
                                'object' => array (
                                    'type' => 'variable',
                                    'text' => 'img'
                                )
                            )
                        ),
                        'rhs' => $this->_personRating
                    ),
                    'rhs' => array (
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
                    )
                ),
                'rhs' => array (
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
                )
            ),
            'projection' => array (
                'p',
                'img',
                'rating',
                'length',
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
                'p' => array('person:alice'),
                'img' => array('50:alice.svg'),
                'rating' => array('10'),
                'length' => array('5 ft 5 in'),
                'tax' => array('10%')
            ),
            array (
                'p' => array('person:bob'),
                'img' => array('50:bob.png'),
                'rating' => array('8'),
                'length' => array('5 ft 10 in'),
                'tax' => array('25%')
            ),
            array (
                'p' => array('person:carol'),
                'img' => array('50:carol.jpg'),
                'rating' => array('1'),
                'length' => array('4 ft 11 in'),
                'tax' => array('2%')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersons() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'and',
                            'lhs' => array (
                                'type' => 'and',
                                'lhs' => $this->_isPerson,
                                'rhs' => $this->_personKnows
                            ),
                            'rhs' => array (
                                'type' => 'triple',
                                'subject' => array (
                                    'type' => 'variable',
                                    'text' => 'p'
                                ),
                                'predicate' => array (
                                    'type' => 'literal',
                                    'text' => 'looks like'
                                ),
                                'object' => array (
                                    'type' => 'variable',
                                    'text' => 'img'
                                )
                            )
                        ),
                        'rhs' => $this->_personRating
                    ),
                    'rhs' => array (
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
                    )
                ),
                'rhs' => array (
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
                )
            ),
            'projection' => array (
                'p',
                'knows',
                'img',
                'rating',
                'length',
                'tax'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'desc'
                ),
                array (
                    'variable' => 'knows',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:carol'),
                'knows' => array('person:alice'),
                'img' => array('50:carol.jpg'),
                'rating' => array('1'),
                'length' => array('4 ft 11 in'),
                'tax' => array('2%')
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array('person:bob'),
                'img' => array('50:carol.jpg'),
                'rating' => array('1'),
                'length' => array('4 ft 11 in'),
                'tax' => array('2%')
            ),
            array (
                'p' => array('person:bob'),
                'knows' => array('person:alice'),
                'img' => array('50:bob.png'),
                'rating' => array('8'),
                'length' => array('5 ft 10 in'),
                'tax' => array('25%')
            ),
            array (
                'p' => array('person:alice'),
                'knows' => array('person:carol'),
                'img' => array('50:alice.svg'),
                'rating' => array('10'),
                'length' => array('5 ft 5 in'),
                'tax' => array('10%')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsExceptBob() {
        // All persons except Bob
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'minus',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_personBob
            ),
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
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsThatKnowAlice() {
        // All persons that know Alice
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => $this->_isPerson,
                    'rhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => 'p'
                        ),
                        'predicate' => array (
                            'type' => 'literal',
                            'text' => 'knows'
                        ),
                        'object' => array (
                            'type' => 'variable',
                            'text' => 'relation'
                        )
                    )
                ),
                'rhs' => array (
                    'type' => 'triple',
                    'subject' => array (
                        'type' => 'variable',
                        'text' => 'relation'
                    ),
                    'predicate' => array (
                        'type' => 'literal',
                        'text' => 'name'
                    ),
                    'object' => array (
                        'type' => 'literal',
                        'text' => 'Alice'
                    )
                )
            ),
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
                'p' => array('person:bob')
            ),
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsRelatedToAlice() {
        // All persons having some relation with Alice
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => $this->_isPerson,
                    'rhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => 'p'
                        ),
                        'predicate' => array (
                            'type' => 'variable',
                            'text' => 'relationWith'
                        ),
                        'object' => array (
                            'type' => 'variable',
                            'text' => 'relation'
                        )
                    )
                ),
                'rhs' => array (
                    'type' => 'triple',
                    'subject' => array (
                        'type' => 'variable',
                        'text' => 'relation'
                    ),
                    'predicate' => array (
                        'type' => 'literal',
                        'text' => 'name'
                    ),
                    'object' => array (
                        'type' => 'literal',
                        'text' => 'Alice'
                    )
                )
            ),
            'projection' => array (
                'p',
                'relationWith'
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
                'relationWith' => array('knows')
            ),
            array (
                'p' => array('person:bob'),
                'relationWith' => array('likes')
            ),
            array (
                'p' => array('person:carol'),
                'relationWith' => array('knows')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsAndRelationWithAlice() {
        // All persons, including their relation with Alice
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => 'p'
                        ),
                        'predicate' => array (
                            'type' => 'variable',
                            'text' => 'relationWith'
                        ),
                        'object' => array (
                            'type' => 'variable',
                            'text' => 'relation'
                        )
                    ),
                    'rhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => 'relation'
                        ),
                        'predicate' => array (
                            'type' => 'literal',
                            'text' => 'name'
                        ),
                        'object' => array (
                            'type' => 'literal',
                            'text' => 'Alice'
                        )
                    )
                )
            ),
            'projection' => array (
                'p',
                'relationWith'
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
                'relationWith' => array()
            ),
            array (
                'p' => array('person:bob'),
                'relationWith' => array('knows')
            ),
            array (
                'p' => array('person:bob'),
                'relationWith' => array('likes')
            ),
            array (
                'p' => array('person:carol'),
                'relationWith' => array('knows')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsAndSpecialRelationWithAlice() {
        // All persons, including their relation with Alice (unless this relation is 'knows')
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => array (
                    'type' => 'filter',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'p'
                            ),
                            'predicate' => array (
                                'type' => 'variable',
                                'text' => 'relationWith'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'relation'
                            )
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'relation'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'name'
                            ),
                            'object' => array (
                                'type' => 'literal',
                                'text' => 'Alice'
                            )
                        )
                    ),
                    'rhs' => array (
                        array (
                            'type' => 'filter',
                            'lhs' => array (
                                'type' => 'variable',
                                'text' => 'relationWith'
                            ),
                            'operator' => '!=',
                            'rhs' => array (
                                'type' => 'literal',
                                'text' => 'knows'
                            )
                        )
                    )
                )
            ),
            'projection' => array (
                'p',
                'relationWith'
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
                'relationWith' => array()
            ),
            array (
                'p' => array('person:bob'),
                'relationWith' => array('likes')
            ),
            array (
                'p' => array('person:carol'),
                'relationWith' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsSpecialRelationWithAlice() {
        // All persons having a relation with Alice that is not 'knows'
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'filter',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => $this->_isPerson,
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'p'
                            ),
                            'predicate' => array (
                                'type' => 'variable',
                                'text' => 'relationWith'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'relation'
                            )
                        )
                    ),
                    'rhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => 'relation'
                        ),
                        'predicate' => array (
                            'type' => 'literal',
                            'text' => 'name'
                        ),
                        'object' => array (
                            'type' => 'literal',
                            'text' => 'Alice'
                        )
                    )
                ),
                'rhs' => array (
                    array (
                        'type' => 'filter',
                        'lhs' => array (
                            'type' => 'variable',
                            'text' => 'relationWith'
                        ),
                        'operator' => '!=',
                        'rhs' => array (
                            'type' => 'literal',
                            'text' => 'knows'
                        )
                    )
                )
            ),
            'projection' => array (
                'p',
                'relationWith'
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
                'relationWith' => array('likes')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionAlice() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => $this->_bobUnionAlice,
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

    function testBobUnionAliceWithRating() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'union',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => $this->_personBob,
                    'rhs' => $this->_personRating
                ),
                'rhs' => array (
                    'type' => 'and',
                    'lhs' => $this->_personAlice,
                    'rhs' => $this->_personRating
                ),
            ),
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

    function testCarolUnionBobUnionAlice() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'union',
                'lhs' => $this->_personCarol,
                'rhs' => $this->_bobUnionAlice
            ),
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
            ),
            array (
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionBobUnionAlice() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'union',
                'lhs' => $this->_personBob,
                'rhs' => $this->_bobUnionAlice
            ),
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

    function testCarolUnionBobUnionAliceMinusBob() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'union',
                'lhs' => $this->_personCarol,
                'rhs' => array (
                    'type' => 'minus',
                    'lhs' => $this->_bobUnionAlice,
                    'rhs' => $this->_personBob
                )
            ),
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
                'p' => array('person:carol')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionAliceOptionalRating() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'union',
                'lhs' => $this->_personBob,
                'rhs' => array (
                    'type' => 'optional',
                    'lhs' => $this->_personAlice,
                    'rhs' => $this->_personRating
                )
            ),
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
              'rating' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalKnowsAndLikes() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => array (
                    'type' => 'and',
                    'lhs' => $this->_personKnows,
                    'rhs' => $this->_personLikes
                )
            ),
            'projection' => array (
                'p',
                'knows',
                'likes'
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
                'knows' => array(),
                'likes' => array()
            ),
            array (
                'p' => array('person:bob'),
                'knows' => array('person:alice'),
                'likes' => array('person:alice')
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array(),
                'likes' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalKnowsOptionalLikes() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_knowsOptionalLikes
            ),
            'projection' => array (
                'p',
                'knows',
                'likes'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                ),
                array (
                    'variable' => 'knows',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:alice'),
                'knows' => array('person:carol'),
                'likes' => array()
            ),
            array (
                'p' => array('person:bob'),
                'knows' => array('person:alice'),
                'likes' => array('person:alice')
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array('person:alice'),
                'likes' => array()
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array('person:bob'),
                'likes' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalRatingUnionLikes() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => array (
                    'type' => 'union',
                    'lhs' => $this->_personRating,
                    'rhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'p'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'likes'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'rating'
                            )
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'p'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'likes'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'likes'
                            )
                        )
                    )
                )
            ),
            'projection' => array (
                'p',
                'rating'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                ),
                array (
                    'variable' => 'likes',
                    'direction' => 'desc'
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
                'rating' => array('person:alice')
            ),
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

    function testPersonOptionalRatingMinusCarol() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'optional',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_ratingMinusCarol
            ),
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
            ),
            array (
                'p' => array('person:carol'),
                'rating' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonMinusBobUnionAlice() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'minus',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_bobUnionAlice
            ),
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

    function testPersonMinusRatingMinusCarol() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'minus',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_ratingMinusCarol
            ),
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

    function testPersonAndRatingMinusCarol() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_ratingMinusCarol
            ),
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

    function testPersonAndBobUnionAlice() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_bobUnionAlice
            ),
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

    function testPersonAndKnowsOptionalLikes() {
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => $this->_isPerson,
                'rhs' => $this->_knowsOptionalLikes
            ),
            'projection' => array (
                'p',
                'knows',
                'likes'
            ),
            'ordering' => array (
                array (
                    'variable' => 'p',
                    'direction' => 'asc'
                ),
                array (
                    'variable' => 'knows',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:alice'),
                'knows' => array('person:carol'),
                'likes' => array()
            ),
            array (
                'p' => array('person:bob'),
                'knows' => array('person:alice'),
                'likes' => array('person:alice')
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array('person:alice'),
                'likes' => array()
            ),
            array (
                'p' => array('person:carol'),
                'knows' => array('person:bob'),
                'likes' => array()
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testVariableNames() {
        // Strange strings as variable names
        $query = array (
            'type' => 'select',
            'grouping'=>array(),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'triple',
                        'subject' => array (
                            'type' => 'variable',
                            'text' => '_'
                        ),
                        'predicate' => array (
                            'type' => 'literal',
                            'text' => 'class'
                        ),
                        'object' => array (
                            'type' => 'variable',
                            'text' => '?c'
                        ),
                    ),
                    'rhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'name'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'given name'
                            ),
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'knows'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => '2 knów \'`"'
                            )
                        )
                    )
                ),
            'rhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'has length'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => ':l'
                            ),
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'tax rate'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => '%t'
                            )
                        )
                    ),
                'rhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'is rated'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => '1.10'
                            ),
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => '_'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'looks like'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => '  \\  like ""! '
                            )
                        )
                    )
                )
            ),
            'projection' => array (
                '_',
                '?c',
                'given name',
                '2 knów \'`"',
                ':l',
                '%t',
                '1.10',
                '  \\  like ""! '
            ),
            'ordering' => array (
                array (
                    'variable' => '_',
                    'direction' => 'asc'
                ),
                array (
                    'variable' => '2 knów \'`"',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                '_' => array('person:alice'),
                '?c' => array('person'),
                'given name' => array('Alice'),
                '2 knów \'`"' => array('person:carol'),
                ':l' => array('5 ft 5 in'),
                '%t' => array('10%'),
                '1.10' => array('10'),
                '  \\  like ""! ' => array('50:alice.svg')
            ),
            array (
                '_' => array('person:bob'),
                '?c' => array('person'),
                'given name' => array('Bob'),
                '2 knów \'`"' => array('person:alice'),
                ':l' => array('5 ft 10 in'),
                '%t' => array('25%'),
                '1.10' => array('8'),
                '  \\  like ""! ' => array('50:bob.png')
            ),
            array (
                '_' => array('person:carol'),
                '?c' => array('person'),
                'given name' => array('Carol'),
                '2 knów \'`"' => array('person:alice'),
                ':l' => array('4 ft 11 in'),
                '%t' => array('2%'),
                '1.10' => array('1'),
                '  \\  like ""! ' => array('50:carol.jpg')
            ),
            array (
                '_' => array('person:carol'),
                '?c' => array('person'),
                'given name' => array('Carol'),
                '2 knów \'`"' => array('person:bob'),
                ':l' => array('4 ft 11 in'),
                '%t' => array('2%'),
                '1.10' => array('1'),
                '  \\  like ""! ' => array('50:carol.jpg')
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testGrouping() {
        $query = array (
            'type' => 'select',
            'grouping' => array (
                'knows',
                'rating'
            ),
            'group' => array (
                'type' => 'and',
                'lhs' => array (
                    'type' => 'and',
                    'lhs' => array (
                        'type' => 'and',
                        'lhs' => array (
                            'type' => 'and',
                            'lhs' => $this->_isPerson,
                            'rhs' => $this->_personKnows
                        ),
                        'rhs' => array (
                            'type' => 'triple',
                            'subject' => array (
                                'type' => 'variable',
                                'text' => 'knows'
                            ),
                            'predicate' => array (
                                'type' => 'literal',
                                'text' => 'knows'
                            ),
                            'object' => array (
                                'type' => 'variable',
                                'text' => 'who knows'
                            )
                        )
                    ),
                    'rhs' => $this->_personRating
                ),
                'rhs' => array (
                    'type' => 'triple',
                    'subject' => array (
                        'type' => 'variable',
                        'text' => 'knows'
                    ),
                    'predicate' => array (
                        'type' => 'literal',
                        'text' => 'identifier'
                    ),
                    'object' => array (
                        'type' => 'variable',
                        'text' => 'knows id'
                    )
                )
            ),
            'projection' => array (
                'p',
                'knows id',
                'who knows',
            ),
            'ordering' => array (
                array (
                    'variable' => 'rating',
                    'direction' => 'desc'
                ),
                array (
                    'variable' => 'knows',
                    'direction' => 'asc'
                ),
                array (
                    'variable' => 'who knows',
                    'direction' => 'asc'
                )
            )
        );

        $expected = array (
            array (
                'p' => array('person:alice', 'person:alice'),
                'knows id' => array('γ', 'γ'),
                'who knows' => array('person:alice', 'person:bob'),
            ),
            array (
                'p' => array('person:bob'),
                'knows id' => array('α'),
                'who knows' => array('person:carol'),
            ),
            array (
                'p' => array('person:carol'),
                'knows id' => array('α'),
                'who knows' => array('person:carol'),
            ),
            array (
                'p' => array('person:carol'),
                'knows id' => array('Β'),
                'who knows' => array('person:alice'),
            )
        );

        $this->assertQueryResult($query, $expected);
    }
}

