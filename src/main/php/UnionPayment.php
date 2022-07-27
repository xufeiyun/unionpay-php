<?php

namespace slkj\unionpay;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * UnionPayment class
 * @package slkj\unionpay
 */
class UnionPayment
{
    /**
     * SECRET_KEY
     * The secret-key for encrypting kinds of keys, 24 characters
     */
    public $secretKey = '************************';

    /**
     * 交易失败时重复查询次数
     * @var int
     */
    public $errorRetries = 5;

    /**
     * union-pay-api-url
     * @var string
     */
    public $apiUrl = '';

    /**
     * http request object
     * @var HttpRequest
     */
    public $http;

    /**
     * transaction sequence
     * @var string
     */
    public $sequence = '';

    /**
     * save handler implementation to save business data by caller
     * @var DataSaverInterface
     */
    public $saver;

    /**
     * public keys of union-pay used to encrypt business data
     * @var UnionPublicKey
     */
    public $pubKeys;

    /**
     * business keys of api-side defined
     * @var UnionBusinessKey
     */
    public $bizKeys;

    /**
     * payer && payee infos of business-side defined
     * @var UnionPayUserInfo
     */
    public $payInfos;

    /**
     * application information (eg: the APP Terminal information)
     * @var UnionAppBaseInfo
     */
    public $appInfos;

    /**
     * encrypt or decrypt union-biz-data
     * @var UnionCryptor
     */
    public $cryptor;

    /**
     * [OriTrxIndCd]
     * [internal-use] the original transaction id, maybe order-code or order-no
     * @var string
     */
    public $originTransactionCode = '';

    public function log($message)
    {
        $message = empty($message) ? $this->sequence : ($this->sequence . " - " . $message);
        log2file($message);
    }

    public function getSecretKey() {
        return $this->secretKey;
    }

    public function getApiUrl() {
        return $this->apiUrl;
    }

    public function getSequence() {
        return $this->sequence;
    }

    /**
     * UnionPayment constructor.
     * 目前仅 SP004 需要payInfo这个信息。
     * @param string $secretKey user-defined-key 24-chars belongs to [0~9a-zA-Z]
     * @param string $apiUrl the api url provided by ChinaUnionPay
     * @param array $pubKeys ['key_module', 'key_index', 'key_file', 'ssl_cert_file', 'ssl_key_file']
     * @param array $bizKeys ['temp_key', 'mac_key', 'session_key', 'pin_key', 'track_key', 'expire_time']
     * @param array $appInfo ['factory_code', 'product_model', 'merchant_code', 'terminal_code', 'package_name', 'app_name', 'app_version', 'transaction_mode', 'app_merchant_no', 'app_terminal_no']
     * @param array $payInfo ['payer_id', 'payer_name', 'payer_account', 'deal_id', 'topay_price', 'payee_id', 'payee_name', 'payee_account', 'payee_idcard', 'receiver']
     * @param DataSaverInterface|null $saver
     */
    public function __construct($secretKey, $apiUrl, $pubKeys = [], $bizKeys = [], $appInfo = [], $payInfo = [], DataSaverInterface $saver = null)
    {
        // verify the secret key
        $this->secretKey = $secretKey;
        if (empty($secretKey) || !is_string($secretKey) || !preg_match('/^[0-9a-fA-F]{24}$/', "{$secretKey}")) {
            throw new UnionException("[secretKey] the value must be 24-chars-length belongs to [0~9a-zA-Z]");
        }

        // api url
        $this->apiUrl = $apiUrl;
        if (empty($apiUrl) || !is_string($apiUrl) || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            throw new UnionException("[apiUrl] the value must be a valid formatted URL");
        }

        // current sequence
        $this->sequence = Utilities::makeSequence();

        // union cryptor
        $logFun = function($s) { $this->log($s); };
        $this->cryptor = new UnionCryptor($this->sequence, $logFun);

        // http
        $this->http = new HttpRequest();

        // public key
        $this->pubKeys = new UnionPublicKey($pubKeys['key_module'], $pubKeys['key_index'], $pubKeys['key_file'], $pubKeys['ssl_cert_file'], $pubKeys['ssl_key_file']);

        // business key
        $this->bizKeys = new UnionBusinessKey($bizKeys, $this->secretKey);

        // application terminal info
        $this->appInfos = new UnionAppBaseInfo($appInfo);

        // payer & payee info
        $this->payInfos = new UnionPayUserInfo($payInfo);

        // saver
        if ($saver == null) { $this->saver = DataSaverHandler::getInstance(); } else { $this->saver = $saver; }
    }

