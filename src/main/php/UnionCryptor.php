<?php

namespace slkj\unionpay;

/*
 * UnionCryptor class
 * Just encrypt OR decrypt union-api-data
 * @package slkj\unionpay
 */
class UnionCryptor extends  BaseCryptor
{
    /**
     * business sequence
     * @var
     */
    private $sequence;

    public function __construct($sequence, $logger = null) {
        $this->sequence = $sequence;
        // log2file($logger);
        parent::__construct($logger);
    }

    /**
     * 加密生成EncryptData数据
     * 用RSA或DES-EDE加密请求数据
     * Encrypt request data for all business request
     * 密钥交易类：用PublicKey加密
     * 普通交易类：用SessionKey加密
     * @param $bizCode
     * @param $encryptionKey 加密密钥
     * @param array $requestData ksort后的请求数据
     * @param $originData
     * @return false|string|null
     */
    public function encryptEncryptDataForAllRequest($bizCode, $encryptionKey, $requestData = [], &$originData) {
        if (empty($bizCode) || empty($requestData) || !is_array($requestData)) {
            $this->log("[$bizCode - encrypt] arguments missing or request_data is not array");
            return false;
        }
        $data = $requestData;
        ksort($data);
        $originData = $data;
        $plainText = json_encode($data);
        $this->log("[$bizCode - Encryption] PlainText: " . $plainText);

        if (UnionBusinessCode::isKeyBusiness($bizCode)) {
            $result = RSACryptor::publicEncrypt($plainText, $encryptionKey); // RSA 用 原始公钥 加密
        } else if (UnionBusinessCode::isCommonBusiness($bizCode)) {
            $result = DESCryptor::encrypt($plainText, $encryptionKey); // DES 用 会话密钥 加密
        } else {
            throw new UnionException("[$bizCode - Encryption] unsupported encryption key for this business code, maybe use session-key!");
        }

        $this->log("[$bizCode - Encryption] EncryptData: " . $result);
        return $result;
    }

    /**
     * 解密EncryptData数据
     * 用DES-EDE专门解密EncryptData
     * Decrypt response data for all business response
     * @param $bizCode
     * @param $decryptionKey 解密密钥
     * @param $responseData
     * @return false|string
     */
    public function decryptEncryptDataForAllRequest($bizCode, $decryptionKey, $responseData) {
        $this->log("[$bizCode - Encryption] Encrypt PlainText: " . json_encode($responseData));

        $data = !empty($decryptionKey) ? $this->decryptEncryptData($bizCode, $responseData, $decryptionKey) : false;

        $this->log("[$bizCode - Encryption] Output  EncryptData: " . json_encode($data));
        return $data;
    }
    /*
     * 解密EncryptData数据
     * 用DES-EDE专门解密EncryptData
     * 密钥交易类：用TempKey解密
     * 普通交易类：用SessionKey解密
     */
    public function decryptEncryptData($bizCode, $responseData, $key) {
        $encryptData = Utilities::getPropertyValue($responseData, UnionPropertyName::ENCRYPT_DATA);
        $decryptData = trim(DESCryptor::decryptWithNoPadding($encryptData, $key));
        $this->log("[$bizCode - Decryption] Decrypt EncryptData:  " . $decryptData);
        return $decryptData;
    }

    /**
     * generate MAC as 8-bytes-upper-hex-string (16 hex characters)
     * @param $bizCode
     * @param $jsonDataString
     * @param $macKeyHex
     * @param bool $endWithAndSign
     * @return false|string
     */
    public function makeMacAs8Bytes($bizCode, $jsonDataString, $macKeyHex, $endWithAndSign = true)
    {
        if (empty($macKeyHex)) {
            $error = "[$bizCode - MAC] NO MacKey value for transaction-with-mac!";
            $this->log($error);
            throw new UnionException($error);
        }

        $eight = 8;

        $this->log("[$bizCode - MAC] Start generating MAC...");

        $data = Utilities::u2c($jsonDataString);
        $data = json_decode($data, true); // 转为数组，而非stdClass对象

        $macBlockJsonString = "";

        // 1. sort array by alphabet order, then convert to query-string-format
        $status = ksort($data);
        foreach ($data as $k => $v) { $macBlockJsonString = $macBlockJsonString . "$k=$v&"; }
        if (!$endWithAndSign) { $macBlockJsonString = rtrim($macBlockJsonString, '&'); }
        $this->log("[$bizCode - MAC] MacBlockJsonString: $macBlockJsonString");
        $macBlockBytes = Utilities::str2bytes($macBlockJsonString);

        // 2. xor each byte in the array, including appending to times of 8-bytes
        $macBlock8Bytes = Utilities::xorByEach8Bytes($macBlockBytes);

        // 3. convert to upper-hex-string
        $macBlock8BytesHex = Utilities::bytes2hex($macBlock8Bytes); // 转了16进制串后，又要转回字节数组，是的，原来是8字节，转后是16字节
        $this->log("[$bizCode - MAC] MacBlockXorTo8BytesToHex: " . $macBlock8BytesHex);

        // 4. using ansiX919 to calc mac
        $macKey24Bytes = $this->expandMacKeyTo24Bytes($macKeyHex);
        $this->log("[$bizCode - MAC] MacKey=$macKeyHex, Mac24BytesHex=" . Utilities::bytes2hex($macKey24Bytes));

        $macKey24BytesHex = Utilities::bytes2hex($macKey24Bytes);
        $this->log("[$bizCode - MAC] MacKeyTo24BytesToHex: " . $macKey24BytesHex);

        $mac8BytesHex =$this->ansiX919GenerateMacByString($bizCode, $macBlock8BytesHex, $macKey24BytesHex);
        $this->log("[$bizCode - MAC] ansix919MacToHex: " . $mac8BytesHex);

        // 5. 取结果五的前8个字节，得到MAC
        $mac = substr($mac8BytesHex, 0, $eight);
        $this->log("[$bizCode - MAC] Generated MAC: $mac");

        return $mac;
    }

