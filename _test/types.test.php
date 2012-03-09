<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/plugin.php');
require_once(DOKU_INC.'lib/plugins/stratastorage/helper/types.php');
class types_test extends Doku_UnitTestCase {

	function setup() {
		$this->_types = new helper_plugin_stratastorage_types();
	}

	function testString() {
		$type = $this->_types->loadType('string'); 
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

