<?php

declare(strict_types=1);

namespace slkj\unionpay\test;

use slkj\unionpay\DataSaverHandler;
use slkj\unionpay\Test\TestDataSaverHandler;
use slkj\unionpay\DESCryptor;
use slkj\unionpay\UnionBusinessCode;
use slkj\unionpay\UnionDataType;
use slkj\unionpay\UnionLogger;
use slkj\unionpay\UnionPayment;
use slkj\unionpay\UnionResult;
use slkj\unionpay\Utilities;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class UnionPayFeaturesTest
 * @package slkj\unionpay\test
 */
// class UnionPayFeaturesTest extends BaseTestCase // derived test class cannot work in this case
class UnionPayFeaturesTest extends TestCase {
    const SECRET_KEY = '1234567890ABCDEF87654321';
    const API_URL = 'https://www.domain.com/';

    const CRLF = "\r\n";

    /**
     * <project-folder>/files/
     * @var string
     */
    private $projectFilesPath = '';

    public function testHelloTest() {
        $this->logFunctionName(__FUNCTION__);

        $message = 'Hello-Test';
        log2file($message);
        $this->assertTrue(strtolower($message) === 'hello-test');
    }

    public function testCheckPublicKeys() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKey =  $appInfo = $payInfo = null;

        $pay = new UnionPayment($key, $api, $pubKey, $bizKey, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance......');
        $this->assertEquals($key, $pay->getSecretKey());
        $this->assertEquals($api, $pay->getApiUrl());
    }