    /*
     * 重置平台密钥 (SPK006 + SPK007)：重置成功后，需要将旧密钥弃用
     */
    public function resetPlatformKeys($isForceReset = false)
    {
        if (empty($this->apiUrl) || $this->apiUrl === false) {
            $msg = __CLASS__ . "The official API URL is empty";
            $this->log($msg);
            throw new UnionException($msg);
        }

        if ($isForceReset) {
            $this->bizKeys->tempKey = ''; // 需要重置密钥
        }

        // 密钥未过期，不需要重置，可由任务提前1天重置
        if (
            Utilities::getPhpDateTime() < $this->bizKeys->expireTime
            && $this->bizKeys->tempKey
            && $this->bizKeys->macKey
            && $this->bizKeys->sessionKey
        ) {
            return true;
        }

        if ($isForceReset) {
            $this->log("[BEGIN] reset keys forcibly......");
        }

        // TODO 加入文件锁，以防止重复申请（已有定时任务申请，暂不处理）

        $this->log("[Start] reset keys... (last keys are expired at {$this->bizKeys->expireTime})");
        $this->bizKeys->tempKey = Utilities::makeTempKey(); // 重置密钥甲方，要生成新的temp-key
        // 先发起：机构重置密钥申请
        $bizData = $this->getBizDatagram_SPK006();
        $response_result = $this->apiSendTransactionDatagram(UnionBusinessCode::RESET_COMPANY_KEY_REQUEST, $bizData);
        $status = UnionResult::isSuccessResult($response_result);
        if ($status) {
            // 解密应答数据
            $decrypt_response = $this->cryptor->decryptEncryptData(UnionBusinessCode::RESET_COMPANY_KEY_REQUEST, $response_result, $this->bizKeys->tempKey);
            // 取到keys数据，并保存到数据库
            $this->parseAndSaveDecryptedKeys($decrypt_response);
            $response_result = json_decode($decrypt_response);
            $status = UnionResult::isSuccessResult($response_result);
            if ($status) {
                // 再发起：机构重置密钥确认
                $bizData = $this->getBizDatagram_SPK007();
                $response_result = $this->apiSendTransactionDatagram(UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM, $bizData);
                $status = UnionResult::isSuccessResult($response_result);
                if ($status) {
                    // 解密应答数据
                    $decrypt_response = $this->cryptor->decryptEncryptData(UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM, $response_result, $this->bizKeys->tempKey);
                    $response_result = json_decode($decrypt_response);
                    $status = UnionResult::isSuccessResult($decrypt_response);
                    if ($status) {
                        // 切换使用新的密钥数据:更新表记录：标记删除旧的，将新的记录type改为1
                        $this->saver->save(UnionDataType::EnableNewerKeyData, UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM, $this->sequence);
                    }
                }
            }
            if (!$status) {
                // 因重置失败取消生成的密钥数据
                $this->saver->save(UnionDataType::CancelCurrentKeyData, UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM, $this->sequence);
            }
        }
        if ($status) {
            $this->log("[Finish] reset keys!");
        } else {
            $this->log("[Failure] reset keys! " . UnionResult::getResponseMessageValue($response_result));
        }

        if ($isForceReset) {
            $this->log("[DONE]  reset keys forcibly!");
        }

        return $status;
    }

