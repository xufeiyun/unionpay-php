<?php

namespace slkj\unionpay\test;

use phpseclib\Crypt\RSA;
use PHPUnit\Framework\TestCase;

/**
 * Class CryptorFeaturesTest
 * @package slkj\unionpay
 */
class CryptorFeaturesTest extends TestCase
{

    public function testEncryptToMac($macBlockHex = '', $key1Hex = '', $key2Hex = '', $key3Hex = '') {

        echo "[args] mab=[$macBlockHex], key1=[$key1Hex], key2=[$key2Hex], key3=[$key3Hex]. <br>";

        $mab = $macBlockHex; if (empty($mab)) $mab = "5428166A7A5A7003";

        $key1 = $key1Hex; if (empty($key1)) $key1 = "1111111111111111";
        $key2 = $key2Hex; if (empty($key2)) $key2 = "1111111111111111";
        $key3 = $key3Hex; if (empty($key3)) $key3 = "1111111111111111";

        // 默认单倍长，此为三倍长，与单倍长结果一致
        $key1 = $key1 . $key1 . $key1; $key2 = $key2 . $key2 . $key2; $key3 = $key3 . $key3 . $key3;
        echo "key1=[$key1]<br>key2=[$key2]<br>key3=[$key3]<br>";

        // hex2bin:  The binary representation of the given data (给定数据的字节表示形式，仍是字符串，但与字节数组有差别的)
        $key1 = hex2bin($key1); $key2 = hex2bin($key2); $key3 = hex2bin($key3);
        // hex2bin === chr(ord('2位16进制')) ?

        $mac = hex2bin("0000000000000000");
        for($i = 0; $i < strlen ( $mab ) / 8 ; $i++) {
            $tempMab = substr($mab, $i*8, 8);
            echo "0. " . bin2hex($mac) . ", " . bin2hex($tempMab) . "<br>";
            $mac = $mac ^ $tempMab;
            echo "1. " . bin2hex($mac) . "<br>";
            $mac = openssl_encrypt($mac, "des-ede3", $key1, OPENSSL_RAW_DATA|OPENSSL_NO_PADDING);
            echo "2. " . bin2hex($mac) . "<br>";
        }
        echo "3. " . bin2hex($mac) . "<br>";
        $mac = openssl_decrypt($mac, "des-ede3", $key2, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING);
        echo "4. " . bin2hex($mac) . "<br>";
        $mac = openssl_encrypt($mac, "des-ede3", $key3, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING);
        echo "5. " . bin2hex($mac) . "<br>";

        echo "7. " . strtoupper(bin2hex($mac)) . "<br><br><br>";
    }

    public function testEncryptData1($plain_text) {
        $rsa = new RSA();
        define('CRYPT_RSA_PKCS15_COMPAT', true);
        $rsa->loadKey($this->public_key_value);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
        $result = $rsa->encrypt($plain_text);
        $result = base64_encode($result);
        return $result;
    }

    public function testEncryptData2($plain_text) {
        $key = file_get_contents($this->public_key_file);

        $key = str_replace("-----BEGIN PUBLIC KEY-----", "", $key);
        $key = str_replace("-----END PUBLIC KEY-----", "", $key);
        $key = str_replace("\r", "", $key);
        $key = str_replace("\n", "", $key);

        log2file("RSA-PUBLIC-KEY:" . $key);

        $rsa = new RSA();
        $hasKey = $rsa->loadKey($key);
        // $hasKey = $rsa->loadKey($key, CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
        if ($hasKey) {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
            $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);

            $result = $rsa->encrypt($plain_text);
            $result = base64_encode($result);
            return $result;
        }
        return false;
    }

    public function testGenerateMac()
    {
        $mabHex = $key1Hex = $key2Hex = '';
        $this->ansiX919GenerateMacByString('5428166A7A5A7003', '111111111111111111111111111111111111111111111111');
        $this->testEncryptToMac('5428166A7A5A7003', $key1Hex, $key2Hex, $key1Hex);

        $mabHex = strtolower('104f1c0721230f37');
        $key1Hex = strtolower('BC1A423BC39430BD');
        $key2Hex = strtolower('E3FA1B30A76E5C35');
        $this->ansiX919GenerateMacByString($mabHex, strtolower('BC1A423BC39430BDE3FA1B30A76E5C35BC1A423BC39430BD'));
        $this->testEncryptToMac($mabHex, $key1Hex, $key2Hex, $key1Hex);

        $mabHex = strtoupper('2901561E1E061A2E');
        $key1Hex = strtoupper('BC1A423BC39430BD');
        $key2Hex = strtoupper('E3FA1B30A76E5C35');
        $this->ansiX919GenerateMacByString($mabHex, strtoupper('BC1A423BC39430BDE3FA1B30A76E5C35BC1A423BC39430BD'));
        $this->testEncryptToMac($mabHex, $key1Hex, $key2Hex, $key1Hex);

        $mabHex = strtoupper('104f1c0721230f37');
        $key1Hex = strtolower('BC1A423BC39430BD');
        $key2Hex = strtolower('E3FA1B30A76E5C35');
        $this->ansiX919GenerateMacByString($mabHex, strtolower('BC1A423BC39430BDE3FA1B30A76E5C35BC1A423BC39430BD'));
        $this->testEncryptToMac($mabHex, $key1Hex, $key2Hex, $key1Hex);

    }

    public function testSetDataKeyValues() {
        /*
        TempKey=[20200513110032202005131100328178],
        SessionKey=[5A2F549ABA5421DE13C0A6BE1C09D9FF],
        MacKey=[BF12478CDB123A15AC94235460CEA8EE],
        PinKey=[B085D0370096D1BCC895C97C8111D82D],
        TrackKey=[5146C1B4E97E8464792DF993E4B2B6FA]
        */

        $this->temp_key = "20200513110032202005131100328178";
        $this->mac_key = "BF12478CDB123A15AC94235460CEA8EE";
        //$this->mac_key = "11111111111111111111111111111111";
        $this->session_key = "5A2F549ABA5421DE13C0A6BE1C09D9FF";
        $this->pin_key = "B085D0370096D1BCC895C97C8111D82D";
        $this->track_key = "5146C1B4E97E8464792DF993E4B2B6FA";
    }

}