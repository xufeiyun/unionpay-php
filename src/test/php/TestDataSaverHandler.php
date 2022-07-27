<?php


namespace slkj\unionpay\test;

use slkj\unionpay\DataSaverInterface;

class TestDataSaverHandler implements DataSaverInterface
{
    public function save(int $dataType, string $bizCode, string $sequence, $bizData = [], $originData = '')
    {
        // TODO: Implement save() method.
        $class = __CLASS__;
        $bizData = json_encode($bizData);
        $originData = json_encode($originData);
        log2file("{$sequence} - [{$bizCode} - {$class}] Save data to file in test-data-saver-handler");
        log2file("{$sequence} - [{$bizCode} - {$class}] ... [DataType: {$dataType}, BizCode: {$bizCode}, Data: {$bizData}, OriginData: {$originData}] ...");
    }
}