    /*
     * 农资代扣代付 (SPU004) - 含 SPU002
     */
    public function payToReceiver()
    {
        try {
            // 正常扣款交易
            $result = $this->startPayment();
        } catch (Exception $e) {
            $this->log('UnionPay-Transfer-Error: ' . $e->getMessage());
            $result = ['code' => '-1', 'data' => [], 'msg' => $e->getMessage()];
        }
        $isPaid = UnionResult::isSuccessResult($result);
        if (!$isPaid) {
            // 失败后，查询交易状态
            $bizCode = UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS;
            $orderNo = $this->originTransactionCode;
            // 交易失败时重复查询次数
            $maxRetries = $this->errorRetries;
            $count = 0;
            while ($count < $maxRetries && !$isPaid) {
                $count++;
                $this->log("[$bizCode - $orderNo] START  query trx status: RETRY ({$count}/{$maxRetries})......");
                try {
                    $result = $this->queryTransferDealStatus();
                } catch (Exception $e) {
                    $this->log("[$bizCode - $orderNo] EXCEPTION query trx status: RETRY ({$count}) => " . $e->getMessage());
                    $result = ['code' => '-1', 'data' => [], 'msg' => $e->getMessage()];
                }
                // 失败后，重试查询交易状态
                $isPaid = UnionResult::isSuccessResult($result);
                $status = $isPaid ? 'SUCCESS' : 'FAILURE';
                $this->log("[$bizCode - $orderNo] FINISH query trx status: RETRY ({$count}/{$maxRetries}) => {$status}");
            }
        }
        return $result;
    }

    /*
     * 农资代扣代付 (SPU004)
     */
    private function startPayment()
    {
        $deal_id = $this->payInfos->dealId;
        $topay_price = $this->payInfos->topayPrice;

        $this->log("");
        $this->log("");
        $this->log("[" . UnionBusinessCode::TRANSFER_MONEY . "] START PAY ($deal_id, $topay_price)...");
        $isE18Error = false;

        $status = $this->resetPlatformKeys();
        if ($status) {
            // 农资代扣代付
            $bizData = $this->getBizDatagram_SPU004();
            $response_result = $this->apiSendTransactionDatagram(UnionBusinessCode::TRANSFER_MONEY, $bizData, true, $this->bizKeys->macKey);
            $code = UnionResult::getResponseCodeValue($response_result);
            $isE18Error = 'E18' == strtoupper($code);  //  {"SysRspCode":"E18","SysRspMsg":"session失效[验MAC失败]"}
            $status = UnionResult::isSuccessResult($response_result);
            if ($status) {
                // 解密应答数据
                $decrypt_response = $this->cryptor->decryptEncryptData(UnionBusinessCode::TRANSFER_MONEY, $response_result, $this->bizKeys->sessionKey);
                $response_result = json_decode($decrypt_response, true);
                $status = UnionResult::isSuccessResult($response_result);
            }
            if (!$status) {
                // 解析交易数据
                $code = UnionResult::getResponseCodeValue($response_result);
                $this->log("[" . UnionBusinessCode::TRANSFER_MONEY . "] 扣款支付失败：[$code]" . UnionResult::getResponseMessageValue($response_result));
            }
        }
        $response_result = UnionResult::getFinalResponseResult($response_result, '', $this->sequence, $this->payInfos->dealId);
        $result = json_encode($response_result);
        $this->log("[" . UnionBusinessCode::TRANSFER_MONEY . "] FINISH PAY ($deal_id, $topay_price)! Results: {$result}");
        $this->log("");
        $this->log("");

        if ($isE18Error) {
            $this->log("E18 error happened, need reset it automatically...");
            // $this->resetPlatformKeys(true); // 先不重置，看看银联那边会不会定期让session失效（因为我们是每7天更新下密钥）

            // 再次调用一次付款 - 不要自动调用，仅通过APP客户端按收到出错信息后，再次自行发起请求
            // $this->startPayment();
        }

        return $response_result;
    }

