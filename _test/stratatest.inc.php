<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/triples.php');
class Strata_UnitTestCase extends Doku_UnitTestCase {

	function setup() {
		// Setup a new database
		$this->_triples = new helper_plugin_stratastorage_triples();
		$this->_triples->initialize('sqlite::memory:');
	}

	function teardown() {
	}
}

