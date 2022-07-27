<?php

namespace slkj\unionpay;

/**
 * HttpRequest class
 * @package slkj\unionpay
 */
class HttpRequest
{
    // curl - arguments
    const SSL_CERT_TYPE = 'PEM'; // 证书和私钥类型(不同时要分开定义): "PEM" (default), "DER", and "ENG"
    const SSL_KEY_TYPE = 'PEM';  // 证书和私钥类型(不同时要分开定义): "PEM" (default), "DER", and "ENG"
    const CLIENT_CERT_FILE_PWD = ''; // Client Cert证书密码，无则为空
    const CLIENT_KEY_FILE_PWD = ''; // Client Key证书密码，无则为空

    // TODO 双向认证
    private $isSSL = 0;        // enable CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
    private $sslCertFile = ''; // file for CURLOPT_SSLCERT
    private $sslKeyFile = '';  // file for CURLOPT_SSLKEY

    /**
     * Send data via Post
     * @param int $isSSL use SSL features or not
     * @param int $crtFile CRT证书文件(*.crt)路径
     * @param int $keyFile KEY私钥文件(*.pem)路径
     */
    public function __construct($isSSL = 0, $crtFile = '', $keyFile = '')
    {
        $this->isSSL = $isSSL;
        $this->sslCertFile = $crtFile;
        $this->sslKeyFile = $keyFile;
    }

    /**
     * Send data via Post
     * @param string $url the target url
     * @param string $postData the post data
     * @param int $timeout execution timeout, 30 seconds by default
     * @param array $headers headers for CURLOPT_HTTPHEADER
     * @param boolean $debug enable verbose information or not for debug
     * @return string  resource content for curl_exec
     */
    public function post($url, $postData, $timeout = 30, $headers = ['Content-type:application/json; charset=UTF-8'], $debug = false)
    {
        $result = false;
        try {
            $handler = curl_init();
            curl_setopt($handler, CURLOPT_VERBOSE, $debug);
            if ($this->isSSL) {
                // curl_setopt($handler, CURLOPT_SSL_VERIFYSTATUS, false);
                curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, TRUE);
                // curl_setopt($handler, CURLOPT_CAINFO, getcwd() . '/cert/ca.crt');
                curl_setopt($handler, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_0);
                curl_setopt($handler, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');

                curl_setopt($handler, CURLOPT_SSLCERTTYPE, self::SSL_CERT_TYPE);
                curl_setopt($handler, CURLOPT_SSLCERT, $this->sslCertFile);
                if (self::CLIENT_CERT_FILE_PWD) { curl_setopt($handler, CURLOPT_SSLCERTPASSWD, self::CLIENT_CERT_FILE_PWD); }

                curl_setopt($handler, CURLOPT_SSLKEYTYPE, self::SSL_KEY_TYPE);
                curl_setopt($handler, CURLOPT_SSLKEY, $this->sslKeyFile);
                if (self::CLIENT_KEY_FILE_PWD) { curl_setopt($handler, CURLOPT_SSLKEYPASSWD, self::CLIENT_KEY_FILE_PWD); }

            } else {
                curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, FALSE);
            }
            curl_setopt($handler, CURLOPT_POST, TRUE);
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handler, CURLOPT_URL, $url);
            curl_setopt($handler, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, 5);
            $res = curl_exec($handler);
            if (!$res) {
                $result = $this->handleResult(curl_errno($handler), curl_error($handler));
            } else {
                $result = json_decode(Utilities::u2c($res));
            }
            curl_close($handler);
            // $result = u2c($result);
        } catch (Exception $e) {
            $this->handleResult($e->getCode(), $e->getMessage());
        }
        return $result;
    }

    private function handleResult($code, $message, $method = 'post')
    {
        if ($code == 28) {
            $message = "[{$code}] timeout for remote-api，no result returned";
        }
        $msg = [
            "code" => "[CURL] {$code}",
            "message" => "[CURL] {$message}"
        ];
        log2file("curl {$method} fail: " . json_encode($msg));
        return $msg;
    }

}