    /*
     * 农资收购剩余额度查询 (SPM013)
     */
    public function queryAccountBalance()
    {
        $this->log("");
        $this->log("");
        $this->log("[SPM013] START QUERY BALANCE...");

        $response_result = [];
        $status = $this->resetPlatformKeys();
        if ($status) {
            // 农资收购剩余额度查询
            $bizData = $this->getBizDatagram_SPM013();
            $response_result = $this->apiSendTransactionDatagram(UnionBusinessCode::RECHARGE_BALANCE, $bizData, true, $this->bizKeys->macKey);
            $status = UnionResult::isSuccessResult($response_result);
            if ($status) {
                // 解密应答数据
                $decrypt_response = $this->cryptor->decryptEncryptData(UnionBusinessCode::RECHARGE_BALANCE, $response_result, $this->bizKeys->sessionKey);
                $response_result = json_decode($decrypt_response, true);
                $status = UnionResult::isSuccessResult($response_result);
            }
            if (!$status) {
                // 解析交易数据
                $code = UnionResult::getResponseCodeValue($response_result);
                $this->log("[" . UnionBusinessCode::RECHARGE_BALANCE . "] 查询额度失败：[$code]" . UnionResult::getResponseMessageValue($response_result));
            }
        }
        $response_result = UnionResult::getFinalResponseResult($response_result, '查询商户可用额度成功', $this->sequence, $this->payInfos->dealId);
        $this->log("[SPM013] FINISH QUERY BALANCE!");
        $this->log("");
        $this->log("");
        return $response_result;
    }

    /*
     * 农资代扣代付交易状态查询 (SPU005) => 请直接使用 全渠道交易状态查询 SPU002
     */
    public function queryTransferDealStatus($originCode = '')
    {
        $this->log("");
        $this->log("");
        $this->log("[" . UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS . "] START QUERY TRANSFER DEAL STATUS...");

        $response_result = [];
        $status = $this->resetPlatformKeys();
        if ($status) {
            // 农资代扣代付交易状态查询
            $bizData = $this->getBizDatagram_SPU002($originCode);
            $response_result = $this->apiSendTransactionDatagram(UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS, $bizData, true, $this->bizKeys->macKey);
            $status = UnionResult::isSuccessResult($response_result); // 此查询交易是成功的
            if ($status) {
                // 解密应答数据
                $decrypt_response = $this->cryptor->decryptEncryptData(UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS, $response_result, $this->bizKeys->sessionKey);
                $response_result = json_decode($decrypt_response, true);
                $status = UnionResult::isSuccessResult($response_result); // 要看实际的原始交易状态：OriRspCd
            }
            if (!$status) {
                // 解析交易数据
                $code = UnionResult::getResponseCodeValue($response_result);
                $this->log("[" . UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS . "] 农资代扣代付交易状态查询失败：[$code]" . UnionResult::getResponseMessageValue($response_result));
            }
        }
        $response_result = UnionResult::getFinalResponseResult($response_result, '农资代扣代付交易状态查询成功', $this->sequence, $this->payInfos->dealId);
        $this->log("[" . UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS . "] FINISH TRANSFER DEAL STATUS!");
        $this->log("");
        $this->log("");
        return $response_result;
    }

    private function parseAndSaveDecryptedKeys($response)
    {
        $this->bizKeys->parseEncryptedKeys($response);

        // 保存的数据
        $postData = [
            'biz_type' => UnionDataType::EnableNewerKeyData,
            'public_key_file' => $this->pubKeys->getPublicKeyFile(),
            'public_key_value' => $this->pubKeys->getPublicKey(),

            'temp_key' => DESCryptor::encrypt($this->bizKeys->tempKey, $this->secretKey),
            'mac_key' => DESCryptor::encrypt($this->bizKeys->macKey, $this->secretKey),
            'session_key' => DESCryptor::encrypt($this->bizKeys->sessionKey, $this->secretKey),
            'track_key' => DESCryptor::encrypt($this->bizKeys->trackKey, $this->secretKey),
            'pin_key' => DESCryptor::encrypt($this->bizKeys->pinKey, $this->secretKey),
        ];

        // 先将密钥保存为待用密钥
        $this->saver->save(UnionDataType::SaveTempKeyData, UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM, $this->sequence, $postData);
    }

    /**
     * 获得加密EncryptData时用的加密密钥
     * @param $bizCode
     * @return false|string
     */
    private function getEncryptionKey($bizCode) {
        $key = '';
        if (UnionBusinessCode::isKeyBusiness($bizCode)) {
            $key = $this->pubKeys->getPublicKey(); // 用 通用公钥 加密
        } else if (UnionBusinessCode::isCommonBusiness($bizCode)) {
            $key = $this->bizKeys->sessionKey; // 用 会话密钥 加密
        }
        if (empty($key)) {
            throw new UnionException("[{$bizCode}] The encryption key (public-key or session-key) is empty.");
        }
        return $key;
    }

