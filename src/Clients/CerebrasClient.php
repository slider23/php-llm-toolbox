<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\CerebrasResponseMapper;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenaiResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Exceptions\ProxyException;
use Slider23\PhpLlmToolbox\Tools\ToolAwareTrait;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;
use Slider23\PhpLlmToolbox\Traits\SOTrait;

class CerebrasClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ToolAwareTrait;
    use ProxyTrait;
    use SOTrait;

    public string $url = 'https://api.cerebras.ai/v1/chat/completions';
    public string $model;
    public string $apiKey;
    public int $timeout = 30;
    public int $max_tokens = 40_000;
//    public ?string $response_format = null; // null, {'type': 'json_object'}, { "type": "json_schema", "json_schema": { "name": "schema_name", "strict": true, "schema": {...} } }
    public string $reasoning_effort = 'low'; // 'low', 'medium', 'high' ; only for gpt-oss-120b model
    public ?int $seed = null;
    public ?string $stop = null;
    public ?bool $stream = null;
    public float $temperature = 0.2;
    public int $top_p = 1;
    public array $tools = [];
    public ?string $user = null;
    public bool $logprobs = false;
    public int $top_logprobs = 1;


    public function __construct(?string $model, string $apiKey)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

//    public function setSchema(string $schema, bool $strict = true, ?string $name = null)
//    {
//        $strictString = $strict ? 'true' : 'false';
//        $schema_name = "";
//        if($name) {
//            $schema_name = '"name": "'.$name.'",';
//        }
//
//        $this->response_format = '{ "type": "json_schema", "json_schema": { '.$schema_name.' "strict": '.$strictString.', "schema": '.$schema.' } }';
//    }

    public function setBody(array $messages): void
    {
        $body = [
            'model' => $this->model,
            'messages' => $this->normalizeMessagesArray($messages),
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'max_completion_tokens' => $this->max_tokens,
            'reasoning_effort' => $this->reasoning_effort,
        ];

        if($this->response_format) $body['response_format'] = $this->response_format;
        if($this->seed) $body['seed'] = $this->seed;
        if($this->stop) $body['stop'] = $this->stop;
        if($this->stream) $body['stream'] = $this->stream;
        if($this->user) $body['user'] = $this->user;
        if($this->logprobs) {
            $body['logprobs'] = $this->logprobs;
            $body['top_logprobs'] = $this->top_logprobs;
        }

        // Add tools if available
        if ($this->hasTools()) {
            $body['tools'] = $this->toolExecutor->getToolDefinitions();
        }

        $this->body = $body;
    }

    public function request(array $messages = null): LlmResponseDto
    {
        if ($messages) $this->setBody($messages);

        if($this->proxyHost){
            $this->checkProxy();
        }else{
            if($this->forceProxy){
                throw new ProxyException("Proxy is required for Cerebras API access.");
            }
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url,
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
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);
        if($http_code !== 200) {
            if(isset($result['message'])) throw new LlmVendorException($result['message']);
            else throw new LlmVendorException("Unknown error from vendor");
        }

        $this->response = $result;

        $dto = CerebrasResponseMapper::makeDto($result);
        if ($dto->status == "error") {
            throw new LlmVendorException("OpenAI error: " . $dto->errorMessage);
        }
        return $dto;
    }

    public function getModels()
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.cerebras.ai/v1/models',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        $this->applyProxy($curl);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true)['data'] ?? [];
    }
}