<?php
require_once(DOKU_INC.'_test/lib/unittest.php');
class optional_group_test extends Doku_GroupTest {
    function optional_group_test() {
        $dir = dirname(__FILE__).'/';
        $this->addTestFile($dir . 'query_operators_numeric_optional.test.php');
        $this->addTestFile($dir . 'query_sort_optional.test.php');
    }
}

