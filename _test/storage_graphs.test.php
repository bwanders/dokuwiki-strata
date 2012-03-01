<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/triples.php');
class storage_graphs_test extends Doku_UnitTestCase {

	function setup() {
		// Setup a new in-memory table
		$this->_triples = new helper_plugin_stratastorage_triples();
		$this->_triples->initialize('sqlite::memory:');
		$this->_triples->_setupDatabase();
	}

	function testGraphs() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice', 'knowledgebase of bob');
		$this->assertTrue($OK);
		$OK = $this->_triples->addTriple('Alice', 'knows', 'Carol', 'knowledgebase of alice');
		$this->assertTrue($OK);

		$expected1 = array('eid' => 1, 'subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'knowledgebase of bob');
		$expected2 = array('eid' => 2, 'subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'knowledgebase of alice');

		// Retrieve the wiki graph
		$data = $this->_triples->fetchTriples();
		$this->assertEqual($data, array());

		// Retrieve Bobs graph
		$data = $this->_triples->fetchTriples(null, null, null, 'knowledgebase of bob');
		$this->assertEqual($data, array($expected1));

		// Retrieve Alices graph
		$data = $this->_triples->fetchTriples(null, null, null, 'knowledgebase of alice');
		$this->assertEqual($data, array($expected2));

		// Retrieve all graphs
		$data = $this->_triples->fetchTriples(null, null, null, '%');
		$this->assertEqual($data, array($expected1, $expected2));
	}
}

