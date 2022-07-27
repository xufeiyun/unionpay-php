<?php

namespace slkj\unionpay;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Generated the union-public-key by key-module(n) & key-index(e)
 * Class UnionPublicKey
 * @package slkj\unionpay
 */
class UnionPublicKey
{
    // 公钥信息
    private $public_key_mod = "";   // 公钥模:均为16进制
    private $public_key_idx = "";   // 公钥指数:均为16进制
    private $public_key_value = ""; // 公钥数据，由 公钥模 + 公钥指数 生成的
    private $public_key_file = "";  // 公钥文件:存储的公钥数据

    private $private_key_value = ""; // 私钥数据 - 未使用到

    private $http_crt_file = ''; // CRT证书文件(*.crt)路径
    private $http_key_file = ''; // KEY私钥文件(*.pem)路径

    public function __construct($keyModule, $keyIndex, $saveKeyFile, $sslCertFile, $sslKeyFile)
    {
        $this->public_key_mod = $keyModule;
        $this->public_key_idx = $keyIndex;
        $this->public_key_file = $saveKeyFile;
        $this->http_crt_file = $sslCertFile;
        $this->http_key_file = $sslKeyFile;
        $this->checkFiles();
        $this->makePublicKeyByModuleAndIndex();
    }

    /**
     * return the public-key data if exists
     * @return false|string
     */
    public function getPublicKey() {
        return $this->public_key_value ? $this->public_key_value : false;
    }

    /**
     * return the public-key file path if exists
     * @return false|string
     */
    public function getPublicKeyFile() {
        return $this->public_key_file && file_exists($this->public_key_file) ? $this->public_key_file : false;
    }

    private function checkFiles()
    {
        if (empty($this->public_key_mod) || empty($this->public_key_idx)) {
            $msg = __CLASS__. ": Client's public-key module data OR public-key index data is empty!";
            log2file($msg);
            throw new UnionException($msg);
        }

        if (!file_exists($this->http_crt_file) || !file_exists($this->http_key_file)) {
            $msg = __CLASS__. ": Client's cert file(*.crt) OR key file(*.pem) does NOT exist: <br>cert file：$this->http_crt_file, <br>key file：$this->http_key_file";
            log2file($msg);
            throw new UnionException($msg);
        }
    }

    /*
     * Generate the Public Key data by public-key-module & public-key-index data
     */
    private function makePublicKeyByModuleAndIndex()
    {
        log2file("Try loading file content: " . $this->public_key_file);
        file_exists($this->public_key_file) && $this->public_key_value = file_get_contents($this->public_key_file);

        if ($this->public_key_value) {
            log2file("Loaded file content from public-key-file!");
        } else {
            $rsa = new RSA();

            $e = new BigInteger($this->public_key_idx, 16);
            $n = new BigInteger($this->public_key_mod, 16);
            $pk = $rsa->loadKey(['e' => $e, 'n' => $n]); // WITHOUT CRYPT_RSA_PUBLIC_FORMAT_PKCS1
            if ($pk) {
                // NOT CRYPT_RSA_PUBLIC_FORMAT_PKCS1
                $this->public_key_value = $rsa->getPublicKey(RSA::PUBLIC_FORMAT_PKCS8); // default used by PHP's openssl_public_encrypt()
                // save to file
                !empty($this->public_key_value)
                && file_exists($this->public_key_file)
                && Utilities::write2file($this->public_key_file, $this->public_key_value);
                // if ($this->public_key_value) { log2file(__CLASS__. " [SUCCESS] Generated Public-Key data：\r\n" . $this->public_key_value); }
            }
            if (!$pk || empty($this->public_key_value) || $this->public_key_value === false) {
                $msg = __CLASS__. " [FAILURE] Can't generate the Public-Key data, please check public-key-module and public-key-index data.";
                log2file($msg);
                throw new UnionException($msg);
            } else {
                log2file("Generated the public-key-file by module & index data!");
            }
        }
    }
}