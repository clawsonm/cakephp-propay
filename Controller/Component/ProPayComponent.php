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

App::uses('Component', 'Controller');
App::uses('CakeEventManager', 'Event');

/**
 * Class ProPayComponent
 *
 * @category Controller/Component
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */
class ProPayComponent extends Component {

	protected $_SPS;

	protected $_ID;

	public function initialize(Controller $controller) {
		$this->_SPS = new SPS();
		$this->_ID = new ID(Configure::read('ProPay.AuthenticationToken'), Configure::read('ProPay.BillerAccountId'));
	}

/**
 * preauth the given card with the given data, and given amount
 *
 * @param array $data Card info, Customer info, Amount information
 *
 * @return array
 */
	public function preAuthCard($data) {
		$status = $this->createPayer($data);

		return $status;
	}

/**
 * call the soap createPayer routine, and return data via events
 *
 * @param $payerData
 *
 * @return boolean
 */
	public function createPayer($payerData) {
		$createPayer = new CreatePayer($this->_ID, $payerData['id']);
		$createPayerResponse = $this->_SPS->CreatePayer($createPayer);

		if ($createPayerResponse->CreatePayerResult->RequestResult->ResultCode == '00') {
			$payerId = $createPayerResponse->CreatePayerResult->ExternalAccountID;
			$event = new CakeEvent(
				'ProPay.Component.PrePay.createdPayer',
				$this,
				array(
					'id' => $payerData['id'],
					'payerId' => $payerId
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			debug($createPayerResponse);
			return false;
		}
	}

} 