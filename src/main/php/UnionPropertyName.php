<?php

namespace slkj\unionpay;

/**
 * UnionPropertyName class
 * @package slkj\unionpay
 */
class UnionPropertyName
{
    // business infos - property name definitions
    const ENCRYPT_DATA                  = "EncryptData";

    // priority for these 4 codes: SysRspCode > OriRspCd > RspCd > code
    const SYS_RESPONSE_CODE             = "SysRspCode";
    const ORIGIN_RESPONSE_CODE          = "OriRspCd";
    const RESPONSE_CODE                 = "RspCd";
    const SELF_CODE                     = "code"; // defined ONLY by this library

    const SYS_RESPONSE_MESSAGE          = "SysRspMsg";
    const ORIGIN_RESPONSE_DESCRIPTION   = "OriRspDesc";
    const RESPONSE_MESSAGE              = "RspMsg";
    const RESPONSE_DESCRIPTION          = "RspDesc";
    const SELF_MESSAGE                  = "message"; // defined ONLY by this library

    const TEMP_KEY                      = "TempKey";
    const MAC_KEY                       = "MacKey";
    const SESSION_KEY                   = "SessionKey";
    const TRACK_KEY                     = "TdkKey";
    const PIN_KEY                       = "PinKey";
}
