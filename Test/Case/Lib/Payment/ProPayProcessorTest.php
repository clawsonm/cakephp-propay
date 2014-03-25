<?php
/**
 * ProPay Processor Test Case
 *
 * @category Test
 * @package  cakephp-propay
 * @author   Michael Clawson <macaronisoft@gmail.com>
 * @license  MIT License
 * @link     https://github.com/clawsonm/cakephp-propay
 */

App::uses('ProPayProcessor', 'ProPay.Lib/Payment');

class ProPayProcessorTest extends CakeTestCase {

	public function setup() {
		$this->soapClient = $this->getMock(
			'SPS',
			array(
				'CreatePayer',
				'CreatePaymentMethod',
				'AuthorizePaymentMethodTransaction',
				'ProcessPaymentMethodTransaction',
				'GetTempToken'
			),
			array(
				array(),
				'http://protectpaytest.propay.com/api/sps.svc?wsdl'
			)
		);
		$this->soapAuthID = new ID('asdf', 'asdf');
		$this->postData = array(
			'payerAccountName' => 'asdf',
			'address1' => '111 Brute Squad Lane',
			'city' => 'Provo',
			'state' => 'UT',
			'country' => 'USA',
			'zipCode' => '84604',
			'email' => 'test@test.com',
			'telephoneNumber' => '5555555555',
			'accountName' => 'Andre the Giant',
			'accountNumber' => '4111111111111111',
			'expirationDate' => '0914',
			'payerAccountId' => '1234',
			'paymentMethodType' => 'Visa',
			'paymentMethodName' => 'qwerty',
			'paymentMethodId' => '5678',
			'ccv' => '999',
			'amount' => 200.00,
			'currencyCode' => 'USD',
			'invoice' => 'zxcv'
		);
	}

