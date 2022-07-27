<?php

namespace slkj\unionpay;

/*
 * Utilities class
 * @package slkj\unionpay
 */
class Utilities
{
    /**
     * unicode => chinese
     * @param $string
     * @return string|string[]|null
     */
    public static function u2c($string)
    {
        if (is_string($string)) {
            return preg_replace_callback("#\\\u([0-9a-f]{4})#i",
                function ($r) {
                    return iconv('UCS-2BE', 'UTF-8', pack('H4', $r[1]));
                },
                $string);
        }
        return $string;
    }

    /**
     * ansi => utf8
     * @param $string
     * @return array|false|string|string[]|null
     */
    public static function s2u8($string)
    {
        $encode = mb_detect_encoding($string, array("ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5'));
        if ($encode == 'UTF-8') {
            return $string;
        } else {
            return mb_convert_encoding($string, 'UTF-8', $encode);
        }
    }

    /**
     * get its value of specific property from result
     * @param $result
     * @param $property
     * @param $trim
     * @return false|mixed
     */
    public static function getPropertyValue($result, $property, $trim = false)
    {
        $value = false;
        if (is_string($result)) {
            $value = json_decode($result);
        }
        if ($result && is_object($result) && isset($result->$property)) {
            $value = $result->$property;
        } elseif ($result && is_array($result) && isset($result[$property])) {
            $value = $result[$property];
        }
        if ($value && $trim) {
            $value = trim($value);
        }
        return $value;
    }

    /**
     * Length: 3(SEQ) + 14 + 6
     * @return string
     */
    public static function makeSequence() {
        $dt = date("YmdHis");
        $rd = rand(100000, 999999);
        return "SEQ" .$dt . $rd;
    }

    /**
     * Length: 6 + 17
     * @param $biz
     * @return string
     */
    public static function makeQueryNO($bizCode) {
        $order_no = self::makeOrderNo();
        return $bizCode . $order_no;
    }

    /**
     * Length: 14 + 3
     * @return string
     */
    public static function makeOrderNo() {
        $dt = date("YmdHis");
        $rd = rand(100, 999);
        return $dt . $rd;
    }

    /**
     * Length: 14 + 14 + 6
     * @return string
     */
    public static function makeTempKey() {
        $dt = date("YmdHis");
        $rd = rand(100000, 999999);
        $tmpKey = $dt . $dt . $rd;
        return $tmpKey;
    }

    /**
     * string to bytes
     * @param $value
     * @return array|false
     */
    public static function str2bytes($value) {
        if (empty($value)) return false;

        // get bytes
        $count = strlen($value);
        $bytes = [];
        for ($i = 0; $i < $count; $i++) {
            $bytes[$i] = ord($value[$i]); // ord('a') => 97 (dec), dechex(97) => 61 (hex); need to use dechex() to convert to hex-value for hex-string
            // $bytes[$i] = bin2hex($value[$i]); // bin2hex('a') => 61 (hex)
        }
        return $bytes;
    }

    /**
     * add zero (0x00) in the end
     * @param $bytes
     * @param int $number
     * @return int return count for padded zero
     */
    public static function padZero(&$bytes, $number = 8) {
        if ($number < 1) $number = 8;

        $count = count($bytes);
        if ($count % $number == 0) {
            return $count; //  exactly divisible OR no remainder
        }
        $padCount = $number - $count % $number;
        for ($i = 0; $i < $padCount; $i++) {
            $bytes[$count] = 0x00;
            $count++;
        }
        return $count;
    }


    /*
     * return byte[] eight-bytes-data
     */
    public static function xorByEach8Bytes($bytesData, $eight = 8) {
        if ($eight < 1) $eight = 8;

        if (empty($bytesData)) return false; // argument is wrong

        // 补足字节数
        $count = self::padZero($bytesData, $eight);
        // 每8字节xor
        $xorValues = [];
        for ($i = 0; $i < $eight; $i++) {
            $xorValues[$i] = $bytesData[$i];
        }
        $group = ceil($count / $eight);
        for ($k = 1; $k < $group; $k++) {
            $j = 0;
            $i = $k * $eight;
            $next = $i + $eight;
            while ($i < $next) {
                $xorValues[$j++] ^= $bytesData[$i++];
            }
        }
        return $xorValues;
    }

    /**
     * 'a' (0x61) => "61", 0x03 => "03"
     * @param $bytes
     * @return string
     */
    public static function bytes2hex($bytes) {
        $value = "";
        $count = count($bytes);
        for ($i = 0; $i < $count; $i++) {
            //$value .= str_pad(bin2hex($bytes[$i]), 2, "0", STR_PAD_LEFT);
            $value .= str_pad(dechex($bytes[$i]), 2, "0", STR_PAD_LEFT);
        }
        // $value = strtoupper($value);
        return $value;
    }

    public static function numberFormat($number = null)
    {
        if (empty($number)) return 0; // 0

        $number = floatval($number); // to consider the precision influence
        $number = "{$number}";
        $ns = explode('.', $number);
        if (count($ns) == 1) {
            return intval($number); // int
        }

        $n1 = $ns[0];
        $n2 = $ns[1];
        $n2 = "{$n2}00";
        $n2 = substr($n2, 0, 2);
        $number = "{$n1}.{$n2}";
        $number = floatval($number); // float
        return $number;
    }

    public static function write2file($fileName, $content) {
        if (empty($fileName) && !empty($content)) {
            log2file($content);
            return;
        }
        if (!file_exists($fileName)) {
            $path = dirname($fileName);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
        file_put_contents($fileName, $content, FILE_APPEND);
    }

    public static function ts2time($timestamp) {
        return date("Y-m-d H:i:s", $timestamp);
    }

    public static function getPhpDateTime($day_intervals = 0, $second_intervals = 0) {
        $d = $dt = date_create(date("Y-m-d H:i:s"));
        if ($second_intervals != 0) {
            $d = date_add($d, date_interval_create_from_date_string("$second_intervals seconds"));
        }
        if ($day_intervals != 0) {
            $d = date_add($d, date_interval_create_from_date_string("$day_intervals days"));
        }
        return date_format($d, 'Y-m-d H:i:s');
    }

    /**
     * @param $instance
     * @param $mappings
     * @param $data
     * @param string $secret has secret, then decrypt the value with secret using DESCryptor::decrypt()
     * @return false
     */
    public static function parseMappedPropertyValue($instance, $mappings, $data, $secret = '') {
        if (empty($instance) || empty($mappings) || empty($data)) return false;

        // $class = __CLASS__;; log2file("[{$class}] SECRET_KEY = {$secret}");

        // TODO check all required keys exist or not
        foreach ($mappings as $k => $v) {
            // ATTENTION: Cannot access private property
            $value = self::getPropertyValue($data, $v);
            if ($value) {
                $instance->$k = $secret ? DESCryptor::decrypt($value, $secret) : $value;
            }
        }
    }
}