    /**
     * 获得解密EncryptData时用的解密密钥
     * @param $bizCode
     * @return false|string
     */
    private function getDecryptionKey($bizCode) {
        $key = '';
        if (UnionBusinessCode::isKeyBusiness($bizCode)) {
            $key = $this->bizKeys->tempKey; // 用 临时密钥 解密
        } else if (UnionBusinessCode::isCommonBusiness($bizCode)) {
            $key = $this->bizKeys->sessionKey; // 用 会话密钥 解密
        }
        if (empty($key)) {
            throw new UnionException("[{$bizCode}] The decryption key (temp-key or session-key) is empty.");
        }
        return $key;
    }

    /**
     * 实际发送交易报文的方法
     * @param $bizCode 交易代码
     * @param $businessData 交易数据(JSON数据，未加密)
     * @param false $isMacDeal true 有MAC交易; false 无MAC交易
     * @return array|false|string
     */
    private function apiSendTransactionDatagram($bizCode, $businessData, $isMacDeal = false) {
        // 获取加密报文数据
        $originData = "";

        // get EncryptData data
        $encryptionKey = $this->getEncryptionKey($bizCode);
        // add common fields for business data
        $businessData += [ /* 报文消息版本号 */ "MsgVer" => UnionPayConstant::UNION_MsgVer,  /* 报文消息发送时间 */"SndDt" => date("Y-m-d\TH:i:s")];
        $encryptData = $this->cryptor->encryptEncryptDataForAllRequest($bizCode, $encryptionKey, $businessData, $originData);

        // 生成发送数据JSON串
        $orderNo = Utilities::makeQueryNO($bizCode);
        if ($bizCode == UnionBusinessCode::TRANSFER_MONEY) {
            $this->originTransactionCode = $orderNo; // 转账交易时，使用查询交易状态功能
        }

        $postData = [
            "Version" => UnionPayConstant::UNION_Version,
            "TrxTp" => $bizCode,
            "TrxIndCd" => $orderNo,
            "FirmMchntId" => $this->appInfos->merchantCode,
            "FirmTermId" => $this->appInfos->terminalCode,
            "EncryptData" => $encryptData,
            "CerVer" => UnionPayConstant::UNION_CerVer,
        ];

        // generate MAC
        if ($isMacDeal) {
            $mac = $this->cryptor->makeMacAs8Bytes($bizCode, json_encode($postData), $this->bizKeys->macKey);
            $postData['MAC'] = $mac;
        } else {
            $this->log("[$bizCode - MAC] NO MAC TRANSACTION for business.");
        }

        // saving data before SEND
        $this->saver->save(UnionDataType::SaveAPIRequestData, $bizCode, $this->sequence, $this->getSavingPostData($postData), $originData);

        // sending business datagram request to ChinaUnionPay API
        $postData = json_encode($postData);
        $this->log("[$bizCode - POST] Sending JSON Data(String) to API: " . $postData);
        $postResult = $this->http->post($this->apiUrl, $postData);
        $this->log("[$bizCode - POST] Received  Response Data from API: " . Utilities::u2c(json_encode($postResult)));

        if (UnionResult::isSuccessResult($postResult)) {
            // saving data after RECV
            $decryptionKey = $this->getDecryptionKey($bizCode);
            $originResult = $this->cryptor->decryptEncryptDataForAllRequest($bizCode, $decryptionKey, $postResult);
            $this->saver->save(UnionDataType::SaveAPIResponseData, $bizCode, $this->sequence, $this->getSavingPostData($postResult), $originResult);
        }
        return $postResult;
    }

