<?php

namespace Slider23\PhpLlmToolbox\Dto;

class CostCalculator
{
    public array $pricesByModel = [
        'anthropic' => [
            'claude-opus-4-20250514' => [
                'inputTokens' =>                  15 / 1_000_000,
                'cacheCreationInputTokens' =>  18.75 / 1_000_000,
                'cacheReadInputTokens' =>        1.5 / 1_000_000,
                'outputTokens' =>                 75 / 1_000_000,
            ],
            'claude-sonnet-4-20250514' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>   3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                 15 / 1_000_000,
            ],
            'claude-3-7-sonnet-20250219' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>   3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                 15 / 1_000_000,
            ],
            'claude-3-5-sonnet-20241022' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>   3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                 15 / 1_000_000,
            ],
            'claude-3-5-sonnet-20240620' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>   3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                 15 / 1_000_000,
            ],
            'claude-3-5-haiku-20241022' => [
                'inputTokens' =>                   1 / 1_000_000,
                'cacheCreationInputTokens' =>    0.8 / 1_000_000,
                'cacheReadInputTokens' =>        0.8 / 1_000_000,
                'outputTokens' =>                  4 / 1_000_000,
            ]
        ],
        'deepseek' => [
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
        ]

    ];

    public function calculate(string $vendor, string $model, ?int $inputTokens, ?int $cacheCreationInputTokens, ?int $cacheReadInputTokens, ?int $outputTokens): float
    {
        $priceInputTokens = 0;
        $priceCreationInputTokens = 0;
        $priceReadInputTokens = 0;
        $priceOutputTokens = 0;

        if($vendor === 'openrouter') {
            $openRouterModelsPath = dirname(__DIR__, 2) . '/resources/openrouter_models.json';

            $openRouterModels = json_decode(file_get_contents($openRouterModelsPath), true);
            if (isset($openRouterModels[$model])) {
                $model = $openRouterModels[$model];
            }

            $priceInputTokens = $model['pricing']['prompt'] ?? 0;
            $priceCreationInputTokens = $model['pricing']['input_cache_write'] ?? $priceInputTokens;
            $priceReadInputTokens = $model['pricing']['input_cache_read'] ?? $priceInputTokens;
            $priceOutputTokens = $model['pricing']['completion'] ?? 0;
        }else{

        }

        $price = 0;
        $price += (int)$inputTokens * $priceInputTokens;
        $price += (int)$cacheCreationInputTokens * $priceCreationInputTokens;
        $price += (int)$cacheReadInputTokens * $priceReadInputTokens;
        $price += (int)$outputTokens * $priceOutputTokens;

        return $price;
    }

    public function calculateCostToDto(LlmResponseDto $llmResponseDto): LlmResponseDto
    {
        $cost = $this->calculate(
            $llmResponseDto->vendor,
            $llmResponseDto->model,
            $llmResponseDto->inputTokens,
            $llmResponseDto->cacheCreationInputTokens,
            $llmResponseDto->cacheReadInputTokens,
            $llmResponseDto->outputTokens
        );
        $llmResponseDto->cost = $cost;
        return $llmResponseDto;
    }

}
