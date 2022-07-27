<?php

namespace slkj\unionpay;

use Doctrine\Common\Annotations\Annotation\Required;
use Logger;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Stands for the application terminal information (APP or WEB)
 * Class UnionAppBaseInfo
 * @package slkj\unionpay
 */
class UnionAppBaseInfo
{
    const PROPERTY_DATA_MAPPINGS = [
        'factoryCode' => 'factory_code',
        'productModel' => 'product_model',
        'merchantCode' => 'merchant_code',
        'terminalCode' => 'terminal_code',

        'packageName' => 'package_name',
        'appName' => 'app_name',
        'appVersion' => 'app_version',
        'transactioMode' => 'transaction_mode',
        'appMerchantNo' => 'app_merchant_no',
        'appTerminalNo' => 'app_terminal_no',
    ];

    /**
     * 设备信息:厂商代码(FirmCd)
     * @Required
     * @var string
     */
    public $factoryCode = '';

    /**
     * 设备信息:产品型号(ProdCd)
     * @Required
     * @var string
     */
    public $productModel = '';
    /**
     * 实体商户号(FirmMchntId)
     * @var string
     */
    public $merchantCode = '';
    /**
     * 实体终端号(FirmTermId)
     * @var string
     */
    public $terminalCode = '';

    /**
     * App包名(PayAppPackNm、AppId)
     * @Required
     * @var string
     */
    public $packageName = '';
    /**
     * App名称(PayAppNm、AppNm)
     * @Required
     * @var string
     */
    public $appName = '';
    /**
     * App版本(PayAppVerInf)
     * @Required
     * @var string
     */
    public $appVersion = '';


    /**
     * 交易模式(TrxMd)
     * 固定值:01(主商户号)
     * @Required
     * @var string
     */
    public $transactioMode = '01';

    /**
     * 应用商户号(AppMrchntNo)
     * 必填，当TrxMd=02时
     * @var string
     */
    public $appMerchantNo = '';

    /**
     * 应用终端号(AppTrmNo)
     * 必填，当TrxMd=02时
     * @var string
     */
    public $appTerminalNo = '';

    public function __construct($appInfos = [])
    {
        $this->parseInfos($appInfos);
    }

    private function parseInfos($appInfos) {
        Utilities::parseMappedPropertyValue($this, self::PROPERTY_DATA_MAPPINGS, $appInfos);
    }
}