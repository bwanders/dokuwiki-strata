<?php
require_once('stratatest.inc.php');
class storage_test extends Strata_UnitTestCase {

	function testAdd() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice', 'wiki');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testAddArray() {
		$OK = $this->_triples->addTriples(array(array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice')), 'wiki');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testAddMulti() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice', 'wiki');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'knows', 'Carol', 'wiki');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki'),
			array('subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testSpecialChars() {
		$OK = $this->_triples->addTriple('*', 'select', '%', 'wiki');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('_', '(', '`', 'wiki');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple(';', '\'', '"', 'wiki');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('subject' => '*', 'predicate' => 'select', 'object' => '%', 'graph' => 'wiki'),
			array('subject' => '_', 'predicate' => '(', 'object' => '`', 'graph' => 'wiki'),
			array('subject' => ';', 'predicate' => '\'', 'object' => '"', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}
}

