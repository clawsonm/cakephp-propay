<?php
/**
 * All ProPay Tests Test Suite
 *
 * @category Test
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */
class AllProPayTest extends CakeTestCase {

/**
 * define the test suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All ProPay test');

		$path = CakePlugin::path('ProPay') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);

		return $suite;
	}

}
