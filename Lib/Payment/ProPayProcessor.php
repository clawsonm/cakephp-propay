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
 * charge the given card with the given data, and given amount
 *
 * @param array $data Card info, Customer info, Amount information
 *
 * @return boolean
 */
	public function chargeCard($data) {
		$status = $this->createPayer($data);

		if ($status) {
			$status = $this->createPaymentMethod($data);
		}

		if ($status) {
			$status = $this->processPaymentTransaction($data);
		}

		return $status;
	}

/**
 * Preauth the given card with the given data, and given amount
 *
 * @param array $data Card info, Customer info, Amount information
 *
 * @return boolean
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
 * @param array $payeeData payer data
 *
 * @return boolean
 */
	public function createPayer($payeeData) {
		$payerData = new PayerData(null, null, null, $payeeData['payerAccountName']);
		$createPayerWithData = new CreatePayerWithData($this->_ID, $payerData);
		$createPayerWithDataResponse = $this->_SPS->CreatePayerWithData($createPayerWithData);

		if ($createPayerWithDataResponse->CreatePayerWithDataResult->RequestResult->ResultCode == '00') {
			$this->payerAccountId = $createPayerWithDataResponse->CreatePayerWithDataResult->ExternalAccountID;
			$event = new CakeEvent(
				'ProPay.Payment.ProPay.createdPayer',
				$this,
				array(
					'payerAccountName' => $payeeData['payerAccountName'],
					'payerAccountId' => $this->payerAccountId
				)
			);
			CakeEventManager::instance()->dispatch($event);
			return true;
		} else {
			$this->latestRequestResult = $createPayerWithDataResponse->CreatePayerWithDataResult->RequestResult;
			return false;
		}
	}

/**
 * call the soap CreatePaymentMethod routine, and return data via events
 *
 * @param array $paymentData payment data
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
 * @param array $paymentData payment data
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
			null,
			$paymentData['invoice'],
			null,
			$paymentData['payerAccountId'],
			null
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

/**
 * call the soap processPaymentMethodTransaction routine, and return data via events
 *
 * @param array $paymentData payment data
 *
 * @throws InvalidArgumentException
 *
 * @return boolean
 */
	public function processPaymentTransaction($paymentData) {
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
			null,
			$paymentData['invoice'],
			null,
			$paymentData['payerAccountId'],
			null
		);

		$processPaymentMethodTransaction = new ProcessPaymentMethodTransaction($this->_ID, $transaction, $paymentData['paymentMethodId'], $paymentInfoOverrides);

		$processPaymentMethodTransactionResponse = $this->_SPS->ProcessPaymentMethodTransaction($processPaymentMethodTransaction);

		if ($processPaymentMethodTransactionResponse->ProcessPaymentMethodTransactionResult->RequestResult->ResultCode == '00') {
			$this->transactionId = $processPaymentMethodTransactionResponse->ProcessPaymentMethodTransactionResult->Transaction->TransactionId;
			$this->authCode = $processPaymentMethodTransactionResponse->ProcessPaymentMethodTransactionResult->Transaction->AuthorizationCode;
			$event = new CakeEvent(
				'ProPay.Payment.ProPay.processedTransaction',
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
			$this->latestRequestResult = $processPaymentMethodTransactionResponse->ProcessPaymentMethodTransactionResult->RequestResult;
			return false;
		}
	}

/**
 * call the soap GetTempToken routine, and return data via events
 *
 * @param string|integer $payerAccountName some kind of id that will be returned in the event
 *                                         with payerAccountId so they can be associated.
 * @param integer        $tokenDuration    duration of token
 * @return boolean
 */
	public function getTempToken($payerAccountName, $tokenDuration = 600) {
		$payerInformation = new PayerInformation(null, $payerAccountName);
		$tempTokenProperties = new TempTokenProperties($tokenDuration);
		$tempTokenRequest = new TempTokenRequest($this->_ID, $payerInformation, $tempTokenProperties);

		$getTempToken = new GetTempToken($tempTokenRequest);

		$getTempTokenResponse = $this->_SPS->GetTempToken($getTempToken);

		if ($getTempTokenResponse->GetTempTokenResult->RequestResult->ResultCode == '00') {
			$this->credentialId = $getTempTokenResponse->GetTempTokenResult->CredentialId;
			$this->payerAccountId = $getTempTokenResponse->GetTempTokenResult->PayerId;
			$this->tempToken = $getTempTokenResponse->GetTempTokenResult->TempToken;

			$event = new CakeEvent(
				'ProPay.Payment.ProPay.createdPayer',
				$this,
				array(
					'payerAccountId' => $this->payerAccountId,
					'payerAccountName' => $payerAccountName
				)
			);
			CakeEventManager::instance()->dispatch($event);
			$event2 = new CakeEvent(
				'ProPay.Payment.ProPay.receivedTempToken',
				$this,
				array(
					'credentialId' => $this->credentialId,
					'tempToken' => $this->tempToken
				)
			);
			CakeEventManager::instance()->dispatch($event2);
			return true;
		} else {
			$this->latestRequestResult = $getTempTokenResponse->GetTempTokenResult->RequestResult;
			return false;
		}
	}

}