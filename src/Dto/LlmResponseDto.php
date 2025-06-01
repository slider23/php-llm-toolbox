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
        $dto->assistant_content = $response['content'][0]['text'] ?? null;
        $dto->finish_reason = $response['stop_reason'] ?? null;
        if(isset($response['usage'])){
            if(!isset($pricesByModel[$response['model']])){
                $dto->cost = null;
            }else{
                $price = $pricesByModel[$response['model']];
                $dto->inputTokens = $response['usage']['input_tokens'] ?? null;
                $dto->outputTokens = $response['usage']['output_tokens'] ?? null;
                $dto->cacheReadInputTokens = $response['usage']['cache_creation_input_tokens'] ?? null;
                $dto->cacheCreationInputTokens = $response['usage']['cache_read_input_tokens'] ?? null;
                $dto->cost = $price['inputTokens'] * $dto->inputTokens
                    + $price['cacheCreationInputTokens'] * $dto->cacheCreationInputTokens
                    + $price['cacheReadInputTokens'] * $dto->cacheReadInputTokens
                    + $price['outputTokens'] * $dto->outputTokens;
            }
            $dto->totalTokens = max($dto->inputTokens, ($dto->cacheCreationInputTokens + $dto->cacheReadInputTokens)) + $dto->outputTokens;
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

    public static function fromOpenaiResponse(\OpenAI\Responses\Chat\CreateResponse $response, $vendor = ""): self
    {
        $pricesByModel = [
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
        ];

        $dto = new LlmResponseDto();
        $dto->vendor = $vendor;
        $dto->id = $response->id ?? null;
        $dto->model = $response->model ?? null;
        $dto->assistant_content = $response->choices[0]->message->content ?? null;
        $dto->_extractThinking();
        $dto->finish_reason = $response->choices[0]->finishReason ?? null;
        if(isset($response->usage)){
            if(!isset($pricesByModel[$response->model])){
                $dto->cost = null;
            }else{
                $price = $pricesByModel[$response->model];
                $dto->inputTokens = $response->usage->promptTokens ?? null;
                $dto->outputTokens = $response->usage->completionTokens ?? null;
//                $dto->cacheReadInputTokens = $response->usage->completionTokensDetails ?? null;
//                $dto->cacheCreationInputTokens = $response->usage->cacheCreationInputTokens ?? null;
                $dto->cost = $price['promptTokens'] * $dto->cacheCreationInputTokens
//                    + $price['cacheReadInputTokens'] * $dto->cacheReadInputTokens
                    + $price['outputTokens'] * $dto->outputTokens;
            }
            $dto->totalTokens = max($dto->inputTokens, ($dto->cacheCreationInputTokens + $dto->cacheReadInputTokens)) + $dto->outputTokens;
        }
        $dto->rawResponse = $response->toArray();

        return $dto;
    }

    public static function fromDeepseekResponse(array $result): LlmResponseDto
    {
        $pricesByModel = [
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
        ];
        $dto = new LlmResponseDto();
        $dto->rawResponse = $result;
        $dto->id = $result['id'] ?? null;
        $dto->model = $result['model'] ?? null;
        $dto->assistant_content = $result['choices'][0]['message']['content'] ?? null;
        $dto->assistant_reasoning_content = $result['choices'][0]['message']['reasoning_content'] ?? null;
        $dto->finish_reason = $result['finish_reason'] ?? null;
        if(isset($result['usage'])){
            $price = $pricesByModel[$result['model']] ?? null;
            if($price){
                $dto->cost =
                    $price['cacheReadInputTokens'] * $result['usage']['prompt_cache_hit_tokens']
                    + $price['cacheCreationInputTokens'] * $result['usage']['prompt_cache_miss_tokens']
                    + $price['outputTokens'] * $result['usage']['completion_tokens'];
                $dto->totalTokens = $result['usage']['total_tokens'];
            }
        }
        return $dto;
    }

    public static function fromOpenrouterResponse(array $resultArray): LlmResponseDto
    {
        $pricesByModel = [
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
                'outputTokens' =>                4.4  / 1_000_000,
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
        ];
        $dto = new LlmResponseDto();
        $dto->rawResponse = $resultArray;
        $dto->id = $resultArray['id'] ?? null;
        $dto->model = $resultArray['model'] ?? null;
        $dto->vendor = "openrouter";
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
