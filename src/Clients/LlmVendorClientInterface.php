<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

interface LlmVendorClientInterface
{
    public function setBody(array $messages): void;

    public function request(?array $messages = null): LlmResponseDto;
}