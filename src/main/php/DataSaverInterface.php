<?php

namespace slkj\unionpay;

/**
 * Interface DataSaverInterface
 * @package slkj\unionpay
 */
interface DataSaverInterface
{
    /**
     * @param int $type UnionDataType value
     * @param string $bizCode UnionBusinessCode
     * @param string $sequence Business Sequence Number
     * @param array $bizData business data
     * @param string $originData the relative original data of the business data
     * @return boolean
     */
    public function save(int $dataType, string $bizCode, string $sequence, $bizData = [], $originData = '');
}