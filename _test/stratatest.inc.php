<?php
require_once DOKU_INC.'lib/plugins/strata/helper/triples.php';

/**
 * Base test class for Strata plugin.
 */
class Strata_UnitTestCase extends DokuWikiTest {

	function setup() {
        $this->pluginsEnabled[] = 'strata';
        parent::setUp();

		// Setup a new database (uncomment the one to use)
		//$this->_triples = new helper_plugin_stratastorage_triples();
        $this->_triples = new helper_plugin_strata_triples();

		// Use SQLite (default)
		$this->_triples->_initialize('sqlite::memory:');

		// Use MySQL, which is set up with:
		// CREATE DATABASE strata_test;
		// GRANT ALL ON strata_test.* TO ''@localhost;
		//$this->_triples->initialize('mysql:dbname=strata_test');

		// Use PostgreSQL, which is set up with:
		// createuser -SDR strata
		// createdb -l "en_US.UTF-8" -E UTF8 -T template0 strata_test
		//$this->_triples->initialize('pgsql:dbname=strata_test;user=strata');

	}

	function teardown() {
		// Remove the database
		$this->_triples->_db->removeDatabase();
	}
}

