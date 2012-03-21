<?php
require_once('strataquerytest.inc.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
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
                'p' => 'person:alice',
                'img' => '50:alice.svg',
                'rating' => '10',
                'length' => '5 ft 5 in',
                'tax' => '10%'
            ),
            array (
                'p' => 'person:bob',
                'img' => '50:bob.png',
                'rating' => '8',
                'length' => '5 ft 10 in',
                'tax' => '25%'
            ),
            array (
                'p' => 'person:carol',
                'img' => '50:carol.jpg',
                'rating' => '1',
                'length' => '4 ft 11 in',
                'tax' => '2%'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersons() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:carol',
                'knows' => 'person:alice',
                'img' => '50:carol.jpg',
                'rating' => '1',
                'length' => '4 ft 11 in',
                'tax' => '2%'
            ),
            array (
                'p' => 'person:carol',
                'knows' => 'person:bob',
                'img' => '50:carol.jpg',
                'rating' => '1',
                'length' => '4 ft 11 in',
                'tax' => '2%'
            ),
            array (
                'p' => 'person:bob',
                'knows' => 'person:alice',
                'img' => '50:bob.png',
                'rating' => '8',
                'length' => '5 ft 10 in',
                'tax' => '25%'
            ),
            array (
                'p' => 'person:alice',
                'knows' => 'person:carol',
                'img' => '50:alice.svg',
                'rating' => '10',
                'length' => '5 ft 5 in',
                'tax' => '10%'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsExceptBob() {
        // All persons except Bob
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsThatKnowAlice() {
        // All persons that know Alice
        $query = array (
            'type' => 'select',
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
                'p' => 'person:bob'
            ),
            array (
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsRelatedToAlice() {
        // All persons having some relation with Alice
        $query = array (
            'type' => 'select',
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
                'p' => 'person:bob',
                'relationWith' => 'knows'
            ),
            array (
                'p' => 'person:bob',
                'relationWith' => 'likes'
            ),
            array (
                'p' => 'person:carol',
                'relationWith' => 'knows'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsAndRelationWithAlice() {
        // All persons, including their relation with Alice
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'relationWith' => null
            ),
            array (
                'p' => 'person:bob',
                'relationWith' => 'knows'
            ),
            array (
                'p' => 'person:bob',
                'relationWith' => 'likes'
            ),
            array (
                'p' => 'person:carol',
                'relationWith' => 'knows'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsAndSpecialRelationWithAlice() {
        // All persons, including their relation with Alice (unless this relation is 'knows')
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'relationWith' => null
            ),
            array (
                'p' => 'person:bob',
                'relationWith' => 'likes'
            ),
            array (
                'p' => 'person:carol',
                'relationWith' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testAllPersonsSpecialRelationWithAlice() {
        // All persons having a relation with Alice that is not 'knows'
        $query = array (
            'type' => 'select',
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
                'p' => 'person:bob',
                'relationWith' => 'likes'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionAlice() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:bob'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionAliceWithRating() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'rating' => '10'
            ),
            array (
                'p' => 'person:bob',
                'rating' => '8'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testCarolUnionBobUnionAlice() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:bob'
            ),
            array (
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionBobUnionAlice() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:bob'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testCarolUnionBobUnionAliceMinusBob() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testBobUnionAliceOptionalRating() {
        $query = array (
            'type' => 'select',
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
              'p' => 'person:alice',
              'rating' => '10'
            ),
            array (
              'p' => 'person:bob',
              'rating' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalKnowsAndLikes() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'knows' => null,
                'likes' => null
            ),
            array (
                'p' => 'person:bob',
                'knows' => 'person:alice',
                'likes' => 'person:alice'
            ),
            array (
                'p' => 'person:carol',
                'knows' => null,
                'likes' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalKnowsOptionalLikes() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'knows' => 'person:carol',
                'likes' => null
            ),
            array (
                'p' => 'person:bob',
                'knows' => 'person:alice',
                'likes' => 'person:alice'
            ),
            array (
                'p' => 'person:carol',
                'knows' => 'person:alice',
                'likes' => null
            ),
            array (
                'p' => 'person:carol',
                'knows' => 'person:bob',
                'likes' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalRatingUnionLikes() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'rating' => '10'
            ),
            array (
                'p' => 'person:bob',
                'rating' => 'person:alice'
            ),
            array (
                'p' => 'person:bob',
                'rating' => '8'
            ),
            array (
                'p' => 'person:carol',
                'rating' => '1'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonOptionalRatingMinusCarol() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'rating' => '10'
            ),
            array (
                'p' => 'person:bob',
                'rating' => '8'
            ),
            array (
                'p' => 'person:carol',
                'rating' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonMinusBobUnionAlice() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonMinusRatingMinusCarol() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:carol'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonAndRatingMinusCarol() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'rating' => '10'
            ),
            array (
                'p' => 'person:bob',
                'rating' => '8'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonAndBobUnionAlice() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice'
            ),
            array (
                'p' => 'person:bob'
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testPersonAndKnowsOptionalLikes() {
        $query = array (
            'type' => 'select',
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
                'p' => 'person:alice',
                'knows' => 'person:carol',
                'likes' => null
            ),
            array (
                'p' => 'person:bob',
                'knows' => 'person:alice',
                'likes' => 'person:alice'
            ),
            array (
                'p' => 'person:carol',
                'knows' => 'person:alice',
                'likes' => null
            ),
            array (
                'p' => 'person:carol',
                'knows' => 'person:bob',
                'likes' => null
            )
        );

        $this->assertQueryResult($query, $expected);
    }

    function testVariableNames() {
        // Strange strings as variable names
        $query = array (
            'type' => 'select',
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
                                'text' => '  like '
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
                '  like '
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
                '_' => 'person:alice',
                '?c' => 'person',
                'given name' => 'Alice',
                '2 knów \'`"' => 'person:carol',
                ':l' => '5 ft 5 in',
                '%t' => '10%',
                '1.10' => '10',
                '  like ' => '50:alice.svg'
            ),
            array (
                '_' => 'person:bob',
                '?c' => 'person',
                'given name' => 'Bob',
                '2 knów \'`"' => 'person:alice',
                ':l' => '5 ft 10 in',
                '%t' => '25%',
                '1.10' => '8',
                '  like ' => '50:bob.png'
            ),
            array (
                '_' => 'person:carol',
                '?c' => 'person',
                'given name' => 'Carol',
                '2 knów \'`"' => 'person:alice',
                ':l' => '4 ft 11 in',
                '%t' => '2%',
                '1.10' => '1',
                '  like ' => '50:carol.jpg'
            ),
            array (
                '_' => 'person:carol',
                '?c' => 'person',
                'given name' => 'Carol',
                '2 knów \'`"' => 'person:bob',
                ':l' => '4 ft 11 in',
                '%t' => '2%',
                '1.10' => '1',
                '  like ' => '50:carol.jpg'
            )
        );

        $this->assertQueryResult($query, $expected);
    }
}

