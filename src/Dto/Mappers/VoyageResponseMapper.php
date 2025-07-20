<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class VoyageResponseMapper
{
    public static array $pricesByModel = [
        'voyage-3-large' => [
            'inputTokens' => 0.18 / 1_000_000,
        ],
        'voyage-3.5' => [
            'inputTokens' => 0.06 / 1_000_000,
        ],
        'voyage-3.5-lite' => [
            'inputTokens' => 0.02 / 1_000_000,
        ],
        'voyage-code-3' => [
            'inputTokens' => 0.18 / 1_000_000,
        ],
        'voyage-finance-2' => [
            'inputTokens' => 0.12 / 1_000_000,
        ],
        'voyage-law-2' => [
            'inputTokens' => 0.12 / 1_000_000,
        ],
        'voyage-code-2' => [
            'inputTokens' => 0.12 / 1_000_000,
        ],
        'rerank-2' => [
            'inputTokens' => 0.05 / 1_000_000,
        ],
        'rerank-2-lite' => [
            'inputTokens' => 0.02 / 1_000_000,
        ],
        'voyage-multimodal-3' => [
            'inputTokens' => 0.12 / 1_000_000,
        ],
    ];


    public static function makeEmbeddingDto(array $responseArray): EmbeddingDto
    {
        $dto = new EmbeddingDto();
        if (isset($responseArray['error'])) {
            $dto->status = "error";
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            return $dto;
        }

        $dto->vendor = "voyage";
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