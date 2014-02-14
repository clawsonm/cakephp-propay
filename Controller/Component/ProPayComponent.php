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

	public $payerAccountId;

	public $paymentMethodId;

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

		if ($status) {
			$status = $this->createPaymentMethod($data);
		}

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
		$createPayer = new CreatePayer($this->_ID, $payerData['payerAccountName']);
		$createPayerResponse = $this->_SPS->CreatePayer($createPayer);

		if ($createPayerResponse->CreatePayerResult->RequestResult->ResultCode == '00') {
			$this->payerAccountId = $createPayerResponse->CreatePayerResult->ExternalAccountID;
			$event = new CakeEvent(
				'ProPay.Component.ProPay.createdPayer',
				$this,
				array(
					'payerAccountName' => $payerData['payerAccountName'],
					'payerAccountId' => $payerAccountId
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			debug($createPayerResponse);
			return false;
		}
	}

/**
 * call the soap createPaymentMethod routine, and return data via events
 *
 * @param $paymentData
 *
 * @return boolean
 */
	public function createPaymentMethod($paymentData) {
		if (!isset($paymentData['payerAccountId'])) {
			$paymentData['payerAccountId'] = $this->payerAccountId;
		}
		$billingInfo = new Billing(
			$paymentData['address1'],
			'',
			'',
			$paymentData['city'],
			$paymentData['countryCode'],
			$paymentData['email'],
			$paymentData['state'],
			$paymentData['telephoneNumber'],
			$paymentData['zipCode']
		);

		$paymentMethodInfo = new PaymentMethodAdd(
			'',
			$paymentData['accountName'],
			$paymentData['accountNumber'],
			'',
			$billingInfo,
			'',
			'SAVENEW',
			$paymentData['expirationDate'],
			$paymentData['payerAccountId'],
			$paymentData['paymentMethodType'],
			0,
			false
		);

		$createPaymentMethodResponse = $this->_SPS->createPaymentMethod($this->_ID, $paymentMethodInfo);

		if ($createPaymentMethodResponse->CreatePaymentMethodResult->RequestResult->ResultCode == '00') {
			$this->paymentMethodId = $createPaymentMethodResponse->CreatePaymentMethodResult->PaymentMethodId;
			$event = new CakeEvent(
				'ProPay.Component.ProPay.createdPaymentMethod',
				$this,
				array (
					'paymentMethodName' => $paymentData['paymentMethodName'],
					'paymentMethodId' => $this->paymentMethodId
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			debug($createPaymentMethodResponse);
			return false;
		}
	}

} 