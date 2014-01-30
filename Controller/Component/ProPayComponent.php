<?php
/**
 * Utility functions (autoloader, etc.)
 *
 * @category Controller/Component
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */

namespace Component;

App::uses('Component', 'Controller');

class ProPayComponent extends Component {

/**
 * preauth the given card with the given data, and given amount
 *
 * @param array $data Card info, Customer info, Amount information
 *
 * @return array
 */
	public function preAuthCard($data) {
		return array('success' => true, 'auth_code' => 'test');
	}

} 