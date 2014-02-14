<?php
/**
 * ProPay Processor
 *
 * @category Lib/Payment
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */

App::uses('CakeEventManager', 'Event');
App::uses('CakeEvent', 'Event');

/**
 * Class ProPayProcessor
 *
 * @category Lib/Payment
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 *
 * @property SPS $_SPS
 * @property ID $_ID
 *
 */
class ProPayProcessor {

	protected $_SPS;

	protected $_ID;

	public $payerAccountId;

	public $paymentMethodId;

/**
 * Constructor
 *
 * @return ProPayProcessor
 */
	public function __construct() {
		$this->_SPS = new SPS();
		$this->_ID = new ID(Configure::read('ProPay.AuthenticationToken'), Configure::read('ProPay.BillerAccountId'));
	}

/**
 * Preauth the given card with the given data, and given amount
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

		if ($status) {
			$status = $this->authorizePaymentTransaction($data);
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
				'ProPay.Payment.ProPay.createdPayer',
				$this,
				array(
					'payerAccountName' => $payerData['payerAccountName'],
					'payerAccountId' => $this->payerAccountId
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
 * call the soap CreatePaymentMethod routine, and return data via events
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
			$paymentData['country'],
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

		$createPaymentMethodResponse = $this->_SPS->createPaymentMethod(new CreatePaymentMethod($this->_ID, $paymentMethodInfo));

		if ($createPaymentMethodResponse->CreatePaymentMethodResult->RequestResult->ResultCode == '00') {
			$this->paymentMethodId = $createPaymentMethodResponse->CreatePaymentMethodResult->PaymentMethodId;
			$event = new CakeEvent(
				'ProPay.Payment.ProPay.createdPaymentMethod',
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

/**
 * call the soap AuthorizePaymentMethodTransaction routine, and return data via events
 *
 * @param $paymentData
 *
 * @return boolean
 */
	public function authorizePaymentTransaction($paymentData) {
		if (!isset($paymentData['payerAccountId'])) {
			$paymentData['payerAccountId'] = $this->payerAccountId;
		}
		if (!isset($paymentData['paymentMethodId'])) {
			$paymentData['paymentMethodId'] = $this->paymentMethodId;
		}

		$creditCardOverrides = new CreditCardOverrides(null, $paymentData['ccv'], null, null);
		$paymentInfoOverrides = new PaymentInfoOverrides(null, $creditCardOverrides, null);
		$transaction = new Transaction(
			$paymentData['amount'] * 100,
			null,
			null,
			$paymentData['currencyCode'],
			$paymentData['invoice'],
			null,
			$paymentData['payerAccountId']
		);

		$authorizePaymentMethodTransaction = new AuthorizePaymentMethodTransaction($this->_ID, $transaction, $paymentData['paymentMethodId'], $paymentInfoOverrides);

		$authorizePaymentMethodTransactionResponse = $this->_SPS->AuthorizePaymentMethodTransaction($authorizePaymentMethodTransaction);

		if ($authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->RequestResult->ResultCode == '00') {
			$transactionId = $authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->Transaction->TransactionId;
			$authCode = $authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->Transaction->AuthorizationCode;
			$event = new CakeEvent(
				'ProPay.Payment.ProPay.authorizedTransaction',
				$this,
				array (
					'invoice' => $paymentData['invoice'],
					'transactionId' => $transactionId,
					'authCode' => $authCode
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			debug($authorizePaymentMethodTransactionResponse);
			return false;
		}
	}

} 