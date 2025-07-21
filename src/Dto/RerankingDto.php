<?php

namespace Slider23\PhpLlmToolbox\Dto;

class RerankingDto
{
    public ?string $model = null;
    public string $vendor = 'voyage';
    public ?string $query = null;
    /**
     * @var array|null ['relevance_score' => float, 'index' => int, ?'document' => string[]]
     */
    public ?array $data = null;
    public ?int $tokens = null;
    public ?float $cost = null;
    public ?string $status = null;
    public ?string $errorMessage = null;
}