<?php

namespace Slider23\PhpLlmToolbox\Dto;

class CostCalculator
{
    public array $priceByVendor = [
        'anthropic' => [
            'claude-3-5-sonnet-20241022' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>    3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                  15 / 1_000_000,
            ],
            'claude-3-7-sonnet-20250219' => [
                'inputTokens' =>                   3 / 1_000_000,
                'cacheCreationInputTokens' =>    3.75 / 1_000_000,
                'cacheReadInputTokens' =>        0.3 / 1_000_000,
                'outputTokens' =>                  15 / 1_000_000,
            ],
            'claude-3-5-haiku-20241022' => [
                'inputTokens' =>                   0.8 / 1_000_000,
                'cacheCreationInputTokens' =>    0.8 / 1_000_000,
                'cacheReadInputTokens' =>        0.8 / 1_000_000,
                'outputTokens' =>                  4 / 1_000_000,
            ]
        ],
        'deepseek' => [
            "deepseek-chat" => [
                'promptTokens' => 0.14 / 1_000_000,
                'cacheReadInputTokens' => 0.014 / 1_000_000,
                'cacheCreationInputTokens' => 0.14 / 1_000_000,
                'outputTokens' => 0.28 / 1_000_000
            ],
            'deepseek-reasoner' => [
                'promptTokens' => 0.55 / 1_000_000,
                'cacheReadInputTokens' => 0.14 / 1_000_000,
                'cacheCreationInputTokens' => 0.55 / 1_000_000,
                'outputTokens' => 2.19 / 1_000_000
            ]
        ],
        'openrouter' => [
            'openai/gpt-4o-mini' => [
                'inputTokens' =>                 0.15 / 1_000_000,
                'cacheCreationInputTokens' =>    0.15 / 1_000_000,
                'cacheReadInputTokens' =>        0.15 / 1_000_000,
                'outputTokens' =>                0.6  / 1_000_000,
            ],
            'openai/o3-mini' => [
                'inputTokens' =>                 1.1 / 1_000_000,
                'cacheCreationInputTokens' =>    1.1 / 1_000_000,
                'cacheReadInputTokens' =>        1.1 / 1_000_000,
                'outputTokens' =>                4.4 / 1_000_000,
            ],
            'anthropic/claude-3.5-haiku-20241022' => [
                'inputTokens' =>                 0.8 / 1_000_000,
                'cacheCreationInputTokens' =>    0.8 / 1_000_000,
                'cacheReadInputTokens' =>        0.8 / 1_000_000,
                'outputTokens' =>                4   / 1_000_000,
            ],
            'google/gemini-2.0-flash-001' => [
                'inputTokens' =>                 0.1 / 1_000_000,
                'cacheCreationInputTokens' =>    0.1 / 1_000_000,
                'cacheReadInputTokens' =>        0.1 / 1_000_000,
                'outputTokens' =>                0.4 / 1_000_000,
            ]
        ]
    ];

    public function calculate(string $vendor, string $model, int $inputTokens, int $cacheCreationInputTokens, int $cacheReadInputTokens, int $outputTokens): float
    {
        if (!isset($this->priceByVendor[$vendor][$model])) {
            throw new \InvalidArgumentException("Unknown vendor ($vendor) or model ($model)");
        }

        $price = 0;
        $price += $inputTokens * $this->priceByVendor[$vendor][$model]['inputTokens'];
        $price += $cacheCreationInputTokens * $this->priceByVendor[$vendor][$model]['cacheCreationInputTokens'];
        $price += $cacheReadInputTokens * $this->priceByVendor[$vendor][$model]['cacheReadInputTokens'];
        $price += $outputTokens * $this->priceByVendor[$vendor][$model]['outputTokens'];

        return $price;
    }
}