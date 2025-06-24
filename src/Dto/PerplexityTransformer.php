<?php

namespace Slider23\PhpLlmToolbox\Dto;

use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class PerplexityTransformer
{
    public static array $pricesByModel = [
        "sonar" => [
            'inputTokens' =>   1 / 1_000_000,
            'outputTokens' =>  1 / 1_000_000,
            'searchRequests' => [
                'low' =>    5 / 1_000,
                'medium' => 8 / 1_000,
                'high' =>   12 / 1_000,
            ]
        ],
        "sonar-pro" => [
            'inputTokens' =>    3 / 1_000_000,
            'outputTokens' =>  15 / 1_000_000,
            'searchRequests' => [
                'low' =>     6 / 1_000,
                'medium' => 10 / 1_000,
                'high' =>   14 / 1_000,
            ]
        ],
        "sonar-reasoning" => [
            'inputTokens' =>    1 / 1_000_000,
            'outputTokens' =>   5 / 1_000_000,
            'searchRequests' => [
                'low' =>     5 / 1_000,
                'medium' =>  8 / 1_000,
                'high' =>   12 / 1_000,
            ]
        ],
        "sonar-reasoning-pro" => [
            'inputTokens' =>    2 / 1_000_000,
            'outputTokens' =>   8 / 1_000_000,
            'searchRequests' => [
                'low' =>     6 / 1_000,
                'medium' => 10 / 1_000,
                'high' =>   14 / 1_000,
            ]
        ],
    ];
    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "perplexity";
        if(isset($responseArray['error'])){
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            $dto->httpStatusCode = $responseArray['error']['status'] ?? null;
            $dto->status = "error";
            return $dto;
        }
        $dto->status = "success";
        $dto->rawResponse = $responseArray;
        $dto->id = $responseArray['id'] ?? null;
        $dto->model = $responseArray['model'] ?? null;
        $dto->assistant_content = $responseArray['choices'][0]['message']['content'] ?? null;
        $dto->_extractThinking();
        $dto->finish_reason = $responseArray['choices'][0]['finish_reason'] ?? null;
        if(isset($responseArray['usage'])){
            $dto->inputTokens = $responseArray['usage']['prompt_tokens'] ?? null;
            $dto->outputTokens = $responseArray['usage']['completion_tokens'] ?? null;
            $dto->citations = $responseArray['usage']['citations'] ?? null;
            $dto->search_results = $responseArray['usage']['search_results'] ?? null;
            $dto->thinkTokens = $responseArray['usage']['reasoning_tokens'] ?? null;
            $dto->citationTokens = $responseArray['usage']['citation_tokens'] ?? null;
            $dto->totalTokens = $responseArray['usage']['total_tokens'] ?? null;
            $dto->perplexitySearchContextSize = $responseArray['usage']['search_context_size'] ?? null;
            $dto->perplexityNumSearchQueries = $responseArray['usage']['num_search_queries'] ?? null;
            $prices = self::$pricesByModel[$dto->model];
            if(! $prices){
                throw  new \Exception("No prices found for {$dto->model}");
            }
            $dto->cost = $dto->inputTokens * $prices['inputTokens']
                + $dto->outputTokens * $prices['outputTokens']
                + $dto->perplexityNumSearchQueries * $prices['searchRequests'][$dto->perplexitySearchContextSize];
        }
        $dto->citations = $responseArray['citations'] ?? null;
        $dto->search_results = $responseArray['search_results'] ?? null;
        if($dto->citations && is_array($dto->citations)) {
            $dto->assistant_content = self::replaceFootnotesWithLinks($dto->assistant_content, $dto->citations);
        }
        return $dto;
    }

    public static function replaceFootnotesWithLinks($text, $links) {
        // footnote pattern [1], [2], [3] и т.д.
        $pattern = '/\[(\d+)]/';

        $callback = function($matches) use ($links) {
            $footnoteNumber = (int)$matches[1]; // Footnote number
            $linkIndex = $footnoteNumber - 1;   // Link index
            if (isset($links[$linkIndex])) {
                // Markdown
                return '[' . $footnoteNumber . '](' . $links[$linkIndex] . ')';
            }
            return $matches[0];
        };

        return preg_replace_callback($pattern, $callback, $text);
    }
}