<?php

namespace slkj\unionpay;

/**
 * UnionResult class
 * @package slkj\unionpay
 */
class UnionResult
{
    const TransactionSuccess = '银联转账支付成功';              // 这是代付提示 （商家 付款 出去）
    const TransactionFailure = '银联转账失败，请现金结算';      // 这是代付提示（商家 付款 出去）

    /*
     * 是白名单中的代码，则返回实际消息，否则统一返回消息
     */
    const WhiteListCode = ['E31', '00', '14', '51'];

    /*
     * 银联交易API返回代码定义信息
     */
    const CodeList = [
        '00' => ['code' => '00', 'result' => '承兑或交易成功', 'type' => 'A', 'operation' => '成功', 'message' => '交易成功'],
        '01' => ['code' => '01', 'result' => '查发卡方', 'type' => 'C', 'operation' => '失败', 'message' => '请持卡人与发卡银行联系'],
        '03' => ['code' => '03', 'result' => '无效商户', 'type' => 'C', 'operation' => '失败', 'message' => '无效商户'],
        '04' => ['code' => '04', 'result' => '没收卡', 'type' => 'D', 'operation' => '呑卡、没收', 'message' => '此卡为无效卡（POS）'],
        '05' => ['code' => '05', 'result' => '身份认证失败', 'type' => 'C', 'operation' => '失败', 'message' => '持卡人认证失败'],
        '10' => ['code' => '10', 'result' => '部分金额批准', 'type' => 'A', 'operation' => '成功，需提示', 'message' => '显示部分批准金额，提示操作员'],
        '11' => ['code' => '11', 'result' => '重要人物批准（VIP）', 'type' => 'A', 'operation' => '成功', 'message' => '此为VIP客户'],
        '12' => ['code' => '12', 'result' => '无效的关联交易', 'type' => 'C', 'operation' => '失败', 'message' => '无效交易'],
        '13' => ['code' => '13', 'result' => '无效金额', 'type' => 'B', 'operation' => '失败', 'message' => '无效金额'],
        '14' => ['code' => '14', 'result' => '无效卡号（无此账号）', 'type' => 'B', 'operation' => '失败', 'message' => '无效卡号'],
        '15' => ['code' => '15', 'result' => '无此发卡方', 'type' => 'C', 'operation' => '失败', 'message' => '此卡无对应发卡方'],
        '21' => ['code' => '21', 'result' => '卡未初始化', 'type' => 'C', 'operation' => '失败', 'message' => '该卡未初始化或睡眠卡'],
        '22' => ['code' => '22', 'result' => '故障怀疑，关联交易错误', 'type' => 'C', 'operation' => '失败', 'message' => '操作有误，或超出交易允许天数'],
        '25' => ['code' => '25', 'result' => '找不到原始交易', 'type' => 'C', 'operation' => '失败', 'message' => '没有原始交易，请联系发卡方'],
        '30' => ['code' => '30', 'result' => '报文格式错误', 'type' => 'C', 'operation' => '失败', 'message' => '请重试'],
        '34' => ['code' => '34', 'result' => '有作弊嫌疑', 'type' => 'D', 'operation' => '呑卡、没收', 'message' => '作弊卡, 呑卡'],
        '38' => ['code' => '38', 'result' => '超过允许的PIN试输入', 'type' => 'D', 'operation' => '失败', 'message' => '密码错误次数超限，请与发卡方联系'],
        '40' => ['code' => '40', 'result' => '请求的功能尚不支持', 'type' => 'C', 'operation' => '失败', 'message' => '发卡方不支持的交易'],
        '41' => ['code' => '41', 'result' => '挂失卡', 'type' => 'D', 'operation' => '呑卡、没收', 'message' => '挂失卡（POS）'],
        '43' => ['code' => '43', 'result' => '被窃卡', 'type' => 'D', 'operation' => '呑卡、没收', 'message' => '被窃卡（POS）'],
        '45' => ['code' => '45', 'result' => '不允许降级交易', 'type' => 'C', 'operation' => '失败', 'message' => '请使用芯片'],
        '51' => ['code' => '51', 'result' => '资金不足', 'type' => 'C', 'operation' => '失败', 'message' => '可用余额不足'],
        '54' => ['code' => '54', 'result' => '过期的卡', 'type' => 'C', 'operation' => '失败', 'message' => '该卡已过期'],
        '55' => ['code' => '55', 'result' => '不正确的PIN', 'type' => 'C', 'operation' => '失败', 'message' => '密码错'],
        '57' => ['code' => '57', 'result' => '交易关闭，联系发卡行', 'type' => 'C', 'operation' => '失败', 'message' => '发卡行不允许该卡发生交易'],
        '58' => ['code' => '58', 'result' => '不允许终端进行的交易', 'type' => 'C', 'operation' => '失败', 'message' => '发卡方不允许该卡在本终端进行此交易'],
        '59' => ['code' => '59', 'result' => '有作弊嫌疑', 'type' => 'C', 'operation' => '失败', 'message' => '卡片校验错'],
        '61' => ['code' => '61', 'result' => '超出金额限制', 'type' => 'C', 'operation' => '失败', 'message' => '交易金额超限'],
        '62' => ['code' => '62', 'result' => '受限制的卡', 'type' => 'C', 'operation' => '失败', 'message' => '受限制的卡'],
        '64' => ['code' => '64', 'result' => '原始金额错误', 'type' => 'C', 'operation' => '失败', 'message' => '交易金额与原交易不匹配'],
        '65' => ['code' => '65', 'result' => '超出取款/消费次数限制', 'type' => 'C', 'operation' => '失败', 'message' => '超出取款次数限制'],
        '68' => ['code' => '68', 'result' => '发卡行响应超时', 'type' => 'B', 'operation' => '失败', 'message' => '交易超时，请重试'],
        '75' => ['code' => '75', 'result' => '允许的输入PIN次数超限', 'type' => 'C', 'operation' => '失败', 'message' => '密码错误次数超限'],
        '90' => ['code' => '90', 'result' => '正在日终处理', 'type' => 'C', 'operation' => '失败', 'message' => '系统日切，请稍后重试'],
        '91' => ['code' => '91', 'result' => '发卡方不能操作', 'type' => 'C', 'operation' => '失败', 'message' => '发卡方状态不正常，请稍后重试'],
        '92' => ['code' => '92', 'result' => '金融机构或中间网络设施找不到或无法达到', 'type' => 'C', 'operation' => '失败', 'message' => '发卡方线路异常，请稍后重试'],
        '94' => ['code' => '94', 'result' => '重复交易', 'type' => 'C', 'operation' => '失败', 'message' => '拒绝，重复交易，请稍后重试'],
        '96' => ['code' => '96', 'result' => '银联处理中心系统异常、失效', 'type' => 'C', 'operation' => '失败', 'message' => '拒绝，交换中心异常，请稍后重试'],
        '97' => ['code' => '97', 'result' => 'ATM/POS终端号找不到', 'type' => 'D', 'operation' => '失败', 'message' => '终端号未登记'],
        '98' => ['code' => '98', 'result' => '银联处理中心收不到发卡方应答', 'type' => 'E', 'operation' => '失败', 'message' => '发卡方超时'],
        '99' => ['code' => '99', 'result' => 'PIN格式错', 'type' => 'B', 'operation' => '失败', 'message' => 'PIN格式错，请重新签到'],
        'A0' => ['code' => 'A0', 'result' => 'MAC鉴别失败', 'type' => 'B', 'operation' => '失败', 'message' => 'MAC校验错，请重新签到'],
        'A1' => ['code' => 'A1', 'result' => '转账货币不一致', 'type' => 'C', 'operation' => '失败', 'message' => '转账货币不一致'],
        'A2' => ['code' => 'A2', 'result' => '有缺陷的成功', 'type' => 'A', 'operation' => '成功', 'message' => '交易成功，请向资金转入行确认'],
        'A3' => ['code' => 'A3', 'result' => '资金到账行无此账户', 'type' => 'C', 'operation' => '失败', 'message' => '资金到账行账号不正确'],
        'A4' => ['code' => 'A4', 'result' => '有缺陷的成功', 'type' => 'A', 'operation' => '成功', 'message' => '交易成功，请向资金到账行确认'],
        'A5' => ['code' => 'A5', 'result' => '有缺陷的成功', 'type' => 'A', 'operation' => '成功', 'message' => '交易成功，请向资金到账行确认'],
        'A6' => ['code' => 'A6', 'result' => '有缺陷的成功', 'type' => 'A', 'operation' => '成功', 'message' => '交易成功，请向资金到账行确认'],
        'A7' => ['code' => 'A7', 'result' => '安全处理失败', 'type' => 'C', 'operation' => '失败', 'message' => '安全处理失败'],
        'E1' => ['code' => 'E1', 'result' => 'CUPA连接超时，需要进行冲正', 'type' => 'C', 'operation' => '失败', 'message' => '正在发起冲正交易'],
        'E2' => ['code' => 'E2', 'result' => '请求超时', 'type' => 'C', 'operation' => '失败', 'message' => '交易失败'],
        'E3' => ['code' => 'E3', 'result' => '请求数据非法，交易失败', 'type' => 'C', 'operation' => '失败', 'message' => '请求数据非法，交易失败'],
        'E4' => ['code' => 'E4', 'result' => '系统异常，请稍候重试', 'type' => 'C', 'operation' => '失败', 'message' => '系统异常，请稍候重试'],
        'E5' => ['code' => 'E5', 'result' => '交易失败，IC卡数据非法', 'type' => 'C', 'operation' => '失败', 'message' => '交易失败，IC卡数据非法'],
        'E6' => ['code' => 'E6', 'result' => '流水号生产错误，交易失败', 'type' => 'C', 'operation' => '失败', 'message' => '流水号生产错误，交易失败'],
        'E7' => ['code' => 'E7', 'result' => '报文发送失败，请重试', 'type' => 'C', 'operation' => '失败', 'message' => '报文发送失败，请重试'],
        'RS' => ['code' => 'RS', 'result' => '冲正成功', 'type' => 'C', 'operation' => '失败', 'message' => '冲正成功'],
        'RF' => ['code' => 'RF', 'result' => '冲正失败', 'type' => 'C', 'operation' => '失败', 'message' => '冲正失败'],
    ];

