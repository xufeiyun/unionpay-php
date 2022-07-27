<?php

namespace slkj\unionpay;

/*
 * DataSaverHandler class
 * Using openssl crypt methods with public & private key pairs
 * @package slkj\unionpay
 */
class DataSaverHandler implements DataSaverInterface
{
    const ERROR_MESSAGE = "...You MUST implement DataSaverInterface.save(...) by yourself...";

    private static $instance = null;
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new DataSaverHandler();
        }
        return self::$instance;
    }

    /**
     * Save business data for each bizCode
     * @param int $dataType see definitions in UnionDataType class
     * @param string $bizCode see definitions in UnionBusinessCode class
     * @param string $sequence Business Sequence Number
     * @param array $bizData the business data of each api defined via biz-code
     * @param string $originData the original data un-encrypted of each api before sending to the api
     * @return bool
     */
    public function save(int $dataType, string $bizCode, string $sequence, $bizData = [], $originData = '')
    {
        log2file(self::ERROR_MESSAGE);
        throw new UnionException(self::ERROR_MESSAGE);
    }
}
