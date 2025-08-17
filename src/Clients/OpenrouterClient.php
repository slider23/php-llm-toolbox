<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenrouterResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Tools\ToolAwareTrait;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

class OpenrouterClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ToolAwareTrait;
    use ProxyTrait;
    public string $model;
    public string $apiKey;

    public array $providers;

    public $timeout = 60;
    // https://openrouter.ai/docs/api-reference/parameters
    public float $temperature = 1; // 0.0 to 2.0
    public float $top_p = 1; // 0.0 to 1.0
    public float $top_k = 0; // 0 to 100
    public float $frequency_penalty = 0; // -2.0 to 2.0
    public float $presence_penalty = 0; // -2.0 to 2.0
    public float $repetition_penalty = 1; // 0 to 2.0
    public float $min_p = 0; // 0 to 1.0
    public float $top_a = 0; // 0 to 1.0

    public int $max_tokens = 4000;
    public ?array $logit_bias = null;
    public ?bool $logprobs = null;

    public ?int $top_logprobs = null;
    public ?array $response_format = null; // ["type" => "json_object"]
    public ?bool $structured_outputs = null;
    public ?array $stop = null;
    public ?array $tools = null;
    public ?array $tool_choice = null;

    public bool $include_reasoning = false;
    public string $http_referer = "php-llm-toolbox";
    public string $x_title = "php-llm-toolbox";


    public function __construct(string $model , string $apiKey, array $providers = [])
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->providers = $providers;
    }

    public function setBody(array $messages): void
    {
        $body = [
            'model' => $this->model,
            'messages' => $this->normalizeMessagesArray($messages),
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'top_k' => $this->top_k,
            'frequency_penalty' => $this->frequency_penalty,
            'presence_penalty' => $this->presence_penalty,
            'repetition_penalty' => $this->repetition_penalty,
            'min_p' => $this->min_p,
            'top_a' => $this->top_a,
            'include_reasoning' => $this->include_reasoning,
        ];
        if($this->providers AND count($this->providers) > 0) $body['provider'] = ["order" => $this->providers];
        if(!is_null($this->logit_bias)) $body['logit_bias'] = $this->logit_bias;
        if(!is_null($this->logprobs)) $body['logprobs'] = $this->logprobs;
        if(!is_null($this->top_logprobs)) $body['top_logprobs'] = $this->top_logprobs;
        if(!is_null($this->response_format)) $body['response_format'] = $this->response_format;
        if(!is_null($this->structured_outputs)) $body['structured_outputs'] = $this->structured_outputs;
        if(!is_null($this->stop)) $body['stop'] = $this->stop;
        if(!is_null($this->tools)) $body['tools'] = $this->tools;
        if(!is_null($this->tool_choice)) $body['tool_choice'] = $this->tool_choice;
        $this->body = $body;
    }


    public function request(array $messages = null): LlmResponseDto
    {
        if($messages) $this->setBody($messages);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://openrouter.ai/api/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: ' . $this->http_referer,
                'X-Title: ' . $this->x_title,
                'content-type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($this->body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = OpenrouterResponseMapper::makeDto($result);
        if($dto->status == "error") {
            throw new LlmVendorException("Openrouter error: ".$dto->errorMessage);
        }
        return $dto;
    }

    public function fetchCost(string $id): ?float
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://openrouter.ai/api/v1/generation?id='. $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'HTTP-Referer: ' . $this->http_referer,
                'content-type: application/json'
            ],
            CURLOPT_TIMEOUT => $this->timeout

        ]);
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        if(isset($result['error'])) {
            return null;
        }
        return $result['data']['total_cost'] ?? null;
    }
}
