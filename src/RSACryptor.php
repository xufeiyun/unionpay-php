<?php

namespace ericxu\unionpay;

/*
 * RSACryptor class
 */
class RSACryptor extends  BaseCryptor
{
    public function __construct() {
    }

    /**
     * Encrypt by public-key (then decrypt by private-key)
     * @param string $plain_text
     * @param string $public_key
     * @return null|string
     */
    public static function publicEncrypt($plain_text, $public_key)
    {
        if (!is_string($plain_text)) {
            return null;
        }

        $key = openssl_pkey_get_public($public_key);
        $key_length = openssl_pkey_get_details($key)['bits'];
        $part_len = $key_length / 8 - 11;
        $parts = str_split($plain_text, $part_len);

        // MUST encrypt by parts
        $encrypted = '';
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_public_encrypt($part, $encrypted_temp, $key);
            $encrypted .= $encrypted_temp;
        }
        return base64_encode($encrypted);
    }

    /**
     * Decrypt by private-key (then encrypt by public-key)
     * @param string $encrypt_text
     * @param string $private_key
     * @return null
     */
    public static function privateDecrypt($encrypt_text, $private_key)
    {
        if (!is_string($encrypt_text)) {
            return null;
        }

        $key = openssl_pkey_get_private($private_key);
        $key_length = openssl_pkey_get_details($key)['bits'];
        $part_len = $key_length / 8;
        $base64_decoded = base64_decode($encrypt_text);
        $parts = str_split($base64_decoded, $part_len);

        // MUST decrypt by parts
        $decrypted = '';
        foreach ($parts as $part) {
            $decrypted_temp = '';
            openssl_private_decrypt($part, $decrypted_temp, $key);
            $decrypted .= $decrypted_temp;
        }

        return $decrypted;
    }


    /**
     * Encrypt by private-key (then Decrypt by public-key)
     * @param string $plain_text
     * @param string $private_key
     * @return null|string
     */
    public static function privateEncrypt($plain_text = '', $private_key)
    {
        if (!is_string($plain_text)) {
            return null;
        }

        $key = openssl_pkey_get_private($private_key);
        $key_length = openssl_pkey_get_details($key)['bits'];
        $part_len = $key_length / 8 - 11;
        $parts = str_split($plain_text, $part_len);

        // MUST encrypt by parts
        $encrypted = '';
        foreach ($parts as $part) {
            $encrypted_temp = '';
            openssl_private_encrypt($part, $encrypted_temp, $key);
            $encrypted .= $encrypted_temp;
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt by public-key (then Encrypt by private-key)
     * @param string $encrypt_text
     * @param string $public_key
     * @return null
     */
    public static function publicDecrypt($encrypt_text = '', $public_key)
    {
        if (!is_string($encrypt_text)) {
            return null;
        }

        $key = openssl_pkey_get_public($public_key);
        $key_length = openssl_pkey_get_details($key)['bits'];
        $part_len = $key_length / 8;
        $base64_decoded = base64_decode($encrypt_text);
        $parts = str_split($base64_decoded, $part_len);

        // MUST decrypt by parts
        $decrypted = "";
        foreach ($parts as $part) {
            $decrypted_temp = '';
            openssl_public_decrypt($part, $decrypted_temp, $key);
            $decrypted .= $decrypted_temp;
        }

        return $decrypted;
    }

    /*
     * Sign data by private-key
     * @param string $data
     * @param string $private_key
     * @return null|string
     */
    public static function sign($data, $private_key)
    {
        $key = openssl_pkey_get_public($private_key);
        openssl_sign($data, $sign, $key, self::RSA_ALGORITHM_SIGN);
        $sign = base64_encode($sign);
        return $sign;
    }

    /*
     * Verify data by public-key
     * @param string $data
     * @param string $sign
     * @param string $public_key
     * @return null|string
     */
    public static function verify($data, $sign, $public_key)
    {
        $key = openssl_pkey_get_public($public_key);
        $sign = base64_decode($sign);
        $res = openssl_verify($data, $sign, $key, self::RSA_ALGORITHM_SIGN);
        return $res;
    }

}