    public static function isSuccessResult($result) {
        // 银联返回结果是否成功
        $code = self::getResponseCodeValue($result);
        return self::isSuccessCode($code);
    }

    public static function isSuccessCode($code) {
        return '00' === "$code" || '0' === "$code";
    }

    public static function getResponseCodeValue($result) {
        $value = Utilities::getPropertyValue($result, UnionPropertyName::SYS_RESPONSE_CODE);
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::ORIGIN_RESPONSE_CODE); // 查询交易时，用此优先，成功时会返回，失败时不返回
        }
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::RESPONSE_CODE);
        }
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::SELF_CODE);
        }
        return $value;
    }

    public static function getResponseMessageValue($result) {
        $value = Utilities::getPropertyValue($result, UnionPropertyName::SYS_RESPONSE_MESSAGE);
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::ORIGIN_RESPONSE_DESCRIPTION);
        }
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::RESPONSE_MESSAGE);
        }
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::RESPONSE_DESCRIPTION);
        }
        if (empty($value) || !$value) {
            $value = Utilities::getPropertyValue($result, UnionPropertyName::SELF_MESSAGE);
        }
        return Utilities::u2c($value);
    }

    public static function getFinalResponseResult($union_result, $success_message = '', $sequence = '', $bizId = 0) {
        $code = self::getResponseCodeValue($union_result);
        $message = self::getResponseMessageValue($union_result);
        $isSuccess = self::isSuccessResult($union_result); // 交易成功代码
        $union_data = [
            'code' => $code,
            'msg' => $message,
            'data' => base64_encode(Utilities::u2c(json_encode($union_result)))
        ];
        // 白名单代码中的，则返回实际消息，否则统一返回失败消息
        $error_message = self::TransactionFailure;
        if (in_array($code, self::WhiteListCode)) {
            $errors = UnionResult::CodeList[$code];
            if ($errors) {
                $r = UnionResult::CodeList[$code]['result'];
                $m = UnionResult::CodeList[$code]['message'];
                $error_message = ($r == $m) ? $r : "$r (原因：$m)/(异常: $message)";
                $error_message != $message ? $error_message = $message : "";
            } else {
                $error_message = $error_message . "[" . $message ."]";
            }
        }
        $success_message = empty($success_message) ? UnionResult::TransactionSuccess : $success_message;
        if (!$isSuccess) {
            log2file("$sequence - [Final Result] $error_message (交易：{$bizId}, 原因：{$message})");
        }
        return ['code' => $isSuccess ? '0' : '-1',
            'msg' => $isSuccess ? $success_message : $error_message,
            'data' => $union_result ? json_decode(Utilities::u2c(json_encode($union_data)), true) : "failure"
        ];
    }

}
