<?php

namespace Slider23\PhpLlmToolbox\Dto;

class OpenrouterResponseMapper
{
    public static function makeDto(array $responseArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "openrouter";
        
        if(isset($responseArray['error'])){
            $dto->status = "error";
            $dto->errorMessage = $responseArray['error']['message'] ?? null;
            $dto->httpStatusCode = $responseArray['error']['code'] ?? null;
            return $dto;
        }
        
        $dto->status = "success";
        $dto->rawResponse = $responseArray;
        $dto->id = $responseArray['id'] ?? null;
        $dto->model = $responseArray['model'] ?? null;
        if(isset($responseArray['choices'][0])){
            $dto->assistant_content = $responseArray['choices'][0]['message']['content'] ?? null;
            $dto->finish_reason = $responseArray['choices'][0]['finish_reason'] ?? null;
        }
        if(isset($responseArray['usage'])){
            $dto->inputTokens = $responseArray['usage']['prompt_tokens'] ?? null;
            $dto->outputTokens = $responseArray['usage']['completion_tokens'] ?? null;
            $dto->totalTokens = $responseArray['usage']['total_tokens'] ?? null;
            $dto->cost = 0; // Cost calculation can be added here if needed
        }
        
        $dto->_extractThinking();
        return $dto;
    }
}