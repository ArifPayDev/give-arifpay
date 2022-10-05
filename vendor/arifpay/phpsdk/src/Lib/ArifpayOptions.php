<?php

namespace Arifpay\Phpsdk\Lib;

class ArifpayOptions
{
    public $sandbox;

    public function __construct($sandbox)
    {
        $this->sandbox = $sandbox;
    }
}
