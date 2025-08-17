<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class OpenaiResponseMapper
{
    public static array $pricesByModel = [
        // GPT-5 models
        'gpt-5' => [
            'inputTokens' => 1.25 / 1_000_000,
            'cacheCreationInputTokens' => 1.25 / 1_000_000,
            'cacheReadInputTokens' => 0.125 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],
        'gpt-5-mini' => [
            'inputTokens' => 0.25 / 1_000_000,
            'cacheCreationInputTokens' => 0.25 / 1_000_000,
            'cacheReadInputTokens' => 0.025 / 1_000_000,
            'outputTokens' => 2.00 / 1_000_000,
        ],
        'gpt-5-nano' => [
            'inputTokens' => 0.05 / 1_000_000,
            'cacheCreationInputTokens' => 0.05 / 1_000_000,
            'cacheReadInputTokens' => 0.005 / 1_000_000,
            'outputTokens' => 0.40 / 1_000_000,
        ],
        'gpt-5-chat-latest' => [
            'inputTokens' => 1.25 / 1_000_000,
            'cacheCreationInputTokens' => 1.25 / 1_000_000,
            'cacheReadInputTokens' => 0.125 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],

        // GPT-4.1 models
        'gpt-4.1' => [
            'inputTokens' => 2.00 / 1_000_000,
            'cacheCreationInputTokens' => 2.00 / 1_000_000,
            'cacheReadInputTokens' => 0.50 / 1_000_000,
            'outputTokens' => 8.00 / 1_000_000,
        ],
        'gpt-4.1-mini' => [
            'inputTokens' => 0.40 / 1_000_000,
            'cacheCreationInputTokens' => 0.40 / 1_000_000,
            'cacheReadInputTokens' => 0.10 / 1_000_000,
            'outputTokens' => 1.60 / 1_000_000,
        ],
        'gpt-4.1-nano' => [
            'inputTokens' => 0.10 / 1_000_000,
            'cacheCreationInputTokens' => 0.10 / 1_000_000,
            'cacheReadInputTokens' => 0.025 / 1_000_000,
            'outputTokens' => 0.40 / 1_000_000,
        ],

        // GPT-4o models
        'gpt-4o' => [
            'inputTokens' => 2.50 / 1_000_000,
            'cacheCreationInputTokens' => 2.50 / 1_000_000,
            'cacheReadInputTokens' => 1.25 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],
        'gpt-4o-2024-05-13' => [
            'inputTokens' => 5.00 / 1_000_000,
            'outputTokens' => 15.00 / 1_000_000,
        ],
        'gpt-4o-audio-preview' => [
            'inputTokens' => 2.50 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],
        'gpt-4o-realtime-preview' => [
            'inputTokens' => 5.00 / 1_000_000,
            'cacheCreationInputTokens' => 5.00 / 1_000_000,
            'cacheReadInputTokens' => 2.50 / 1_000_000,
            'outputTokens' => 20.00 / 1_000_000,
        ],
        'gpt-4o-search-preview' => [
            'inputTokens' => 2.50 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],

        // GPT-4o-mini models
        'gpt-4o-mini' => [
            'inputTokens' => 0.15 / 1_000_000,
            'cacheCreationInputTokens' => 0.15 / 1_000_000,
            'cacheReadInputTokens' => 0.075 / 1_000_000,
            'outputTokens' => 0.60 / 1_000_000,
        ],
        'gpt-4o-mini-audio-preview' => [
            'inputTokens' => 0.15 / 1_000_000,
            'outputTokens' => 0.60 / 1_000_000,
        ],
        'gpt-4o-mini-realtime-preview' => [
            'inputTokens' => 0.60 / 1_000_000,
            'cacheCreationInputTokens' => 0.60 / 1_000_000,
            'cacheReadInputTokens' => 0.30 / 1_000_000,
            'outputTokens' => 2.40 / 1_000_000,
        ],
        'gpt-4o-mini-search-preview' => [
            'inputTokens' => 0.15 / 1_000_000,
            'outputTokens' => 0.60 / 1_000_000,
        ],

        // o1 models
        'o1' => [
            'inputTokens' => 15.00 / 1_000_000,
            'cacheCreationInputTokens' => 15.00 / 1_000_000,
            'cacheReadInputTokens' => 7.50 / 1_000_000,
            'outputTokens' => 60.00 / 1_000_000,
        ],
        'o1-pro' => [
            'inputTokens' => 150.00 / 1_000_000,
            'outputTokens' => 600.00 / 1_000_000,
        ],
        'o1-mini' => [
            'inputTokens' => 1.10 / 1_000_000,
            'cacheCreationInputTokens' => 1.10 / 1_000_000,
            'cacheReadInputTokens' => 0.55 / 1_000_000,
            'outputTokens' => 4.40 / 1_000_000,
        ],

        // o3 models
        'o3' => [
            'inputTokens' => 2.00 / 1_000_000,
            'cacheCreationInputTokens' => 2.00 / 1_000_000,
            'cacheReadInputTokens' => 0.50 / 1_000_000,
            'outputTokens' => 8.00 / 1_000_000,
        ],
        'o3-pro' => [
            'inputTokens' => 20.00 / 1_000_000,
            'outputTokens' => 80.00 / 1_000_000,
        ],
        'o3-mini' => [
            'inputTokens' => 1.10 / 1_000_000,
            'cacheCreationInputTokens' => 1.10 / 1_000_000,
            'cacheReadInputTokens' => 0.55 / 1_000_000,
            'outputTokens' => 4.40 / 1_000_000,
        ],
        'o3-deep-research' => [
            'inputTokens' => 10.00 / 1_000_000,
            'cacheCreationInputTokens' => 10.00 / 1_000_000,
            'cacheReadInputTokens' => 2.50 / 1_000_000,
            'outputTokens' => 40.00 / 1_000_000,
        ],

        // o4 models
        'o4-mini' => [
            'inputTokens' => 1.10 / 1_000_000,
            'cacheCreationInputTokens' => 1.10 / 1_000_000,
            'cacheReadInputTokens' => 0.275 / 1_000_000,
            'outputTokens' => 4.40 / 1_000_000,
        ],
        'o4-mini-deep-research' => [
            'inputTokens' => 2.00 / 1_000_000,
            'cacheCreationInputTokens' => 2.00 / 1_000_000,
            'cacheReadInputTokens' => 0.50 / 1_000_000,
            'outputTokens' => 8.00 / 1_000_000,
        ],

        // Codex models
        'codex-mini-latest' => [
            'inputTokens' => 1.50 / 1_000_000,
            'cacheCreationInputTokens' => 1.50 / 1_000_000,
            'cacheReadInputTokens' => 0.375 / 1_000_000,
            'outputTokens' => 6.00 / 1_000_000,
        ],

        // Computer use models
        'computer-use-preview' => [
            'inputTokens' => 3.00 / 1_000_000,
            'outputTokens' => 12.00 / 1_000_000,
        ],

        // Image models
        'gpt-image-1' => [
            'inputTokens' => 5.00 / 1_000_000,
            'cacheCreationInputTokens' => 5.00 / 1_000_000,
            'cacheReadInputTokens' => 1.25 / 1_000_000,
        ],

        // Legacy models (keeping for backward compatibility)
        'gpt-4-turbo' => [
            'inputTokens' => 10.00 / 1_000_000,
            'outputTokens' => 30.00 / 1_000_000,
        ],
        'gpt-4' => [
            'inputTokens' => 30.00 / 1_000_000,
            'outputTokens' => 60.00 / 1_000_000,
        ],
        'gpt-3.5-turbo' => [
            'inputTokens' => 0.50 / 1_000_000,
            'outputTokens' => 1.50 / 1_000_000,
        ],

        // Embedding models
        'text-embedding-3-small' => [
            'inputTokens' => 0.020 / 1_000_000,
        ],
        'text-embedding-3-large' => [
            'inputTokens' => 0.130 / 1_000_000,
        ],
        'text-embedding-ada-002' => [
            'inputTokens' => 0.100 / 1_000_000,
        ],
    ];

    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "openai";

        if (isset($responseArray['error'])) {
            $dto->status = "error";
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            $dto->httpStatusCode = is_numeric($responseArray['error']['code'] ?? null) ?
                (int)$responseArray['error']['code'] : null;
            return $dto;
        }

        $dto->status = "success";
        $dto->rawResponse = $responseArray;
        $dto->id = $responseArray['id'] ?? null;
        $dto->model = $responseArray['model'] ?? null;

        if (isset($responseArray['choices'][0])) {
            $choice = $responseArray['choices'][0];
            $dto->assistantContent = $choice['message']['content'] ?? null;
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
            $cachedTokens = $responseArray['usage']['prompt_tokens_details']['cached_tokens'] ?? 0;

            $dto->inputTokens = $promptTokens - $cachedTokens;
            $dto->cacheCreationInputTokens = $dto->inputTokens;
            $dto->cacheReadInputTokens = $cachedTokens;
            $dto->outputTokens = $completionTokens;
            $dto->totalTokens = $responseArray['usage']['total_tokens'] ?? null;

            // Calculate cost based on model pricing
            $prices = null;

            // --- Normalize model name for pricing lookup ---
            $normalizedModel = $dto->model;
            if ($normalizedModel) {
                if (!isset(self::$pricesByModel[$normalizedModel])) {
                    // Try stripping trailing date suffixes: -YYYY, -YYYY-MM, -YYYY-MM-DD
                    $candidate = preg_replace('/-(20\\d{2})(-\\d{2}){0,2}$/', '', $normalizedModel);
                    if ($candidate !== $normalizedModel && isset(self::$pricesByModel[$candidate])) {
                        $normalizedModel = $candidate;
                    }
                }
            }

            $prices = $normalizedModel ? (self::$pricesByModel[$normalizedModel] ?? null) : null;

            // Стоимость считаем даже если нет outputTokens (может быть только ввод / кэш)
            if ($prices) {
                $costInputCreation = ($prices['cacheCreationInputTokens'] ?? $prices['inputTokens'] ?? 0) * ($dto->cacheCreationInputTokens ?? 0);
                $costInputCached   = ($prices['cacheReadInputTokens'] ?? 0) * ($dto->cacheReadInputTokens ?? 0);
                $costOutput        = ($prices['outputTokens'] ?? 0) * ($dto->outputTokens ?? 0);
                $dto->cost = $costInputCreation + $costInputCached + $costOutput;
            }
        }

        $dto->_extractThinking();
        return $dto;
    }


    public static function makeEmbeddingDto(array $responseArray): EmbeddingDto
    {
        $dto = new EmbeddingDto();
        if (isset($responseArray['error'])) {
            $dto->status = "error";
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            return $dto;
        }

        $dto->vendor = "openai";
        $dto->status = "success";
        $dto->embedding = $responseArray['data'][0]['embedding'] ?? [];
        $dto->model = $responseArray['model'] ?? null;
        $dto->tokens = $responseArray['usage']['total_tokens'] ?? 0;
        $prices = self::$pricesByModel[$dto->model] ?? null;
        if ($prices && $dto->tokens) {
            $dto->cost = $dto->tokens * ($prices['inputTokens'] ?? 0);
        } else {
            $dto->cost = 0; // Default cost if no pricing info is available
        }

        return $dto;
    }
}