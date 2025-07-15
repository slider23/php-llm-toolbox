<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\PerplexityResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class PerplexityClient extends LlmVendorClient implements LlmVendorClientInterface
{
    public string $apiKey;
    public string $model;
    public int $timeout = 180; // seconds
    public string $search_mode = 'web'; // 'web' or 'academic'
    public string $reasoning_effort = 'medium'; // 'low', 'medium', 'high' . Only for sonar-deep-research model
    public int $max_tokens = 8192;
    public float $temperature = 0.2;
    public float $top_p = 0.9;
    public array $search_domain_filter = []; // A list of domains to limit search results to. Currently limited to 10 domains for Allowlisting and Denylisting. For Denylisting, add a - at the beginning of the domain string.
    public bool $return_images = false; // Determines whether search results should include images.
    public bool $return_related_questions = false; // Determines whether related questions should be returned.
    public ?string $search_recency_filter = null; // 'hour', 'day', 'week', 'month' or 'year'
    public string $search_after_date_filter = ''; // Filters search results after a specific date (e.g., '2023-01-01').
    public string $search_before_date_filter = '';
    public int $top_k = 0;
    public bool $stream = false;
    public int $presence_penalty = 0;
    public int $frequency_penalty = 0;
    public ?array $response_format = null; // json schema
    public ?array $web_search_options = null;

    public function __construct(string $model , string $apiKey)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function setBody(array $messages): void
    {
        $body = [
            'messages' => $this->normalizeMessagesArray($messages),
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'search_mode' => $this->search_mode,
            'reasoning_effort' => $this->reasoning_effort,
            'search_domain_filter' => $this->search_domain_filter,
            'return_images' => $this->return_images,
            'return_related_questions' => $this->return_related_questions,
            'search_recency_filter' => $this->search_recency_filter,
            'search_after_date_filter' => $this->search_after_date_filter,
            'search_before_date_filter' => $this->search_before_date_filter,
            'top_k' => $this->top_k,
            'stream' => $this->stream,
            'presence_penalty' => $this->presence_penalty,
            'frequency_penalty' => $this->frequency_penalty,
        ];
        if($this->response_format) {
            $body['response_format'] = $this->response_format;
        }
        if($this->web_search_options) {
            $body['web_search_options'] = $this->web_search_options;
        }
        $this->body = $body;
    }

    public function request(array $messages = null): LlmResponseDto
    {
        if($messages) $this->setBody($messages);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.perplexity.ai/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($this->body),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = PerplexityResponseMapper::makeDto($result);
        if($dto->status == "error") {
            throw new LlmVendorException("Perplexity error: ".$dto->errorMessage);
        }
        return $dto;

    }
}