<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenaiResponseMapper;
use Slider23\PhpLlmToolbox\Dto\Mappers\VoyageResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;
use Slider23\PhpLlmToolbox\Traits\ClientTrait;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

class VoyageEmbeddingClient
{
    use ClientTrait;
    use ProxyTrait;

    public string $model;
    public string $apiKey;
    public int $timeout = 60; // Default timeout in seconds
    public bool $debug = false;

    //https://docs.voyageai.com/reference/embeddings-api
    public ?string $input_type = null; // null, "query" or "document"
    public bool $truncation = true;
    public ?int $output_dimension = null;
    public string $output_dtype = "float";
    public ?string $encoding_format = null;


    public function __construct(string $model, string $apiKey)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function embedding(string $input, ?string $inputType = null): EmbeddingDto
    {
        if(!$inputType AND $this->input_type){
            $inputType = $this->input_type;
        }
        $body = [
            'model' => $this->model,
            'input' => $input,
            'input_type' => $inputType,
            'truncation' => $this->truncation,
            'output_dimension' => $this->output_dimension,
            'output_dtype' => $this->output_dtype,
            'encoding_format' => $this->encoding_format
        ];

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.voyageai.com/v1/embeddings',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = VoyageResponseMapper::makeEmbeddingDto($result);

        return $dto;
    }
}