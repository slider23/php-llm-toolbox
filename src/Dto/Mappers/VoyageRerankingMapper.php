<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\RerankingDto;

class VoyageRerankingMapper
{
    public static array $pricesByModel = [
        'rerank-2' => [
            'inputTokens' => 0.05 / 1_000_000,
        ],
        'rerank-2-lite' => [
            'inputTokens' => 0.02 / 1_000_000,
        ]
    ];
    public static function makeRerankingDto(array $responseArray): RerankingDto
    {
        $dto = new RerankingDto();
        if (isset($responseArray['error'])) {
            $dto->status = "error";
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            $dto->model = null;
            $dto->data = null;
            $dto->tokens = null;
            $dto->cost = null;
            return $dto;
        }

        $dto->vendor = "voyage";
        $dto->status = "success";
        $dto->data = $responseArray['data'] ?? [];
        $dto->model = $responseArray['model'] ?? null;
        $dto->tokens = $responseArray['usage']['total_tokens'] ?? 0;
        $prices = self::$pricesByModel[$dto->model] ?? null;
        if($prices && $dto->tokens) {
            $dto->cost = $dto->tokens * ($prices['inputTokens'] ?? 0);
        } else {
            $dto->cost = 0;
        }

        return $dto;
    }
}