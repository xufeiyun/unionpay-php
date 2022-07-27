<?php

namespace slkj\unionpay;

/**
 * UnionPayConstant class
 * @package slkj\unionpay
 */
class UnionPayConstant
{
    const PACKAGE_NAME      = 'unionpay-app';   // 当前项目名
    const UNION_TrxLoc      = "+0/-0";          // 交易坐标
    const UNION_Version     = "100";            // 按规范默认为100
    const UNION_MsgVer      = "V0001";          // 报文消息版本号
    const UNION_CerVer      = "02";             // 证书版号
    const UNION_TrxCurrCd   = "156";            // 交易货币代码: 固定值 156 人民币
    const UNION_PosEntryMd  = "012";            // 服务点输入方式码: 固定值 012
    const UNION_IdType      = "01";             // 收款人证件类型: 固定值 01 身份证
}
