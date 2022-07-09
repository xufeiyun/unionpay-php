<?php

namespace ericxu\unionpay;

/*
 * BaseCryptor class
 */

class BaseCryptor
{
    // RSA/ECB/PKCS1Padding
    public const RSA_ALGORITHM_KEY_TYPE = OPENSSL_KEYTYPE_RSA;
    public const RSA_ALGORITHM_SIGN = OPENSSL_ALGO_SHA256;

    /**
     * get files contents via full path
     * @param $file_path string
     * @return bool|string
     */
    public function getContents($file_path)
    {
        file_exists($file_path) or die ("The specified file does NOT exist: [$file_path]");
        return file_get_contents($file_path);
    }
}
