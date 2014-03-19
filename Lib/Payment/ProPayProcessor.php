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

/**
 * @var SPS $_SPS
 */
	protected $_SPS = null;

/**
 * @var ID $_ID
 */
	protected $_ID = null;

/**
 * @var string $payerAccountId
 */
	public $payerAccountId = null;

/**
 * @var string $paymentMethodId
 */
	public $paymentMethodId = null;

/**
 * @var Result $latestRequestResult
 */
	public $latestRequestResult = null;

/**
 * Constructor
 *
 * @return ProPayProcessor
 */
	public function __construct() {
		$url = 'http://protectpaytest.propay.com/api/sps.svc?wsdl';
		if (Configure::check('ProPay.wsdlUrl')) {
			$url = Configure::read('ProPay.wsdlUrl');
		}
		$this->initialize(
			new SPS(array (), $url),
			new ID(Configure::read('ProPay.authenticationToken'), Configure::read('ProPay.billerAccountId'))
		);
	}

/**
 * initialization function for setting protected properties for easier testing
 *
 * @param SPS $soapClient a configured instance of SPS the soap client
 * @param ID $soapAuthId a configured instance of ID class for authentication with web service
 *
 * @return void
 */
	public function initialize(SPS $soapClient, ID $soapAuthId) {
		$this->_SPS = $soapClient;
		$this->_ID = $soapAuthId;
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
			$this->latestRequestResult = $createPayerResponse->CreatePayerResult->RequestResult;
			return false;
		}
	}

/**
 * call the soap CreatePaymentMethod routine, and return data via events
 *
 * @param $paymentData
 *
 * @throws InvalidArgumentException
 *
 * @return boolean
 */
	public function createPaymentMethod($paymentData) {
		if (!isset($paymentData['payerAccountId'])) {
			$paymentData['payerAccountId'] = $this->payerAccountId;
		}
		if (!isset($paymentData['payerAccountId'])) {
			throw new InvalidArgumentException('Payer Account Id is required. Either set it on the object or pass it in.');
		}

		$billingInfo = new Billing(
			$paymentData['address1'],
			null,
			null,
			$paymentData['city'],
			$paymentData['country'],
			$paymentData['email'],
			$paymentData['state'],
			$paymentData['telephoneNumber'],
			$paymentData['zipCode']
		);

		$paymentMethodInfo = new PaymentMethodAdd(
			null,
			$paymentData['accountName'],
			$paymentData['accountNumber'],
			null,
			$billingInfo,
			$paymentData['paymentMethodType'] . ' ending in ' . substr($paymentData['accountNumber'], -4),
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
			$this->latestRequestResult = $createPaymentMethodResponse->CreatePaymentMethodResult->RequestResult;
			return false;
		}
	}

/**
 * call the soap AuthorizePaymentMethodTransaction routine, and return data via events
 *
 * @param $paymentData
 *
 * @throws InvalidArgumentException
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
		if (!isset($paymentData['payerAccountId'])) {
			throw new InvalidArgumentException('Payer Account Id is required. Either set it on the object or pass it in.');
		}
		if (!isset($paymentData['paymentMethodId'])) {
			throw new InvalidArgumentException('Payment Method Id is required. Either set it on the object or pass it in.');
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
			$this->transactionId = $authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->Transaction->TransactionId;
			$this->authCode = $authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->Transaction->AuthorizationCode;
			$event = new CakeEvent(
				'ProPay.Payment.ProPay.authorizedTransaction',
				$this,
				array (
					'invoice' => $paymentData['invoice'],
					'transactionId' => $this->transactionId,
					'authCode' => $this->authCode
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			$this->latestRequestResult = $authorizePaymentMethodTransactionResponse->AuthorizePaymentMethodTransactionResult->RequestResult;
			return false;
		}
	}

} 