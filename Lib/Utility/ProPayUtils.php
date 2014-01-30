<?php
/**
 * Utility functions (autoloader, etc.)
 *
 * @category Lib/Utility
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */

class ProPayUtils {

/**
 * autoloader for the generated SOAP Client code
 *
 * @param string $class
 */
	public static function generatedClientLoader($class) {
		$base = Configure::read('ProPay.generatedLib');
		if (empty($base)) {
			$base = CakePlugin::path('ProPay') . 'generated' . DS;
		}
		$class = str_replace('\\', DS, $class);
		if (file_exists($base . $class . '.php')) {
			include $base . $class . '.php';
		}
	}
} 