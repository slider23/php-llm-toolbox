<?php

namespace Slider23\PhpLlmToolbox\Dto;

class EmbeddingDto
{
    public ?string $model = null;
    public ?string $vendor = null;
    public array $embedding = [];
    public int $tokens = 0;
    public ?float $cost = null;

    public ?string $status = null;
    public ?string $errorMessage = null;

    public function __construct()
    {

    }

}