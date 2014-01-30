<?php
/**
 * Bootstrap the plugin
 *
 * @category Config
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */

App::uses('ProPayUtils', 'ProPay.Lib/Utility');

spl_autoload_register(__NAMESPACE__ . '\ProPayUtils::generatedClientLoader');