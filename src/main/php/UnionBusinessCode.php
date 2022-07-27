<?php

namespace slkj\unionpay;

/**
 * UnionBusinessCode class
 * @package slkj\unionpay
 */
class UnionBusinessCode
{
    // business infos - business code definitions
    const RESET_SESSION_KEY             = 'SPK001'; // 重置会话密钥，由 SPK007 代替
    const RESET_COMPANY_KEY_REQUEST     = 'SPK006'; // 机构重置密钥申请
    const RESET_COMPANY_KEY_CONFIRM     = 'SPK007'; // 机构重置密钥确认

    const TRANSFER_MONEY_QUERY_STATUS   = 'SPU002'; // 全渠道交易状态查询
    const TRANSFER_MONEY                = 'SPU004'; // 农资代扣代付
    const QUERY_TRANSACTION_STATUS      = 'SPU005'; // 农资代扣代付交易状态查询，由 SPU002 代替
    const RECHARGE_BALANCE              = 'SPM013'; // 农资收购剩余额度查询

    public function __construct() {
    }

    /**
     * 是否属于密钥类交易业务
     * 1) 加密EncryptData用：using RSA by public-key
     * 2) 解密EncryptData用：using DES-EDE by temp-key
     * @param $bizCode
     * @param array $appendCodes 可加入符合规则的业务代码
     * @return bool
     */
    public static function isKeyBusiness($bizCode, $appendCodes = []) {
        $keyCodes = [
            UnionBusinessCode::RESET_COMPANY_KEY_REQUEST,
            UnionBusinessCode::RESET_COMPANY_KEY_CONFIRM
        ];
        return self::isInArray($bizCode, $keyCodes, $appendCodes);
    }

    /**
     * 是否属于普通类交易业务
     * 1) 加密EncryptData用：using DES-EDE by session-key
     * 2) 解密EncryptData用：using DES-EDE by session-key
     * @param $bizCode
     * @param array $appendCodes 可加入符合规则的业务代码
     * @return bool
     */
    public static function isCommonBusiness($bizCode, $appendCodes = []) {
        $commonCodes = [
            UnionBusinessCode::TRANSFER_MONEY,
            UnionBusinessCode::RECHARGE_BALANCE,
            UnionBusinessCode::TRANSFER_MONEY_QUERY_STATUS
        ];
        return self::isInArray($bizCode, $commonCodes, $appendCodes);
    }

    private static function isInArray($code, $codes, $appendCodes) {
        if (!empty($appendCodes)) {
            $codes = array_merge($codes, $appendCodes);
        }
        return in_array($code, $codes);
    }
}
