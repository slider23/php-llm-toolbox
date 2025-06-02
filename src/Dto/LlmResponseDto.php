<?php

namespace Slider23\PhpLlmToolbox\Dto;

use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class LlmResponseDto
{
    public ?string $id = null;
    public ?string $model = null;
    public ?string $vendor = null;
    public ?string $assistant_content = null;
    public ?string $assistant_reasoning_content = null;
    public ?string $finish_reason = null;

    public ?int $inputTokens = null;
    public ?int $outputTokens = null;
    public ?int $cacheCreationInputTokens = null;
    public ?int $cacheReadInputTokens = null;
    public ?int $totalTokens = null;
    public ?float $cost = null;
    public ?int $httpStatusCode = null;
    public ?string $status = null;
    public bool $error = false;
    public ?string $errorMessage = null;

    public array $rawResponse = [];

    public function __construct()
    {

    }

    public static function fromAnthropicResponse(array $response): self
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "anthropic";
        $dto->status = "success";
        $dto->rawResponse = $response;
        $dto->id = $response['id'] ?? null;
        $dto->model = $response['model'] ?? null;
        $dto->assistant_content = null;
        $dto->assistant_reasoning_content = null;
        foreach($response['content'] as $content){
            if($content['type'] === 'text'){
                $dto->assistant_content .= $content['text'];
            } elseif($content['type'] === 'thinking') {
                $dto->assistant_reasoning_content .= $content['thinking'];
            }
        }
        $dto->finish_reason = $response['stop_reason'] ?? null;
        if(isset($response['usage'])){
            $dto->inputTokens = $response['usage']['input_tokens'] ?? null;
            $dto->outputTokens = $response['usage']['output_tokens'] ?? null;
            $dto->cacheReadInputTokens = $response['usage']['cache_creation_input_tokens'] ?? null;
            $dto->cacheCreationInputTokens = $response['usage']['cache_read_input_tokens'] ?? null;
            $dto->totalTokens = max($dto->inputTokens, ($dto->cacheCreationInputTokens + $dto->cacheReadInputTokens)) + $dto->outputTokens;
            $dto = (new CostCalculator())->calculateCostToDto($dto);
        }
        if(isset($response['error'])){
            $type = $response['error']['type'] ?? null;
            $message = $response['error']['message'] ?? null;
            $dto->errorMessage = "[$type] $message";
            $dto->status = "error";
            $dto->error = true;
        }
        return $dto;
    }

    public static function fromDeepseekResponse(array $result): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "deepseek";
        $dto->status = "success";
        $dto->rawResponse = $result;
        $dto->id = $result['id'] ?? null;
        $dto->model = $result['model'] ?? null;
        $dto->assistant_content = $result['choices'][0]['message']['content'] ?? null;
        $dto->assistant_reasoning_content = $result['choices'][0]['message']['reasoning_content'] ?? null;
        $dto->finish_reason = $result['finish_reason'] ?? null;
        if(isset($result['usage'])){
            $dto->inputTokens = $result['usage']['prompt_tokens'];
            $dto->cacheCreationInputTokens = $result['usage']['prompt_cache_miss_tokens'] ?? 0;
            $dto->cacheReadInputTokens = $result['usage']['prompt_cache_hit_tokens'] ?? 0;
            $dto->outputTokens = $result['usage']['completion_tokens'] ?? null;
            $dto->totalTokens = $result['usage']['total_tokens'] ?? 0;
            $dto = (new CostCalculator())->calculateCostToDto($dto);
        }
        return $dto;
    }

    public static function fromOpenrouterResponse(array $resultArray): LlmResponseDto
    {
        $dto = new LlmResponseDto();
        $dto->vendor = "openrouter";
        $dto->status = "success";
        $dto->rawResponse = $resultArray;
        $dto->id = $resultArray['id'] ?? null;
        $dto->model = $resultArray['model'] ?? null;
        if(isset($resultArray['error'])){
            $dto->errorMessage = $resultArray['error']['message'] ?? null;
            $dto->httpStatusCode = $resultArray['error']['status'] ?? null;
            $dto->status = "error";
            throw new LlmVendorException("Openrouter error: " . $dto->errorMessage, $dto->httpStatusCode);
        }
        if(isset($resultArray['choices'][0])){
            $dto->assistant_content = $resultArray['choices'][0]['message']['content'] ?? null;
            $dto->finish_reason = $resultArray['choices'][0]['finish_reason'] ?? null;
        }
        if(isset($resultArray['usage'])){
            $dto->inputTokens = $resultArray['usage']['prompt_tokens'] ?? null;
            $dto->outputTokens = $resultArray['usage']['completion_tokens'] ?? null;
            $dto->totalTokens = $resultArray['usage']['total_tokens'] ?? null;
            $dto = (new CostCalculator())->calculateCostToDto($dto);
        }
        return $dto;
    }

    public function _extractThinking(): void
    {
        if(strpos($this->assistant_content, "<thinking>") !== false){
            preg_match("/<thinking>(.*?)<\/thinking>/", $this->assistant_content, $matches);
            $this->assistant_reasoning_content = $matches[1] ?? null;
            $this->assistant_content = preg_replace("/<thinking>.*?<\/thinking>/", "", $this->assistant_content);
        }
        if(strpos($this->assistant_content, "<think>") !== false){
            preg_match("/<think>(.*?)<\/think>/", $this->assistant_content, $matches);
            $this->assistant_reasoning_content = $matches[1] ?? null;
            $this->assistant_content = preg_replace("/<think>.*?<\/think>/", "", $this->assistant_content);
        }
    }
}
