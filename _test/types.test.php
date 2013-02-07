<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/strata/helper/util.php');
class types_test extends Doku_UnitTestCase {

	function setup() {
		$this->_types = new helper_plugin_strata_util();
	}

	function testString() {
		$type = $this->_types->loadType('text'); 
		// Empty hint
		$s = $type->normalize('bob', '');
		$this->assertEqual($s, 'bob');
		// Empty hint
		$s = $type->normalize('Bob', '');
		$this->assertEqual($s, 'Bob');
		// Numerical hint
		$s = $type->normalize('Bob', 10);
		$this->assertEqual($s, 'Bob');
		// String hint
		$s = $type->normalize('Bob', 'master');
		$this->assertEqual($s, 'Bob');
		// Whitespace
		$s = $type->normalize('  Bob   ', '');
		$this->assertEqual($s, '  Bob   ');
		// Special characters
		$s = $type->normalize('Bob & Alice', '');
		$this->assertEqual($s, 'Bob & Alice');
		// Unicode
		$s = $type->normalize('Één ís één.', '');
		$this->assertEqual($s, 'Één ís één.');
	}

	function testPage() {
		$type = $this->_types->loadType('page'); 
		// Empty hint
		$s = $type->normalize('bob', '');
		$this->assertEqual($s, 'bob');
		// Empty hint
		$s = $type->normalize('Bob', '');
		$this->assertEqual($s, 'bob');
		// Numerical hint
		$s = $type->normalize('Bob', 10);
		$this->assertEqual($s, '10:bob');
		// String hint
		$s = $type->normalize('Bob', 'master');
		$this->assertEqual($s, 'master:bob');
		// Whitespace
		$s = $type->normalize('  Bob   ', '');
		$this->assertEqual($s, 'bob');
		// Special characters
		$s = $type->normalize('Bob & Alice', '');
		$this->assertEqual($s, 'bob_alice');
		// Unicode
		$s = $type->normalize('Één ís één.', '');
		$this->assertEqual($s, 'een_is_een');
		// Relative pathes
		$s = $type->normalize('..:.:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
		$s = $type->normalize('.:..:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
        // Fragments in url (link to namespace start)
		$s = $type->normalize(':#Bob', 'master:user');
		$this->assertEqual($s, 'start#bob');
		$s = $type->normalize('.:#Bob', 'master:user');
		$this->assertEqual($s, 'master:user:start#bob');
		$s = $type->normalize('..:#Bob', 'master:user');
		$this->assertEqual($s, 'master:start#bob');
	}

	function testPageWithID() {
		// Set ID
		global $ID;
		$this->assertEqual($ID, null); // Test whether the test suite is initialised as expected.
		$ID = 'an_id:sub:current_page';

		$type = $this->_types->loadType('page'); 
		// Empty hint
		$s = $type->normalize('bob', '');
		$this->assertEqual($s, 'an_id:sub:bob');
		// Empty hint
		$s = $type->normalize('Bob', '');
		$this->assertEqual($s, 'an_id:sub:bob');
		// Numerical hint
		$s = $type->normalize('Bob', 10);
		$this->assertEqual($s, '10:bob');
		// String hint
		$s = $type->normalize('Bob', 'master');
		$this->assertEqual($s, 'master:bob');
		// Whitespace
		$s = $type->normalize('  Bob   ', '');
		$this->assertEqual($s, 'an_id:sub:bob');
		// Special characters
		$s = $type->normalize('Bob & Alice', '');
		$this->assertEqual($s, 'an_id:sub:bob_alice');
		// Unicode
		$s = $type->normalize('Één ís één.', '');
		$this->assertEqual($s, 'an_id:sub:een_is_een');
		// Relative pathes w.r.t. given namespace
		$s = $type->normalize('..:.:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
		$s = $type->normalize('.:..:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
        // Fragments in url w.r.t. given namespace
		$s = $type->normalize('.:#Bob', 'master:user');
		$this->assertEqual($s, 'master:user:start#bob');
		$s = $type->normalize('..:#Bob', 'master:user');
		$this->assertEqual($s, 'master:start#bob');
		$s = $type->normalize('#Bob', 'master:user');
		$this->assertEqual($s, 'an_id:sub:current_page#bob');
		// Relative pathes w.r.t. ID
		$s = $type->normalize('..:.:Bob', '');
		$this->assertEqual($s, 'an_id:bob');
		$s = $type->normalize('.:..:Bob', '');
		$this->assertEqual($s, 'an_id:bob');
        // Fragments in url w.r.t. ID
		$s = $type->normalize('.:#Bob', '');
		$this->assertEqual($s, 'an_id:sub:start#bob');
		$s = $type->normalize('..:#Bob', '');
		$this->assertEqual($s, 'an_id:start#bob');
		$s = $type->normalize('#Bob', '');
		$this->assertEqual($s, 'an_id:sub:current_page#bob');
		$s = $type->normalize('Other Page#Bob', '');
		$this->assertEqual($s, 'an_id:sub:other_page#bob');

		// Restore global to avoid interference with other tests
		$ID = null;
	}

	function testRef() {
		$type = $this->_types->loadType('ref'); 
		// Empty hint
		$s = $type->normalize('bob', '');
		$this->assertEqual($s, 'bob');
		// Empty hint
		$s = $type->normalize('Bob', '');
		$this->assertEqual($s, 'bob');
		// Numerical hint
		$s = $type->normalize('Bob', 10);
		$this->assertEqual($s, '10:bob');
		// String hint
		$s = $type->normalize('Bob', 'master');
		$this->assertEqual($s, 'master:bob');
		// Whitespace
		$s = $type->normalize('  Bob   ', '');
		$this->assertEqual($s, 'bob');
		// Special characters
		$s = $type->normalize('Bob & Alice', '');
		$this->assertEqual($s, 'bob_alice');
		// Unicode
		$s = $type->normalize('Één ís één.', '');
		$this->assertEqual($s, 'een_is_een');
		// Relative pathes
		$s = $type->normalize('..:.:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
		$s = $type->normalize('.:..:Bob', 'master:user');
		$this->assertEqual($s, 'master:bob');
        // Fragments in url (link to namespace)
		$s = $type->normalize('.:#Bob', 'master:user');
		$this->assertEqual($s, 'master:user:start#bob');
		$s = $type->normalize('..:#Bob', 'master:user');
		$this->assertEqual($s, 'master:start#bob');
		$s = $type->normalize(':#Bob', 'master:user');
		$this->assertEqual($s, 'start#bob');
	}

	function testImage() {
		$type = $this->_types->loadType('image'); 
		// Empty hint
		$s = $type->normalize('bob.png', '');
		$this->assertEqual($s, 'bob.png');
		// Empty hint
		$s = $type->normalize('Bob.png', '');
		$this->assertEqual($s, 'bob.png');
		// Numerical hint
		$s = $type->normalize('Bob.png', 10);
		$this->assertEqual($s, 'bob.png');
		// Whitespace
		$s = $type->normalize('  Bob.png   ', '');
		$this->assertEqual($s, 'bob.png');
		// Special characters
		$s = $type->normalize('Bob & Alice.png', '');
		$this->assertEqual($s, 'bob_alice.png');
		// Unicode
		$s = $type->normalize('Één ís één.png', '');
		$this->assertEqual($s, 'een_is_een.png');
	}
}

