<?php
require_once('stratatest.inc.php');
class storage_graphs_test extends Strata_UnitTestCase {

	function testGraphs() {
		$OK = $this->_triples->addTriple('Bob', 'knows', 'Alice', 'knowledgebase of bob');
		$this->assertTrue($OK);
		$OK = $this->_triples->addTriple('Alice', 'knows', 'Carol', 'knowledgebase of alice');
		$this->assertTrue($OK);

		$expected1 = array('subject' => 'Bob', 'predicate' => 'knows', 'object' => 'Alice', 'graph' => 'knowledgebase of bob');
		$expected2 = array('subject' => 'Alice', 'predicate' => 'knows', 'object' => 'Carol', 'graph' => 'knowledgebase of alice');

		// Retrieve the wiki graph
		$data = $this->_triples->fetchTriples(null, null, null, 'wiki');
		$this->assertEqual($data, array());

		// Retrieve Bobs graph
		$data = $this->_triples->fetchTriples(null, null, null, 'knowledgebase of bob');
		$this->assertEqual($data, array($expected1));

		// Retrieve Alices graph
		$data = $this->_triples->fetchTriples(null, null, null, 'knowledgebase of alice');
		$this->assertEqual($data, array($expected2));

		// Retrieve all graphs
		$data = $this->_triples->fetchTriples(null, null, null, null);
		$this->assertEqual($data, array($expected1, $expected2));
	}
}

