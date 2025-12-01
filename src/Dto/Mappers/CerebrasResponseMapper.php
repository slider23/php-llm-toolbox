<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class CerebrasResponseMapper
{
    public static array $pricesByModel = [

        'gpt-oss-120b' => [
            'inputTokens' => 0.35 / 1_000_000,
            'outputTokens' => 0.70 / 1_000_000,
        ],
        'llama3.1-8b' => [
            'inputTokens' => 0.10 / 1_000_000,
            'outputTokens' => 0.10 / 1_000_000,
        ],
        'llama-3.3-70b' => [
            'inputTokens' => 0.85 / 1_000_000,
            'outputTokens' => 1.20 / 1_000_000,
        ],
        'qwen-3-32b' => [
            'inputTokens' => 0.40 / 1_000_000,
            'outputTokens' => 0.80 / 1_000_000,
        ],
        'qwen-3-235b-a22b-instruct-2507' => [
            'inputTokens' => 0.60 / 1_000_000,
            'outputTokens' => 1.20 / 1_000_000,
        ],
        'qwen-3-235b-a22b-thinking-2507' => [
            'inputTokens' => 0.60 / 1_000_000,
            'outputTokens' => 2.90 / 1_000_000,
        ],
        'zai-glm-4.6' => [
            'inputTokens' => 2.25 / 1_000_000,
            'outputTokens' => 2.75 / 1_000_000,
        ],
    ];

    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "cerebras";

        $dto->status = "success";
        $dto->rawResponse = $responseArray;
        $dto->id = $responseArray['id'] ?? null;
        $dto->model = $responseArray['model'] ?? null;

        if (isset($responseArray['choices'][0])) {
            $choice = $responseArray['choices'][0];
            $dto->assistantContent = $choice['message']['content'] ?? null;
            $dto->reasoningContent = $choice['message']['reasoning_content'] ?? null;
            $dto->finishReason = $choice['finish_reason'] ?? null;

            // Handle tool calls
            if (isset($choice['message']['tool_calls'])) {
                $dto->toolsUsed = true;
                $dto->toolCalls = $choice['message']['tool_calls'];
            }
        }

        if (isset($responseArray['usage'])) {
            $promptTokens = $responseArray['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $responseArray['usage']['completion_tokens'] ?? 0;
            $totalTokens = $responseArray['usage']['total_tokens'] ?? null;

            $dto->inputTokens = $promptTokens;
            $dto->outputTokens = $completionTokens;
            $dto->totalTokens = $totalTokens;

            $prices = self::$pricesByModel[$dto->model] ?? null;

            if ($prices) {
                $costInput = ($prices['inputTokens'] ?? 0) * ($dto->inputTokens ?? 0);
                $costOutput = ($prices['outputTokens'] ?? 0) * ($dto->outputTokens ?? 0);
                $dto->cost = $costInput + $costOutput;
            }
        }

        return $dto;
    }
}