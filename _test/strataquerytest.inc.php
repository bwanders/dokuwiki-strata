<?php
require_once('stratatest.inc.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
class Strata_Query_UnitTestCase extends Strata_UnitTestCase {

    function setup() {
        parent::setup();
        // Load types
        $types = new helper_plugin_stratastorage_types();
        $string = $types->loadType('string'); 
        $ref = $types->loadType('ref'); 
        $image = $types->loadType('image'); 

        // Create objects
        $bob = $ref->normalize('Bob', 'person');
        $alice = $ref->normalize('Alice', 'person');
        $carol = $ref->normalize('Carol', 'person');

        $img_bob = $ref->normalize('Bob.png', 50);
        $img_alice = $ref->normalize('Alice.svg', 50);
        $img_carol = $ref->normalize('Carol.jpg', 50);

        // Fill database
        $this->_triples->addTriple($bob, 'class', 'person');
        $this->_triples->addTriple($alice, 'class', 'person');
        $this->_triples->addTriple($carol, 'class', 'person');

        $this->_triples->addTriple($bob, 'name', 'Bob');
        $this->_triples->addTriple($alice, 'name', 'Alice');
        $this->_triples->addTriple($carol, 'name', 'Carol');

        $this->_triples->addTriple($bob, 'knows', $alice);
        $this->_triples->addTriple($alice, 'knows', $carol);
        $this->_triples->addTriple($carol, 'knows', $bob);
        $this->_triples->addTriple($carol, 'knows', $alice);

        $this->_triples->addTriple($bob, 'likes', $alice);

        $this->_triples->addTriple($bob, 'looks like', $img_bob);
        $this->_triples->addTriple($alice, 'looks like', $img_alice);
        $this->_triples->addTriple($carol, 'looks like', $img_carol);

        $this->_triples->addTriple($bob, 'is rated', 8);
        $this->_triples->addTriple($alice, 'is rated', 10);
        $this->_triples->addTriple($carol, 'is rated', 1);

        $this->_triples->addTriple($bob, 'has length', '5 ft 10 in');
        $this->_triples->addTriple($alice, 'has length', '5 ft 5 in');
        $this->_triples->addTriple($carol, 'has length', '4 ft 11 in');

        $this->_triples->addTriple($bob, 'tax rate', '15%');
        $this->_triples->addTriple($alice, 'tax rate', '10%');
        $this->_triples->addTriple($carol, 'tax rate', '2%');
    }

    function assertQueryResult($query, $expectedResult) {
        $relations = $this->_triples->queryRelations($query);
        if ($relations === false) {
            $this->fail('Query failed');
        } else {
            $this->assertIteratorsEqual($relations, new ArrayIterator($expectedResult));
            $relations->closeCursor();
        }
    }

    function assertIteratorsEqual($x, $y) {
        do {
            $this->assertEqual($x->valid(), $y->valid(), 'Number of result and expected rows differ: %s');
            $this->assertEqual($x->current(), $y->current(), 'Result row differs from expected one: %s');
            $x->next();
            $y->next();
        } while ($x->valid() || $y->valid());
    }
}
