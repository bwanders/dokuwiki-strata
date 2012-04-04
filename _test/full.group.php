<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
class full_group_test extends Doku_GroupTest {
    function full_group_test() {
        $dir = dirname(__FILE__).'/';
        $this->addTestFile($dir . 'types.test.php');
        $this->addTestFile($dir . 'storage.group.php');
        $this->addTestFile($dir . 'query.test.php');
        $this->addTestFile($dir . 'query_operators.test.php');
        $this->addTestFile($dir . 'query_operators_numeric.test.php');
        $this->addTestFile($dir . 'query_sort.test.php');
    }
}

