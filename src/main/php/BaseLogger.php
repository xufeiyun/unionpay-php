<?php

namespace slkj\unionpay;

/*
 * BaseLogger class
 * @package slkj\unionpay
 */
class BaseLogger
{
    protected $logger; // a-callback-function

    public function __construct($logger = null) {
        $this->logger = $logger;
    }

    public function log($message) {
        if ($this->logger && is_object($this->logger)) {
            $log = $this->logger;
            $log($message);
        } else {
            log2file($message);
        }
    }
}
