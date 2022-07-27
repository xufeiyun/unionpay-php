<?php

namespace slkj\unionpay;

use Logger;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Generated the union-public-key by key-module(n) & key-index(e)
 * Class UnionBusinessKey
 * @package slkj\unionpay
 */
class UnionBusinessKey
{
    public $expireTime = '';   // the expiring time for all keys, 密钥过期时间

    const PROPERTY_DATA_MAPPINGS = [
        'tempKey' => 'temp_key',
        'macKey' => 'mac_key',
        'sessionKey' => 'session_key',
        'trackKey' => 'track_key',
        'pinKey' => 'pin_key',
    ];

    const ENCRYPT_PROPERTY_DATA_MAPPINGS = [
        // 'tempKey' => 'Utilities::makeTempKey()',
        'macKey' => UnionPropertyName::MAC_KEY,
        'sessionKey' => UnionPropertyName::SESSION_KEY,
        'trackKey' => UnionPropertyName::TRACK_KEY,
        'pinKey' => UnionPropertyName::PIN_KEY,
    ];
    
    /* all below keys will be encrypted using UnionPayment::SECRET_KEY */
    public $tempKey = '';      // union api data field: temp_key,       由发送方自行生成32位串即可，加密报文数据时使用
    public $macKey = '';       // union api data field: mac_key,        由[机构重置密钥申请-SPK006]获得
    public $sessionKey = '';   // union api data field: session_key,    由[机构重置密钥申请-SPK006]获得
    public $trackKey = '';     // union api data field: track_key,      由[机构重置密钥申请-SPK006]获得
    public $pinKey = '';       // union api data field: pin_key,        由[机构重置密钥申请-SPK006]获得

    public function __construct($dataKeys = [], $secretKey)
    {
        $this->parseKeysToDecrypt($dataKeys, $secretKey);
    }

    private function parseKeysToDecrypt($dataKeys, $secretKey) {
        $key = 'expire_time'; // do NOT encrypt
        $this->expireTime = $dataKeys && $dataKeys[$key] ? $dataKeys[$key] : '';
        Utilities::parseMappedPropertyValue($this, self::PROPERTY_DATA_MAPPINGS, $dataKeys, $secretKey);
        // log2file("[ALL KEYS]: TempKey=[$this->tempKey], SessionKey=[$this->sessionKey], MacKey=[$this->macKey], PinKey=[$this->pinKey], TrackKey=[$this->trackKey]");
    }

    public function parseEncryptedKeys($response) {
        // $this->temp_key = Utilities::makeTempKey();
        Utilities::parseMappedPropertyValue($this, self::ENCRYPT_PROPERTY_DATA_MAPPINGS, $response);
        // log2file("[ALL KEYS]: TempKey=[$this->tempKey], SessionKey=[$this->sessionKey], MacKey=[$this->macKey], PinKey=[$this->pinKey], TrackKey=[$this->trackKey]");
    }
}