    // 转成24字节数据
    private function expandMacKeyTo24Bytes($macKeyHex, $length = 24) {
        if (empty($macKeyHex) || strlen($macKeyHex) % 2 != 0) return null;

        // 先将mackey转为字节数组
        $bytes = [];
        $count = strlen($macKeyHex) / 2; # 临界点未处理
        for ($i = 0; $i < $count; $i++) {
            if ($i >= $length) break;
            // 每2位取出为一个字节，直接用hexdec()? YES
            // $bytes[$i] = bindec(substr($macKeyHex, $i * 2, 2));
            $bytes[$i] = hexdec(substr($macKeyHex, $i * 2, 2));
        }
        $eight = 8;
        // 处理为24个字节
        $bytes = array_merge($bytes, array_fill(count($bytes) + 1, $length - count($bytes), 0x00));// 转为最多24个字节
        $count = count($bytes); // 8, 16, 24
        $group = $count / $eight; // 1, 2, 3
        // Utilities::padZero($bytes, $length);
        for ($i = 0; $i < $eight; $i++) {
            if ($group == 1) {
                $bytes[$eight + $i] = $bytes[$i];
            }
            $bytes[$eight * 2 + $i] = $bytes[$i];
        }
        return $bytes;
    }

    private function ansiX919GenerateMacByString($bizCode, $macBlock8BytesHex, $macKey24BytesHex, $eight = 8) {
        if (empty($macBlock8BytesHex) || empty($macKey24BytesHex)) return false;
        if (strlen($macKey24BytesHex) % $eight != 0) return false;

        $macBlock8BytesHex = strtoupper($macBlock8BytesHex);
        $count = strlen($macBlock8BytesHex) / $eight;

        $macKey24BytesHex = strtoupper($macKey24BytesHex);
        $length = $eight * 2;

        $this->log("[$bizCode - MAC] ansiX919ToMac-start: MacBlock=[$macBlock8BytesHex], MacKey=[$macKey24BytesHex]");

        $key1Hex = substr($macKey24BytesHex, 0 * $length, $length);
        $key2Hex = substr($macKey24BytesHex, 1 * $length, $length);
        $key3Hex = substr($macKey24BytesHex, 2 * $length, $length);

        // 默认单倍长，此为三倍长，经核对，密钥不区分大小写，key3=key1时，结果一致
        $key1Hex = $key1Hex . $key1Hex . $key1Hex;
        $key2Hex = $key2Hex . $key2Hex . $key2Hex;
        $key3Hex = $key3Hex . $key3Hex . $key3Hex;

        $this->log("[$bizCode - MAC] ansiX919ToMac-key1: [$key1Hex]");
        $this->log("[$bizCode - MAC] ansiX919ToMac-key2: [$key2Hex]");
        $this->log("[$bizCode - MAC] ansiX919ToMac-key3: [$key3Hex]");

        $v = hex2bin("0000000000000000");;
        for ($i = 0; $i < $count; $i++) {
            $tmp = substr($macBlock8BytesHex, $i * $eight, $eight);
            $v ^= $tmp;
            // $v = openssl_encrypt($v, "des-ede3", $key1Bin, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
            $v = DESCryptor::encryptWithNoPadding($v, $key1Hex, "DES-EDE3", false);
        }
        // $v = openssl_decrypt($v, "des-ede3", $key2Bin, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $v = DESCryptor::decryptWithNoPadding($v, $key2Hex, "DES-EDE3", false);
        // $v = openssl_encrypt($v, "des-ede3", $key3Bin, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        $v = DESCryptor::encryptWithNoPadding($v, $key3Hex, "DES-EDE3", false);

        $v = bin2hex($v); // F8D044BB99DABE06
        $v = strtoupper($v);

        $this->log("[$bizCode - MAC] ansiX919ToMac-finish: Mac=[$v]");

        return $v;
    }
}
