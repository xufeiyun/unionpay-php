<?php

namespace slkj\unionpay;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Stands for the Payer && Payee information that need for API
 * Class UnionPayUserInfo
 * @package slkj\unionpay
 */
class UnionPayUserInfo
{
    const PROPERTY_DATA_MAPPINGS = [
        'payerId' => 'payer_id',
        'payerName' => 'payer_name',
        'payerAccount' => 'payer_account',

        'dealId' => 'deal_id',
        'topayPrice' => 'topay_price',

        'payeeId' => 'payee_id',
        'payeeName' => 'payee_name',
        'payeeAccount' => 'payee_account',
        'payeeIdcard' => 'payee_idcard',

        'receiver' => 'receiver',
    ];

    /**
     * 收款方，如货物提供者
     * @var string
     */
    public $payerId = '';
    /**
     * 付款方，如实体的商户
     * @var string
     */
    public $payeeId = '';
    /**
     * 业务交易单号
     * @var string
     */
    public $dealId = '';

    /**
     * 付款人姓名(PayerNm)
     * @var string
     */
    public $payerName = '';
    /**
     * 付款人账号(PayerAccNo)
     * 选填
     * @var string
     */
    public $payerAccount = '';
    /**
     * 付款金额(TrxAmt)
     * 至多两位小数金额
     * （会自动转为12位定长数字，单位为分）
     * @var string
     */
    public $topayPrice = '';

    /**
     * 收款人姓名(CardHolder)
     * @var string
     */
    public $payeeName = '';
    /**
     * 收款人账号(AccNo)
     * @var string
     */
    public $payeeAccount = '';
    /**
     * 收款人证件号(IdNo)
     * 【测试时可不发】
     * @var string
     */
    public $payeeIdcard = '';

    /**
     * 优先收款的人的姓名，非空时，要与payeeAccount关系一致
     *
     * 如果与payeeName相同，则会发送payeeIdcard。
     * 如果要发送则会发送payeeIdcard，则与payeeName要相同。
     * 收款方
     * @var string
     */
    public $receiver = '';

    public function __construct($dataInfos = [])
    {
        $this->parseInfos($dataInfos);
    }

    private function parseInfos($dataInfos) {
        Utilities::parseMappedPropertyValue($this, self::PROPERTY_DATA_MAPPINGS, $dataInfos);
    }
}