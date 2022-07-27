<?php
/**
 * Bootstrapping File for unionpay-php
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */

if (!function_exists('log2file')) { function log2file($message) { \slkj\unionpay\UnionLogger::log($message); } }