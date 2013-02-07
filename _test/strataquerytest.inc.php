<?php
require_once('stratatest.inc.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/util.php');
class Strata_Query_UnitTestCase extends Strata_UnitTestCase {

    function setup() {
        parent::setup();
        // Load types
        $types = new helper_plugin_strata_util();
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
        $this->_triples->addTriple($bob, 'class', 'person', 'wiki');
        $this->_triples->addTriple($alice, 'class', 'person', 'wiki');
        $this->_triples->addTriple($carol, 'class', 'person', 'wiki');

        $this->_triples->addTriple($bob, 'name', 'Bob', 'wiki');
        $this->_triples->addTriple($alice, 'name', 'Alice', 'wiki');
        $this->_triples->addTriple($carol, 'name', 'Carol', 'wiki');

        $this->_triples->addTriple($bob, 'identifier', 'Β', 'wiki');
        $this->_triples->addTriple($alice, 'identifier', 'α', 'wiki');
        $this->_triples->addTriple($carol, 'identifier', 'γ', 'wiki');

        $this->_triples->addTriple($bob, 'knows', $alice, 'wiki');
        $this->_triples->addTriple($alice, 'knows', $carol, 'wiki');
        $this->_triples->addTriple($carol, 'knows', $bob, 'wiki');
        $this->_triples->addTriple($carol, 'knows', $alice, 'wiki');

        $this->_triples->addTriple($bob, 'likes', $alice, 'wiki');

        $this->_triples->addTriple($bob, 'looks like', $img_bob, 'wiki');
        $this->_triples->addTriple($alice, 'looks like', $img_alice, 'wiki');
        $this->_triples->addTriple($carol, 'looks like', $img_carol, 'wiki');

        $this->_triples->addTriple($bob, 'is rated', 8, 'wiki');
        $this->_triples->addTriple($alice, 'is rated', 10, 'wiki');
        $this->_triples->addTriple($carol, 'is rated', 1, 'wiki');

        $this->_triples->addTriple($bob, 'has length', '5 ft 10 in', 'wiki');
        $this->_triples->addTriple($alice, 'has length', '5 ft 5 in', 'wiki');
        $this->_triples->addTriple($carol, 'has length', '4 ft 11 in', 'wiki');

        $this->_triples->addTriple($bob, 'tax rate', '25%', 'wiki');
        $this->_triples->addTriple($alice, 'tax rate', '10%', 'wiki');
        $this->_triples->addTriple($carol, 'tax rate', '2%', 'wiki');
    }

    function assertQueryResult($query, $expectedResult, $message='') {
        $relations = $this->_triples->queryRelations($query);
        if ($relations === false) {
            $this->fail($message.' Query failed.');
        } else {
            $this->assertIteratorsEqual($relations, new ArrayIterator($expectedResult), $message);
            $relations->closeCursor();
        }
    }

    function assertIteratorsEqual($x, $y, $message='') {
        $message = $message?$message.': ':'';
        do {
            $this->assertEqual($x->valid(), $y->valid(), $message.'Number of result and expected rows differ: %s');
            $this->assertEqual($x->current(), $y->current(), $message.'Result row differs from expected one: %s');
            $x->next();
            $y->next();
        } while ($x->valid() || $y->valid());
    }
}
