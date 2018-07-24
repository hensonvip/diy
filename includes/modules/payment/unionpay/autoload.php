<?php
spl_autoload_register(function ($class) {
    if (false !== stripos($class, 'Omnipay\UnionPay')) {
        require_once __DIR__.'/src/'.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 8)).'.php';
    }
});