<?php

namespace Slider23\PhpLlmToolbox\Exceptions;

class LlmVendorException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}