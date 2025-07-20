<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\Mappers\VoyageRerankingMapper;
use Slider23\PhpLlmToolbox\Dto\RerankingDto;

class VoyageRerankingClient
{
    use \Slider23\PhpLlmToolbox\Traits\ClientTrait;

    public string $model;
    public string $apiKey;

    // https://docs.voyageai.com/reference/reranker-api
    public ?int $top_k = null;
    public bool $return_documents = false;
    public bool $truncation = false;

    public function __construct(string $model, string $apiKey)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function rerank(string $query, array $documents): RerankingDto
    {
        $body = [
            'model' => $this->model,
            'documents' => $documents,
            'query' => $query
        ];

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.voyage.com/v1/rerank',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 60 // Default timeout in seconds
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = VoyageRerankingMapper::makeRerankingDto($result);

        return $dto;
    }

}