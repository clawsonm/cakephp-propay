<?php
/**
 * All Tests Test Suite
 *
 * @category Test
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */
class AllTestsTest extends CakeTestSuite {

/**
 * define the test suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Tests');
		$suite->addTestDirectoryRecursive(TESTS . 'Case');
		return $suite;
	}

}