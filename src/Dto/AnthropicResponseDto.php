<?php

namespace Slider23\PhpLlmToolbox\Dto;

class AnthropicResponseDto
{
    public string $id;
    public string $model;
    public string $answer;
    public string $stopReason;
    public int $inputTokens;
    public int $outputTokens;
    public int $cacheCreationInputTokens;
    public int $cacheReadInputTokens;
    public int $totalTokens;
    public float $totalCost;

    public int $status;
    public ?string $errorMessage = null;
    public ?string $errorType = null;

    public function __construct()
    {

    }

    public static function fromResponse(array $response, int $statusCode): AnthropicResponseDto
    {
        $dto = new AnthropicResponseDto();
        $dto->id = $response['id'] ?? '';
        $dto->model = $response['model'] ?? '';
        $dto->answer = $response['content']['answer'] ?? '';
        $dto->stopReason = $response['stop_reason'] ?? '';
        $dto->inputTokens = $response['input_tokens'] ?? 0;
        $dto->cacheCreationInputTokens = $response['cache_creation_input_tokens'] ?? 0;
        $dto->cacheReadInputTokens = $response['cache_read_input_tokens'] ?? 0;
        $dto->outputTokens = $response['output_tokens'] ?? 0;
        $dto->totalTokens = $dto->inputTokens + $dto->outputTokens + $dto->cacheCreationInputTokens + $dto->cacheReadInputTokens;
        $dto->status = $statusCode;
        $dto->errorMessage = $response['error']['message'] ?? null;
        $dto->errorType = $response['error']['type'] ?? null;
        return $dto;
    }
}