    private function getSavingPostData($postData) {
        if (is_array($postData)) { $data = $postData; }
        if (is_string($postData)) { $data = json_decode($postData, true); }
        if (is_object($postData)) { $data = json_decode(json_encode($postData), true); }

        if ($this->payInfos) {
            $data['payer_id'] = $this->payInfos->payerId;
            $data['payee_id'] = $this->payInfos->payeeId;
            $data['deal_id'] = $this->payInfos->dealId;
        }
        return $data;
    }

    private function getBizDatagram_SPK006() {
        return [UnionPropertyName::TEMP_KEY => $this->bizKeys->tempKey];
    }

    private function getBizDatagram_SPK007() {
        return [UnionPropertyName::TEMP_KEY => $this->bizKeys->tempKey, "KeyUpdFlg" => 1];
    }

    private function getBizDatagram_SPU004() {
        $payerId = $this->payInfos->payerId;
        $payeeId = $this->payInfos->payeeId;
        $dealId = $this->payInfos->dealId;
        $topayPrice = $this->payInfos->topayPrice;

        $payerName = $this->payInfos->payerName;
        $payerAccount = $this->payInfos->payerAccount;

        $payeeName = $this->payInfos->payeeName;
        $payeeAccount = $this->payInfos->payeeAccount;
        $payeeIdcard = $this->payInfos->payeeIdcard;

        $receiver = $this->payInfos->receiver;

        // 付款金额转为分，12位定长
        $payPrice = intval($topayPrice * 100);
        $payPrice = str_pad($payPrice, 12, "0", STR_PAD_LEFT);

        $this->log("[SPU004]-payer: $payerId/$payerName/$payerAccount/$topayPrice/$payPrice");
        $this->log("[SPU004]-payee: $payeeId/$payeeName/$payeeAccount/$payeeIdcard/$dealId");

        $data = $this->getBizDatagram_CommonHeader();
        $data += [
            "TrxCurrCd" => UnionPayConstant::UNION_TrxCurrCd,
            "PosEntryMd" => UnionPayConstant::UNION_PosEntryMd,
            "CardHolder" => $receiver ?: $payeeName, // 收款人姓名
            "PayerNm" => $payerName,    // 付款人姓名
            "TrxAmt" => $payPrice,      // 付款金额
        ];
        if ($payeeIdcard && $receiver == $payeeName) {  // 同人名时，才发送身份证号
            $data += [
                "IdType" => UnionPayConstant::UNION_IdType,
                "IdNo" => $payeeIdcard,     // 收款人证件号，有的话必须与IdType一起发送
            ];
        }
        if ($payeeAccount) {
            $data += ["AccNo" => $payeeAccount];    // 收款人账号
        }
        if ($payerAccount) {
            $data += ["PayerAccNo" => $payerAccount]; // 付款人账号，可选
        }
        return $data;
    }

    private function getBizDatagram_SPM013() {
        $data = $this->getBizDatagram_CommonHeader();
        // $data += [];
        return $data;
    }

    private function getBizDatagram_SPU002($originCode = '') {
        $data = $this->getBizDatagram_CommonHeader();
        $data += ['OriTrxIndCd' => $originCode ?: $this->originTransactionCode];
        return $data;
    }

    private function getBizDatagram_CommonHeader() {
        return [
            "TrxLoc" => UnionPayConstant::UNION_TrxLoc,         // 交易坐标
            "FirmCd" => $this->appInfos->factoryCode ?: UnionPayConstant::PACKAGE_NAME,   // 设备信息:厂商代码
            "ProdCd" => $this->appInfos->productModel ?: UnionPayConstant::PACKAGE_NAME,  // 设备信息:产品型号
            "PayAppPackNm" => $this->appInfos->packageName,
            "PayAppNm" => $this->appInfos->appName,
            "PayAppVerInf" => $this->appInfos->appVersion,
            "TrxMd" => $this->appInfos->transactioMode,         // 交易模式,固定值:01(主商户号)
            "AppMrchntNo" => $this->appInfos->appMerchantNo,    // 应用商户号: TrxMd=02时必填
            "AppTrmNo" => $this->appInfos->appTerminalNo,       // 应用终端号: TrxMd=02时必填
            "AppId" => $this->appInfos->packageName,
            "AppNm" => $this->appInfos->appName,
        ];
    }

}
