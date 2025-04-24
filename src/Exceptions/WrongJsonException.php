<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Exceptions;

use Exception;
use JsonException;

final class WrongJsonException extends Exception
{
    public function __construct(JsonException $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }
}