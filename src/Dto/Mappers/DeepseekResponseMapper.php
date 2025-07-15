<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class DeepseekResponseMapper
{
    public static array $pricesByModel = [
        "deepseek-chat" => [
            'inputTokens' =>                0.27 / 1_000_000,
            'cacheCreationInputTokens' =>   0.27 / 1_000_000,
            'cacheReadInputTokens' =>       0.07 / 1_000_000,
            'outputTokens' =>                1.1 / 1_000_000
        ],
        'deepseek-reasoner' => [
            'inputTokens' =>                0.55 / 1_000_000,
            'cacheCreationInputTokens' =>   0.55 / 1_000_000,
            'cacheReadInputTokens' =>       0.14 / 1_000_000,
            'outputTokens' =>               2.19 / 1_000_000
        ]
    ];

    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "deepseek";
        $dto->status = "success";
        $dto->rawResponse = $responseArray;
        $dto->id = $responseArray['id'] ?? null;
        $dto->model = $responseArray['model'] ?? null;
        $dto->assistant_content = $responseArray['choices'][0]['message']['content'] ?? null;
        $dto->assistant_reasoning_content = $responseArray['choices'][0]['message']['reasoning_content'] ?? null;
        $dto->finish_reason = $responseArray['finish_reason'] ?? null;
        if(isset($responseArray['usage'])){
            $dto->inputTokens = $responseArray['usage']['prompt_tokens'];
            $dto->cacheCreationInputTokens = $responseArray['usage']['prompt_cache_miss_tokens'] ?? 0;
            $dto->cacheReadInputTokens = $responseArray['usage']['prompt_cache_hit_tokens'] ?? 0;
            $dto->outputTokens = $responseArray['usage']['completion_tokens'] ?? null;
            $dto->totalTokens = $responseArray['usage']['total_tokens'] ?? 0;
            $prices = self::$pricesByModel[$dto->model];
            if(! $prices){
                throw  new \Exception("No prices found for {$dto->model}");
            }
            $dto->cost = $dto->inputTokens * $prices['inputTokens']
                + $dto->cacheCreationInputTokens * $prices['cacheCreationInputTokens']
                + $dto->cacheReadInputTokens * $prices['cacheReadInputTokens']
                + $dto->outputTokens * $prices['outputTokens'];
        }
        return $dto;
    }
}