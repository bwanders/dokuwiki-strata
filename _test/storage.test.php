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
		$this->_triples->_setupDatabase();
	}

	function testAdd() {
		$this->_triples->addTriple('Bob', 'knows', 'Alice');
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}

	function testAddArray() {
		$this->_triples->addTriples(array(array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice')));
		$data = $this->_triples->fetchTriples();
		$expected = array(
			array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki')
		);
		$this->assertEqual($data, $expected);
	}
}

