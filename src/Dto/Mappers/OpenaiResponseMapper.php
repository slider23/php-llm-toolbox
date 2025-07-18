<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class OpenaiResponseMapper
{
    public static array $pricesByModel = [
        'gpt-4o-mini' => [
            'inputTokens' => 0.150 / 1_000_000,
            'outputTokens' => 0.600 / 1_000_000,
        ],
        'gpt-4-turbo' => [
            'inputTokens' => 10.00 / 1_000_000,
            'outputTokens' => 30.00 / 1_000_000,
        ],
        'gpt-4o' => [
            'inputTokens' => 2.50 / 1_000_000,
            'outputTokens' => 10.00 / 1_000_000,
        ],
        'gpt-4' => [
            'inputTokens' => 30.00 / 1_000_000,
            'outputTokens' => 60.00 / 1_000_000,
        ],
        'gpt-3.5-turbo' => [
            'inputTokens' => 0.50 / 1_000_000,
            'outputTokens' => 1.50 / 1_000_000,
        ],
        'o1-preview' => [
            'inputTokens' => 15.00 / 1_000_000,
            'outputTokens' => 60.00 / 1_000_000,
        ],
        'o1-mini' => [
            'inputTokens' => 3.00 / 1_000_000,
            'outputTokens' => 12.00 / 1_000_000,
        ],
        'text-embedding-3-small' => [
            'inputTokens' => 0.02 / 1_000_000,
        ],
        'text-embedding-3-large' => [
            'inputTokens' => 0.13 / 1_000_000,
        ],
        'text-embedding-ada-002' => [
            'inputTokens' => 0.10 / 1_000_000,
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
            $dto->inputTokens = $responseArray['usage']['prompt_tokens'] ?? null;
            $dto->outputTokens = $responseArray['usage']['completion_tokens'] ?? null;
            $dto->totalTokens = $responseArray['usage']['total_tokens'] ?? null;
            
            // Calculate cost based on model pricing
            $prices = null;
            foreach(self::$pricesByModel as $key => $value) {
                if (strpos($dto->model, $key) !== false) {
                    $prices = $value;
                    break;
                }
            }
            if ($prices && $dto->inputTokens && $dto->outputTokens) {
                $dto->cost = ($dto->inputTokens * $prices['inputTokens']) + 
                            ($dto->outputTokens * $prices['outputTokens']);
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
        if($prices && $dto->tokens) {
            $dto->cost = $dto->tokens * ($prices['inputTokens'] ?? 0);
        } else {
            $dto->cost = 0; // Default cost if no pricing info is available
        }

        return $dto;
    }
}