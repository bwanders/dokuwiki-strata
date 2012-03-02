<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/triples.php');
class storage_test extends Doku_UnitTestCase {

	function setup() {
		// Setup a new in-memory table
		$this->_triples = new helper_plugin_stratastorage_triples();
		$this->_triples->initialize('sqlite::memory:');
	}

	function testAdd() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testAddArray() {
		$OK = $this->_triples->addTriples(array(array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice')));
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testAddMulti() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'knows', 'Carol');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki'),
			array('eid' => 2, 'subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testSpecialChars() {
		$OK = $this->_triples->addTriple('*', 'select', '%');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('_', '(', '`');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple(';', '\'', '"');
		$this->assertTrue($OK);
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => '*', 'predicate' => 'select', 'object' => '%', 'graph' => 'wiki'),
			array('eid' => 2, 'subject' => '_', 'predicate' => '(', 'object' => '`', 'graph' => 'wiki'),
			array('eid' => 3, 'subject' => ';', 'predicate' => '\'', 'object' => '"', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}
}

