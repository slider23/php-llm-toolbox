<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenaiResponseMapper;
use Slider23\PhpLlmToolbox\Dto\Mappers\VoyageResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

class VoyageEmbeddingClient
{
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

    public function createEmbedding(string $input, ?string $inputType = null): EmbeddingDto
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

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = VoyageResponseMapper::makeEmbeddingDto($result);

        return $dto;
    }

    public function throwIfError($curl, ?array $response = null): void
    {
        if(curl_errno($curl)) {
            $error = curl_error($curl);
            $errorCode = curl_errno($curl);
            throw new LlmRequestException("CURL Error: ".$error, $errorCode);
        }
        if(!$response) {
            throw new LlmRequestException("CURL Error: empty answer from vendor");
        }
        if (isset($response['error'])) {
            throw new LlmRequestException($response['error']['message'] ?? 'Unknown error from vendor', (int)($response['error']['code'] ?? 0));
        }

    }

    public function jsonDecode(string $json)
    {
        try{
            return json_decode($json, true,  JSON_THROW_ON_ERROR);
        }catch (\JsonException $e){
            throw new WrongJsonException($e);
        }
    }
}