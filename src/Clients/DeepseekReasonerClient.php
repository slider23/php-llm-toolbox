<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

final class DeepseekReasonerClient extends LlmVendorClient
{
    public string $model;

    public string $apiKey;

    public int $max_tokens = 8192;

    public float $temperature = 1;

    public float $top_p = 1;

    private bool $isDebug = false;

    public function __construct(?string $apiKey = null, string $model = 'deepseek-reasoner')
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function request(array $messages): LlmResponseDto
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.deepseek.com/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 360,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'messages' => $this->normalizeMessagesArray($messages),
                'model' => $this->model,
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
                'top_p' => $this->top_p,
            ]),
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

        $dto = LlmResponseDto::fromDeepseekResponse($result);

        return $dto;
    }
}