    public function testCheckBusinessKeys() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKey = $this->getBizKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $payInfo = null;

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance!');
        $this->assertEquals($bizKey['temp_key'], $pay->bizKeys->tempKey);
        $this->assertEquals($bizKey['mac_key'], $pay->bizKeys->macKey);
        $this->assertEquals($bizKey['session_key'], $pay->bizKeys->sessionKey);
        $this->assertEquals($bizKey['pin_key'], $pay->bizKeys->pinKey);
        $this->assertEquals($bizKey['track_key'], $pay->bizKeys->trackKey);
        // $this->assertEquals($bizKey['expire_time'], $pay->bizKeys->expireTime);
    }

    public function testCheckAppInfos() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = null;

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance!');
        $this->assertEquals($appInfo['factory_code'], $pay->appInfos->factoryCode);
        $this->assertEquals($appInfo['product_model'], $pay->appInfos->productModel);
        $this->assertEquals($appInfo['merchant_code'], $pay->appInfos->merchantCode);
        $this->assertEquals($appInfo['terminal_code'], $pay->appInfos->terminalCode);
        $this->assertEquals($appInfo['package_name'], $pay->appInfos->packageName);
        $this->assertEquals($appInfo['app_name'], $pay->appInfos->appName);
        $this->assertEquals($appInfo['app_version'], $pay->appInfos->appVersion);
        $this->assertEquals($appInfo['transaction_mode'], $pay->appInfos->transactioMode);
        $this->assertEquals($appInfo['app_merchant_no'], $pay->appInfos->appMerchantNo);
        $this->assertEquals($appInfo['app_terminal_no'], $pay->appInfos->appTerminalNo);
    }

    public function testCheckPayInfos() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance!');
        $this->assertEquals($payInfo['payer_id'], $pay->payInfos->payerId);
        $this->assertEquals($payInfo['payer_name'], $pay->payInfos->payerName);
        $this->assertEquals($payInfo['payer_account'], $pay->payInfos->payerAccount);
        $this->assertEquals($payInfo['deal_id'], $pay->payInfos->dealId);
        $this->assertEquals($payInfo['topay_price'], $pay->payInfos->topayPrice);

        $this->assertEquals($payInfo['payee_id'], $pay->payInfos->payeeId);
        $this->assertEquals($payInfo['payee_name'], $pay->payInfos->payeeName);
        $this->assertEquals($payInfo['payee_account'], $pay->payInfos->payeeAccount);
        $this->assertEquals($payInfo['payee_idcard'], $pay->payInfos->payeeIdcard);
        $this->assertEquals($payInfo['receiver'], $pay->payInfos->receiver);
    }

    public function testCheckPayInfosReceiverIsSame() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance!');
        $this->assertEquals($payInfo['payee_name'], $pay->payInfos->payeeName);
        $this->assertEquals($payInfo['receiver'], $pay->payInfos->receiver);
        $this->assertEquals($pay->payInfos->payeeName, $pay->payInfos->receiver);
    }

    public function testCheckPayInfosReceiverIsNotSame() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo);
        $pay->log('Created UnionPayment instance!');
        $this->assertEquals($payInfo['payee_name'], $pay->payInfos->payeeName);
        $this->assertEquals($payInfo['receiver'], $pay->payInfos->receiver);
        $this->assertNotEquals($pay->payInfos->payeeName, strtoupper($pay->payInfos->receiver));
    }

    public function testCheckDataSaverHandlerIsNull() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();
        $saver = null;

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->log('Created UnionPayment instance!');
        try {
            $pay->saver->save(UnionDataType::SaveTempKeyData, UnionBusinessCode::QUERY_TRANSACTION_STATUS, $pay->getSequence(), ['temp_key' => Utilities::makeTempKey()]);
        } catch (Exception $e) {
            $this->assertSame($e->getMessage(), DataSaverHandler::ERROR_MESSAGE);
        }
    }

    public function testCheckDataSaverHandlerIsNotNull() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();
        $saver = new TestDataSaverHandler();

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->log('Created UnionPayment instance!');
        try {
            $saver->save(UnionDataType::SaveTempKeyData, UnionBusinessCode::QUERY_TRANSACTION_STATUS, $pay->getSequence(), ['temp_key' => Utilities::makeTempKey()], 'without-origin—strings');
        } catch (Exception $e) {
            $this->assertSame($e->getMessage(), DataSaverHandler::ERROR_MESSAGE);
        }
        $this->assertSame(1, 1);
    }

    public function testPay2Receiver() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption();
        $appInfo = $this->getAppInfos();
        $payInfo = $this->getPayInfos();
        $saver = new TestDataSaverHandler();
        $this->assertTrue($saver != null);

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->errorRetries = 1;
        $pay->log('Created UnionPayment instance!');
        $result = $pay->payToReceiver();
        $code = $result['code'];
        $this->assertSame(true, UnionResult::isSuccessCode($code));
    }

    public function testResetPlatformKeys() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption(-7);
        $appInfo = $this->getAppInfos();
        $payInfo = null;
        $saver = new TestDataSaverHandler();
        $this->assertTrue($saver != null);

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->errorRetries = 1;
        $pay->log('Created UnionPayment instance!');
        $result = $pay->resetPlatformKeys();
        $code = $result['code'];
        $this->assertSame(true, UnionResult::isSuccessCode($code));
    }

    public function testQueryTransferDealStatus() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption(-7);
        $appInfo = $this->getAppInfos();
        $payInfo = null;
        $saver = new TestDataSaverHandler();
        $this->assertTrue($saver != null);

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->errorRetries = 1;
        $pay->log('Created UnionPayment instance!');
        $result = $pay->queryTransferDealStatus(Utilities::makeTempKey()); // test-origin-code
        $code = $result['code'];
        $this->assertSame(true, UnionResult::isSuccessCode($code));
    }

    public function testQueryAccountBalance() {
        $this->logFunctionName(__FUNCTION__);

        $key = self::SECRET_KEY;
        $api = self::API_URL;
        $pubKey = $this->getPubKeys();
        $bizKeyEncrypted = $this->getBizKeysWithDESEncryption(-7);
        $appInfo = $this->getAppInfos();
        $payInfo = null;
        $saver = new TestDataSaverHandler();
        $this->assertTrue($saver != null);

        $pay = new UnionPayment($key, $api, $pubKey, $bizKeyEncrypted, $appInfo, $payInfo, $saver);
        $pay->errorRetries = 1;
        $pay->log('Created UnionPayment instance!');
        $result = $pay->queryAccountBalance();
        $code = $result['code'];
        $this->assertSame(true, UnionResult::isSuccessCode($code));
    }

    private function getPubKeys() {
        $path = $this->projectFilesPath;
        // generate file content to files/union_public_key.pem
        return [
            'key_module' =>     'F1DF6BDF6FC382817F459481985DFEA4F1FC73BF0FC6A0DD13B964413F51991F2B9FB6229B988B8F307988AD09A2C92CC86BD1371C775F70F94479855AFDFBF69AF93D4DBF01FFB3BC1BB00DBB9AA5095FC98F77A2938660ABB792CB89243BAD3943F0D00BAB7DA298BBC411FD18267BF2DD0C82A479CF3FBFBD1D81935F91FADDC2F4380BF39DFF2C4421F9F2D5D93D552465D6D6FC8569757413255948499514521B1F4AD35FBF10CB70A618F9B0F64DD11D1BD66C319DDB83437CFCDA52D01F1C5B3FB83FDCC718556504DBC20662495FDBB4C683973F221D66CF825B26D5415712FAD3160F5672B0F793FCF3CC0436D55A629062CBBC813F373DDFD6CB33',
            'key_index' =>      'F12E03A104B1051C061D071D',
            'key_file' =>       $path . 'union_public_key.pem',
            'ssl_cert_file' =>  $path . 'ssl_cert_file.txt',
            'ssl_key_file' =>   $path . 'ssl_key_file.txt',
        ];
    }

    private function getBizKeysWithDESEncryption($expireDays = 7) {
        $bizKeys = $this->getBizKeys();
        $key = self::SECRET_KEY;
        return [
            'temp_key' =>       DESCryptor::encrypt($bizKeys['temp_key'], $key),
            'mac_key' =>        DESCryptor::encrypt($bizKeys['mac_key'], $key),
            'session_key' =>    DESCryptor::encrypt($bizKeys['session_key'], $key),
            'pin_key' =>        DESCryptor::encrypt($bizKeys['pin_key'], $key),
            'track_key' =>      DESCryptor::encrypt($bizKeys['track_key'], $key),
            'expire_time' =>    Utilities::getPhpDateTime($expireDays),
        ];
    }

    private function getBizKeys() {
        return [
            'temp_key' =>       '2022072413324620220724133246150914', // Utilities::makeTempKey(),
            'mac_key' =>        'BF12478CDB123A15AC94235460CEA8EE',
            'session_key' =>    '5A2F549ABA5421DE13C0A6BE1C09D9FF',
            'pin_key' =>        'B085D0370096D1BCC895C97C8111D82D',
            'track_key' =>      '5146C1B4E97E8464792DF993E4B2B6FA',
            'expire_time' =>    Utilities::getPhpDateTime(7),
        ];
    }


    private function getAppInfos() {
        return [
            'factory_code' => '1001',
            'product_model' => '1002',
            'merchant_code' => '1003',
            'terminal_code' => '1004',
            'package_name' => '1005',
            'app_name' => '1006',
            'app_version' => '1007',
            'transaction_mode' => '1008',
            'app_merchant_no' => '1009',
            'app_terminal_no' => '1010'
        ];
    }

    private function getPayInfos() {
        return [
            'payer_id' => '2001',
            'payer_name' => 'Eric Xu',
            'payer_account' => '20012001',
            'deal_id' => '2002',
            'topay_price' => '12.34',
            'payee_id' => '2003',
            'payee_name' => 'Peter Xu',
            'payee_account' => '20032003',
            'payee_idcard' => '200320032003',
            'receiver' => 'Peter Xu'
        ];
    }


    public static function autoLoadClass($className) {
        $path = realpath(__DIR__ . '/../../main/php/');
        $classes = explode("\\", $className);
        $className = array_reverse($classes)[0];
        $file = "{$path}/{$className}.php";
        if (file_exists($file)) {
            echo (self::CRLF . "[autoLoadClass] Auto loading class: {$file}" . self::CRLF);
            include_once($file);
        } else {
            echo (self::CRLF . "[autoLoadClass] File Not exists: {$file}" . self::CRLF);
        }
    }

    protected function setUp() {
        parent::setUp();
        echo self::CRLF . "TestCase: SetUp..." . self::CRLF;

        // $_ENV[UnionLogger::ENV_SHOW_FILE_NAME] = 1;
        // $_ENV[UnionLogger::ENV_ECHO_MESSAGE] = 0;

        // add vendor loader
        $path = realpath(__DIR__ . '/../../../vendor/');
        $file = $path . '/autoload.php';
        echo self::CRLF . "Vendor Loader File: {$file}" . self::CRLF;
        include_once($file);

        // register unionpay loader
        include_once(__DIR__ . '/../../../bootstrap.php');
        $path = realpath(__DIR__ . '/../../main/php/');
        echo self::CRLF . "The source codes path of unionpay-php project is: $path" . self::CRLF;
        set_include_path($path);
        spl_autoload_register(["slkj\\unionpay\\test\\UnionPayFeaturesTest", 'autoLoadClass']);

        // project path
        $path = realpath(__DIR__ . '/../../../files/');
        $this->projectFilesPath = $path . '/';

        // test classes
        include_once('TestDataSaverHandler.php');
    }

    protected function tearDown() {
        parent::tearDown(); // TODO: Change the autogenerated stub

        echo self::CRLF . "TestCase: TearDown." . self::CRLF;
    }

    private function logFunctionName($function) {
        echo (self::CRLF . "TestCase Name is: {$function}" . self::CRLF);
    }
}
