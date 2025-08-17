<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenaiResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Tools\ToolAwareTrait;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

class OpenaiClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ToolAwareTrait;
    use ProxyTrait;

    public string $model;
    public string $apiKey;
    public ?string $organization = null;
    public ?string $project = null;

    public int $timeout = 60;
    
    // https://platform.openai.com/docs/api-reference/chat/create
    public float $temperature = 1; // 0.0 to 2.0
    public float $top_p = 1; // 0.0 to 1.0
    public float $frequency_penalty = 0; // -2.0 to 2.0
    public float $presence_penalty = 0; // -2.0 to 2.0

    public int $max_completion_tokens = 4000;
    public ?array $logit_bias = null;
    public ?bool $logprobs = null;
    public ?int $top_logprobs = null;
    public ?array $response_format = null; // ["type" => "json_object"]
    public ?int $seed = null;
    public ?string $service_tier = null;
    public ?array $stop = null;
    public ?bool $stream = null;
    public ?array $tools = null;
    public ?array $tool_choice = null;
    public ?bool $parallel_tool_calls = null;
    public ?string $user = null;

    public function __construct(string $model, string $apiKey, ?string $organization = null, ?string $project = null)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->organization = $organization;
        $this->project = $project;
    }

    public function setBody(array $messages): void
    {
        $body = [
            'model' => $this->model,
            'messages' => $this->normalizeMessagesArray($messages),
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'frequency_penalty' => $this->frequency_penalty,
            'presence_penalty' => $this->presence_penalty,
        ];

        // Add optional parameters
        if (!is_null($this->max_completion_tokens)) $body['max_completion_tokens'] = $this->max_completion_tokens;
        if (!is_null($this->logit_bias)) $body['logit_bias'] = $this->logit_bias;
        if (!is_null($this->logprobs)) $body['logprobs'] = $this->logprobs;
        if (!is_null($this->top_logprobs)) $body['top_logprobs'] = $this->top_logprobs;
        if (!is_null($this->response_format)) $body['response_format'] = $this->response_format;
        if (!is_null($this->seed)) $body['seed'] = $this->seed;
        if (!is_null($this->service_tier)) $body['service_tier'] = $this->service_tier;
        if (!is_null($this->stop)) $body['stop'] = $this->stop;
        if (!is_null($this->stream)) $body['stream'] = $this->stream;
        if (!is_null($this->parallel_tool_calls)) $body['parallel_tool_calls'] = $this->parallel_tool_calls;
        if (!is_null($this->user)) $body['user'] = $this->user;
        
        // Add tools if available
        if ($this->hasTools()) {
            $body['tools'] = $this->toolExecutor->getToolDefinitions();
        }
        if (!is_null($this->tool_choice)) $body['tool_choice'] = $this->tool_choice;

        $this->body = $body;
    }

    public function request(array $messages = null): LlmResponseDto
    {
        if ($messages) $this->setBody($messages);
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        if ($this->organization) {
            $headers[] = 'OpenAI-Organization: ' . $this->organization;
        }
        
        if ($this->project) {
            $headers[] = 'OpenAI-Project: ' . $this->project;
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($this->body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        $this->applyProxy($curl);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }
        
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = OpenaiResponseMapper::makeDto($result);
        if ($dto->status == "error") {
            throw new LlmVendorException("OpenAI error: " . $dto->errorMessage);
        }
        return $dto;
    }



    public function moderateContent(string $input): array
    {
        $body = [
            'input' => $input,
            'model' => 'text-moderation-latest'
        ];

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        if ($this->organization) {
            $headers[] = 'OpenAI-Organization: ' . $this->organization;
        }
        
        if ($this->project) {
            $headers[] = 'OpenAI-Project: ' . $this->project;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.openai.com/v1/moderations',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        $this->applyProxy($curl);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        return $result;
    }
}