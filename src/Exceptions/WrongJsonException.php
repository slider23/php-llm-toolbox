<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Exceptions;

use Exception;
use JsonException;

final class WrongJsonException extends Exception
{
    public function __construct(JsonException $exception, string $sourceJson = '')
    {
        parent::__construct($exception->getMessage()." Source: $sourceJson", $exception->getCode(), $exception);
    }
}