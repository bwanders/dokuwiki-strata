<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/triples.php');
class storage_removes_test extends Doku_UnitTestCase {

	function setup() {
		// Setup a new in-memory table
		$this->_triples = new helper_plugin_stratastorage_triples();
		$this->_triples->initialize('sqlite::memory:');
	}

	function testRemove() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'knows', 'Carol');
		$this->assertTrue($OK);

		$expected1 = array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki');
		$expected2 = array('eid' => 2, 'subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'wiki');

		$data = $this->_triples->fetchTriples();
		$this->assertEqual($data, array($expected1, $expected2));

		// Remove all
		$this->_triples->removeTriples();
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}

	function testRemoveBySubject() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice');
		$this->assertTrue($OK);
		$OK =$this->_triples->addTriple('Alice', 'knows', 'Carol');
		$this->assertTrue($OK);

		$expected1 = array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'wiki');
		$expected2 = array('eid' => 2, 'subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'wiki');

		$data = $this->_triples->fetchTriples();
		$this->assertEqual($data, array($expected1, $expected2));
	
		// Remove Bob
		$this->_triples->removeTriples('Bob');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array($expected2));
	
		// Remove Alice
		$this->_triples->removeTriples('Alice');
		$data = $this->_triples->fetchTriples();
                $this->assertEqual($data, array());
	}
}

