<?php

namespace slkj\unionpay;

/**
 * type:1保存密钥数据,2启用最新密钥,3取消当前密钥,21保存响应数据,22保存响应数据
 * Class UnionDataType
 * @package slkj\unionpay
 */
class UnionDataType
{
    const SaveTempKeyData =         1; // save the generated temporary key data
    const EnableNewerKeyData =      2; // enable the newer key data once got & saved newer key data
    const CancelCurrentKeyData =    3; // let the current using key data be cancelled
    const SaveAPIRequestData =      21; // save the request data before sending http-post-request
    const SaveAPIResponseData =     22; // save the response data after http-post-request returned
}