	public function testPreAuthCard() {
		$PPP = $this->getMock(
			'ProPayProcessor',
			array(
				'createPayer',
				'createPaymentMethod',
				'authorizePaymentTransaction'
			)
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->expects($this->once())->method('createPayer')->will($this->returnValue(true));
		$PPP->expects($this->once())->method('createPaymentMethod')->will($this->returnValue(true));
		$PPP->expects($this->once())->method('authorizePaymentTransaction')->will($this->returnValue(true));

		$result = $PPP->preAuthCard(array());
		$this->assertTrue($result);
	}

	public function testChargeCard() {
		$PPP = $this->getMock(
			'ProPayProcessor',
			array(
				'createPayer',
				'createPaymentMethod',
				'processPaymentTransaction'
			)
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->expects($this->once())->method('createPayer')->will($this->returnValue(true));
		$PPP->expects($this->once())->method('createPaymentMethod')->will($this->returnValue(true));
		$PPP->expects($this->once())->method('processPaymentTransaction')->will($this->returnValue(true));

		$result = $PPP->chargeCard(array());
		$this->assertTrue($result);
	}

	public function testCreatePayerSuccess() {
		$ResultCode = new Result('00', 'success', '1234');
		$CreateAccountInformationResult = new CreateAccountInformationResult('1234', $ResultCode);
		$CreatePayerResponse = new CreatePayerResponse($CreateAccountInformationResult);
		$this->soapClient->expects($this->once())->method('CreatePayer')->will($this->returnValue($CreatePayerResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->createPayer($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->payerAccountId, '1234');
	}

	public function testCreatePayerFail() {
		$ResultCode = new Result('88', 'fail', '0000');
		$CreateAccountInformationResult = new CreateAccountInformationResult('0000', $ResultCode);
		$CreatePayerResponse = new CreatePayerResponse($CreateAccountInformationResult);
		$this->soapClient->expects($this->once())->method('CreatePayer')->will($this->returnValue($CreatePayerResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->createPayer($this->postData);
		$this->assertFalse($result);
		$this->assertEqual($PPP->latestRequestResult, $ResultCode);
	}

/**
 * test createPaymentMethod with no payerAccountId set in the data array or the class
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 */
	public function testCreatePaymentMethodNoPayerAccountId() {
		unset($this->postData['payerAccountId']);
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->createPaymentMethod($this->postData);
	}

	public function testCreatePaymentMethodSuccess() {
		$ResultCode = new Result('00', 'success', '1234');
		$CreatePaymentMethodResult = new CreatePaymentMethodResult('1234', $ResultCode);
		$CreatePaymentMethodResponse = new CreatePaymentMethodResponse($CreatePaymentMethodResult);
		$this->soapClient->expects($this->once())->method('CreatePaymentMethod')->will($this->returnValue($CreatePaymentMethodResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->createPaymentMethod($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->paymentMethodId, '1234');
	}

	public function testCreatePaymentMethodPayerAccountIdAsClassProperty() {
		$ResultCode = new Result('00', 'success', '5678');
		$CreatePaymentMethodResult = new CreatePaymentMethodResult('5678', $ResultCode);
		$CreatePaymentMethodResponse = new CreatePaymentMethodResponse($CreatePaymentMethodResult);
		$this->soapClient->expects($this->once())->method('CreatePaymentMethod')->will($this->returnValue($CreatePaymentMethodResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->payerAccountId = '1234';
		unset($this->postData['payerAccountId']);

		$result = $PPP->createPaymentMethod($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->paymentMethodId, '5678');
	}

	public function testCreatePaymentMethodFail() {
		$ResultCode = new Result('88', 'fail', '0000');
		$CreatePaymentMethodResult = new CreatePaymentMethodResult('0000', $ResultCode);
		$CreatePaymentMethodResponse = new CreatePaymentMethodResponse($CreatePaymentMethodResult);
		$this->soapClient->expects($this->once())->method('CreatePaymentMethod')->will($this->returnValue($CreatePaymentMethodResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->createPaymentMethod($this->postData);
		$this->assertFalse($result);
		$this->assertEqual($PPP->latestRequestResult, $ResultCode);
	}

/**
 * test authorizePaymentTransaction with no payerAccountId set in the data array or the class
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 */
	public function testAuthorizePaymentTransactionNoPayerAccountId() {
		unset($this->postData['payerAccountId']);
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->authorizePaymentTransaction($this->postData);
	}

/**
 * test authorizePaymentTransaction with no paymentMethodId set in the data array or the class
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 */
	public function testAuthorizePaymentTransactionNoPaymentMethodId() {
		unset($this->postData['paymentMethodId']);
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->authorizePaymentTransaction($this->postData);
	}

	public function testAuthorizePaymentTransactionSuccess() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$AuthorizePaymentMethodTransactionResponse = new AuthorizePaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('AuthorizePaymentMethodTransaction')->will($this->returnValue($AuthorizePaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->authorizePaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testAuthorizePaymentTransactionPayerAccountIdAsClassProperty() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$AuthorizePaymentMethodTransactionResponse = new AuthorizePaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('AuthorizePaymentMethodTransaction')->will($this->returnValue($AuthorizePaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->payerAccountId = '1234';
		unset($this->postData['payerAccountId']);

		$result = $PPP->authorizePaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testAuthorizePaymentTransactionPaymentMethodIdAsClassProperty() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$AuthorizePaymentMethodTransactionResponse = new AuthorizePaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('AuthorizePaymentMethodTransaction')->will($this->returnValue($AuthorizePaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->paymentMethodId = '5678';
		unset($this->postData['paymentMethodId']);

		$result = $PPP->authorizePaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testAuthorizePaymentTransactionFail() {
		$ResultCode = new Result('88', 'fail', '0000');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'0000',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'0000',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$AuthorizePaymentMethodTransactionResponse = new AuthorizePaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('AuthorizePaymentMethodTransaction')->will($this->returnValue($AuthorizePaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->authorizePaymentTransaction($this->postData);
		$this->assertFalse($result);
		$this->assertEqual($PPP->latestRequestResult, $ResultCode);
	}

/**
 * test processPaymentTransaction with no payerAccountId set in the data array or the class
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 */
	public function testProcessPaymentTransactionNoPayerAccountId() {
		unset($this->postData['payerAccountId']);
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->processPaymentTransaction($this->postData);
	}

/**
 * test processPaymentTransaction with no paymentMethodId set in the data array or the class
 *
 * @return void
 *
 * @expectedException InvalidArgumentException
 */
	public function testProcessPaymentTransactionNoPaymentMethodId() {
		unset($this->postData['paymentMethodId']);
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->processPaymentTransaction($this->postData);
	}

	public function testProcessPaymentTransactionSuccess() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$ProcessPaymentMethodTransactionResponse = new ProcessPaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('ProcessPaymentMethodTransaction')->will($this->returnValue($ProcessPaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->processPaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testProcessPaymentTransactionPayerAccountIdAsClassProperty() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$ProcessPaymentMethodTransactionResponse = new ProcessPaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('ProcessPaymentMethodTransaction')->will($this->returnValue($ProcessPaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->payerAccountId = '1234';
		unset($this->postData['payerAccountId']);

		$result = $PPP->processPaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testProcessPaymentTransactionPaymentMethodIdAsClassProperty() {
		$ResultCode = new Result('00', 'success', '4321');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'A1111',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'4321',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$ProcessPaymentMethodTransactionResponse = new ProcessPaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('ProcessPaymentMethodTransaction')->will($this->returnValue($ProcessPaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$PPP->paymentMethodId = '5678';
		unset($this->postData['paymentMethodId']);

		$result = $PPP->processPaymentTransaction($this->postData);
		$this->assertTrue($result);
		$this->assertEqual($PPP->transactionId, '4321');
		$this->assertEqual($PPP->authCode, 'A1111');
	}

	public function testProcessPaymentTransactionFail() {
		$ResultCode = new Result('88', 'fail', '0000');
		$TransactionInfo = new TransactionInformation(
			'Y',
			'0000',
			1.0,
			20000,
			'USD',
			$ResultCode,
			'randomstring',
			'0000',
			'success'
		);
		$TransactionResult = new TransactionResult($ResultCode, $TransactionInfo);
		$ProcessPaymentMethodTransactionResponse = new ProcessPaymentMethodTransactionResponse($TransactionResult);
		$this->soapClient->expects($this->once())->method('ProcessPaymentMethodTransaction')->will($this->returnValue($ProcessPaymentMethodTransactionResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->processPaymentTransaction($this->postData);
		$this->assertFalse($result);
		$this->assertEqual($PPP->latestRequestResult, $ResultCode);
	}

	public function testGetTempTokenSuccess() {
		$ResultCode = new Result('00', 'success', '1234');
		$TempTokenResult = new TempTokenResult('12345678', '1234', $ResultCode, 'asdfqwer1234qwer');
		$GetTempTokenResponse = new GetTempTokenResponse($TempTokenResult);
		$this->soapClient->expects($this->once())->method('GetTempToken')->will($this->returnValue($GetTempTokenResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->getTempToken('asdf');
		$this->assertTrue($result);
		$this->assertEqual($PPP->credentialId, '12345678');
		$this->assertEqual($PPP->tempToken, 'asdfqwer1234qwer');
		$this->assertEqual($PPP->payerAccountId, '1234');
	}

	public function testGetTempTokenFail() {
		$ResultCode = new Result('88', 'fail', '0000');
		$TempTokenResult = new TempTokenResult('12345678', '1234', $ResultCode, 'asdfqwer1234qwer');
		$GetTempTokenResponse = new GetTempTokenResponse($TempTokenResult);
		$this->soapClient->expects($this->once())->method('GetTempToken')->will($this->returnValue($GetTempTokenResponse));
		$PPP = $this->getMock(
			'ProPayProcessor',
			null
		);
		$PPP->initialize($this->soapClient, $this->soapAuthID);

		$result = $PPP->getTempToken('asdf');
		$this->assertFalse($result);
		$this->assertEqual($PPP->latestRequestResult, $ResultCode);
	}
}