<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
class storage_group_test extends Doku_GroupTest {
	function storage_group_test() {
		$dir = dirname(__FILE__).'/';
		$this->addTestFile($dir . 'storage.test.php');
		$this->addTestFile($dir . 'storage_graphs.test.php');
		$this->addTestFile($dir . 'storage_removes.test.php');
	}
}

