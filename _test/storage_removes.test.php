<?php
require_once('stratatest.inc.php');
class storage_removes_test extends Strata_UnitTestCase {

	function setup() {
		parent::setup();
		// Fill database
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice', 'wiki');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'knows', 'Carol', 'wiki');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'dislikes', 'Carol', 'wiki');
		$this->assertTrue($OK);

		$this->expected1 = array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki');
		$this->expected2 = array('subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'wiki');
		$this->expected3 = array('subject' => 'Alice', 'predicate' => 'dislikes', 'object' => 'Carol', 'graph' => 'wiki');

		$data = $this->_triples->fetchTriples();
		$this->assertEqual($data, array($this->expected1, $this->expected2, $this->expected3));
	}

	function testRemove() {
		// Remove all
		$this->_triples->removeTriples();
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}

	function testRemoveBySubject() {
		$this->_triples->removeTriples('Bob');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected2, $this->expected3));
	
		$this->_triples->removeTriples('Alice');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}

	function testRemoveByPredicate() {
		$this->_triples->removeTriples(null, 'knows');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected3));
	
		$this->_triples->removeTriples(null, 'dislikes');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}

	function testRemoveByObject() {
		$this->_triples->removeTriples(null, null, 'Alice');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected2, $this->expected3));
	
		$this->_triples->removeTriples(null, null, 'Carol');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}

	function testRemoveBySubjectAndPredicate() {
		$this->_triples->removeTriples('Alice', 'knows');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected1, $this->expected3));
	}

	function testRemoveByPredicateAndObject() {
		$this->_triples->removeTriples(null, 'knows', 'Carol');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected1, $this->expected3));
	}

	function testRemoveCaseInsensitive() {
		$this->_triples->removeTriples('bob');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected2, $this->expected3));

		$this->_triples->removeTriples(null, 'Knows');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($this->expected3));

		$this->_triples->removeTriples(null, null, 'carol');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}
}

