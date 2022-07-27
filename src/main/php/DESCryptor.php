<?php

namespace slkj\unionpay;

/*
 * DESCryptor class
 * Using openssl crypt methods for specific ciphers
 * @package slkj\unionpay
 */
class DESCryptor  extends  BaseCryptor
{
    public function __construct($logger = null) {
        parent::__construct($logger);
    }

    /**
     * [openssl-encrypt description]
     * Using openssl features to encrypt & decrypt data via the same secret key
     * @param  string $plain_text
     * @param  string $secret_key
     * @param  string $cipher method: DES-EDE, DES-EDE3, etc
     * @param  string $is_return_base64: call base64_encode
     * @return string
     */
    public static function encrypt($plain_text, $secret_key, $method = 'DES-EDE', $is_return_base64 = true)
    {
        $secret_key = hex2bin($secret_key); // hexadecimal strings to binary strings
        $encrypted_data = openssl_encrypt($plain_text, $method, $secret_key, OPENSSL_RAW_DATA);
        if ($is_return_base64) {
            $encrypted_data = base64_encode($encrypted_data);
        }
        return $encrypted_data;
    }

    /**
     * [openssl-encrypt description]
     * Using openssl features to encrypt & decrypt data via the same secret key
     * @param  string $plain_text
     * @param  string $secret_key
     * @param  string $cipher method: DES-EDE, DES-EDE3, etc
     * @param  string $is_return_base64: call base64_encode
     * @return string
     */
    public static function encryptWithNoPadding($plain_text, $secret_key, $method = 'DES-EDE', $is_return_base64 = true)
    {
        $secret_key = hex2bin($secret_key); // hexadecimal strings to binary strings
        $encrypted_data = openssl_encrypt($plain_text, $method, $secret_key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        if ($is_return_base64) {
            $encrypted_data = base64_encode($encrypted_data);
        }
        return $encrypted_data;
    }

    /**
     * [openssl-decrypt description]
     * Using openssl features to encrypt & decrypt data via the same secret key
     * @param  string $crypt_text
     * @param  string $secret_key
     * @param  string $cipher method: DES-EDE, DES-EDE3, etc
     * @param  string $is_base64_crypt: call base64_decode for crypt_text
     * @return string
     */
    public static function decrypt($crypt_text, $secret_key, $method = 'DES-EDE', $is_base64_crypt = true)
    {
        if ($is_base64_crypt) {
            $crypt_text = base64_decode($crypt_text);
        }
        $secret_key = hex2bin($secret_key);
        $decrypted_data = openssl_decrypt($crypt_text, $method, $secret_key, OPENSSL_RAW_DATA);
        return $decrypted_data;
    }

    /**
     * [openssl-decrypt description]
     * Using openssl features to encrypt & decrypt data via the same secret key
     * @param  string $crypt_text
     * @param  string $secret_key
     * @param  string $cipher method: DES-EDE, DES-EDE3, etc
     * @param  string $is_base64_crypt: call base64_decode for crypt_text
     * @return string
     */
    public static function decryptWithNoPadding($crypt_text, $secret_key, $method = 'DES-EDE', $is_base64_crypt = true)
    {
        if ($is_base64_crypt) {
            $crypt_text = base64_decode($crypt_text);
        }
        $secret_key = hex2bin($secret_key);
        $decrypted_data = openssl_decrypt($crypt_text, $method, $secret_key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING);
        return $decrypted_data;
    }
}
