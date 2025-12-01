<?php

namespace Slider23\PhpLlmToolbox\Dto\Mappers;

use Slider23\PhpLlmToolbox\Dto\BatchInfoDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class AnthropicResponseMapper
{
    public static array $pricesByModel = [
        'claude-opus-4-5-20251101' => [
            'inputTokens' =>                   5 / 1_000_000,
            'cacheCreationInputTokens' =>      5 / 1_000_000,
            'cacheReadInputTokens' =>          5 / 1_000_000,
            'outputTokens' =>                 25 / 1_000_000,
        ],
        'claude-sonnet-4-5-20250929' => [
            'inputTokens' =>                   3 / 1_000_000,
            'cacheCreationInputTokens' =>   3.75 / 1_000_000,
            'cacheReadInputTokens' =>        0.3 / 1_000_000,
            'outputTokens' =>                 15 / 1_000_000,
        ],
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
    ];

    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "anthropic";
        
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
        $dto->finishReason = $responseArray['stop_reason'] ?? null;
        
        // Handle content and tool calls
        if(isset($responseArray['content']) && is_array($responseArray['content'])){
            $textContent = '';
            $toolCalls = [];
            
            foreach($responseArray['content'] as $content){
                if(isset($content['type'])){
                    if($content['type'] === 'text'){
                        $textContent .= $content['text'] ?? '';
                    } elseif($content['type'] === 'tool_use'){
                        $toolCalls[] = [
                            'id' => $content['id'],
                            'function' => [
                                'name' => $content['name'],
                                'arguments' => json_encode($content['input'] ?? [])
                            ]
                        ];
                    }
                }
            }
            
            $dto->assistantContent = $textContent ?: null;
            
            if(!empty($toolCalls)){
                $dto->toolsUsed = true;
                $dto->toolCalls = $toolCalls;
            }
        }
        
        if(isset($responseArray['usage'])){
            $dto->inputTokens = $responseArray['usage']['input_tokens'] ?? null;
            $dto->cacheCreationInputTokens = $responseArray['usage']['cache_creation_input_tokens'] ?? 0;
            $dto->cacheReadInputTokens = $responseArray['usage']['cache_read_input_tokens'] ?? 0;
            $dto->outputTokens = $responseArray['usage']['output_tokens'] ?? null;
            
            $prices = self::$pricesByModel[$dto->model] ?? null;
            if($prices){
                $dto->cost = $dto->inputTokens * $prices['inputTokens']
                    + $dto->cacheCreationInputTokens * $prices['cacheCreationInputTokens']
                    + $dto->cacheReadInputTokens * $prices['cacheReadInputTokens']
                    + $dto->outputTokens * $prices['outputTokens'];
            }
            if(isset($responseArray['usage']['service_tier']) && $responseArray['usage']['service_tier'] === 'batch'){
                $dto->cost = $dto->cost / 2; // Adjust cost for batch requests
            }
        }
        
        $dto->_extractThinking();
        return $dto;
    }

    public static function makeBatchInfoDto(array $result)
    {
        $dto = new BatchInfoDto();
        $dto->vendor = 'anthropic';
        $dto->id = $result['id'] ?? null;
        $dto->status = $result['processing_status'] ?? null;
        $dto->type = $result['type'] ?? "message_batch";
        $dto->createdAt = $result['created_at'] ?? null;
        $dto->endedAt = $result['ended_at'] ?? null;
        $dto->expiresAt = $result['expires_at'] ?? null;
        $dto->cancelInitiatedAt = $result['cancel_initiated_at'] ?? null;
        $dto->processing = $result['request_counts']['processing'] ?? 0;
        $dto->succeeded = $result['request_counts']['succeeded'] ?? 0;
        $dto->errored = $result['request_counts']['errored'] ?? 0;
        $dto->canceled = $result['request_counts']['canceled'] ?? 0;
        $dto->expired = $result['request_counts']['expired'] ?? 0;
        return $dto;
    }
}