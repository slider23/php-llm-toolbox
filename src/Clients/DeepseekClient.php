<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\DeepseekResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

final class DeepseekClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ProxyTrait;

    public string $model;

    public string $apiKey;

    public int $timeout = 180; // seconds

    // https://api-docs.deepseek.com/api/create-chat-completion
    public int $max_tokens = 8192;
    public float $temperature = 1;
    public float $frequency_penalty = 0;
    public float $presence_penalty = 0;
    public string $response_format = 'text'; // text , json_object
    public float $top_p = 1;
    public ?string $stop = null;
    public bool $debug = false;


    public function __construct(string $model, string $apiKey)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function setBody(array $messages): void
    {
        $this->body = [
            'messages' => $this->normalizeMessagesArray($messages),
            'model' => $this->model,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'frequency_penalty' => $this->frequency_penalty,
            'presence_penalty' => $this->presence_penalty,
            'response_format' => [
                'type' => $this->response_format,
            ],
            'stop' => $this->stop,
        ];
    }

    public function request(array $messages = null): LlmResponseDto
    {
        if($messages) $this->setBody($messages);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.deepseek.com/chat/completions',
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
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = DeepseekResponseMapper::makeDto($result);
        if($dto->status == "error") {
            throw new LlmVendorException("Deepseek error: ".$dto->errorMessage);
        }
        return $dto;
    }
}
