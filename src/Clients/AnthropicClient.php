<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

final class AnthropicClient
{
    public string $model;

    private string $apiKey;

    private ?string $apiVersion;

    public int $timeout = 180;

    public int $maxTokens = 3000; // 8192 max

    public float $temperature = 0;

    public int $maxAttempts = 3;

    public bool $debug = false;

    public function __construct(string $model = 'claude-3-5-sonnet-latest', ?string $apiKey = null, ?string $apiVersion = null)
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->apiVersion = $apiVersion;
    }

    private function _prepareMessagesArray(array $messages)
    {
        $preparedMessages = [];
        if (isset($messages['role']) && isset($messages['content'])) {
            // вариант, когда передан один элемент
            $preparedMessages[] = $messages;
        } else {
            // вариант, когда передан массив истории переписки или few-shot
            foreach ($messages as $message) {
                $preparedMessages[] = $message;
            }
        }

        return $preparedMessages;
    }

    public function request(array $messages)
    {
        $prompt = "";
        $filteredMessages = [];
        foreach($messages as $message) {
            if($message['role'] === 'system') {
                $prompt = $message['content'];
            }else{
                $filteredMessages[] = $message;
            }
        }

        $body = [
            'model' => $this->model,
            'system' => [
                [
                    'type' => 'text',
                    'text' => $prompt,
                    'cache_control' => ['type' => 'ephemeral'],
                ],
            ],
            'messages' => $this->_prepareMessagesArray($filteredMessages),
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.anthropic.com/v1/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }
        $response = curl_exec($curl);
        $content = $response;
        curl_close($curl);

        $result = json_decode($content, true);

        return LlmResponseDto::fromAnthropicResponse($result);
    }

    public function createMessageBatch(array $batchRequests): ?string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.anthropic.com/v1/messages/batches',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode(['requests' => $batchRequests]),
            CURLOPT_TIMEOUT => $this->timeout
        ]);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($curl);
        $result = json_decode($response, true);

        curl_close($curl);

        return $result['id'] ?? null;
    }

    public function retrieveMessageBatchInfo(string $batchId): array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.anthropic.com/v1/messages/batches/$batchId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_VERBOSE => $this->debug,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response, true);
        return $result;
    }

    public function retrieveMessageBatchResults(string $messageBatchId)
    {
//        trap("https://api.anthropic.com/v1/messages/batches/$messageBatchId/results");
        $guzzle = new \GuzzleHttp\Client;
        try {
            $response = $guzzle->get("https://api.anthropic.com/v1/messages/batches/$messageBatchId/results", [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ],
                'debug' => $this->debug,
                'timeout' => $this->timeout,
            ]);
            $content = $response->getBody()->getContents();

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.anthropic.com/v1/messages/batches/$messageBatchId/results",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'x-api-key: ' . $this->apiKey,
                    'anthropic-version: ' . $this->apiVersion,
                    'content-type: application/json',
                ],
                CURLOPT_TIMEOUT => $this->timeout,
            ]);
            $content = curl_exec($curl);
            if(curl_errno($curl)){

                $error_msg = curl_error($curl);
                // TODO log error
            }
            curl_close($curl);


//            trap($content);
            //            trap($response->getHeaders());
            $items = explode("\n", $content);
            $result = [];
            foreach ($items as $item) {
                //                trap($item);
                if (json_validate($item) === false) {
                    // TODO log error
                }
                $result[] = json_decode($item, true);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $result = null;
        }

        return $result;
    }

    public function cancelBatch(string $messageBatchId)
    {
        $guzzle = new \GuzzleHttp\Client;
        try {
            $response = $guzzle->post("https://api.anthropic.com/v1/messages/batches/$messageBatchId/cancel", [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ],
                'debug' => $this->debug,
                'timeout' => $this->timeout,
            ]);
            $content = $response->getBody()->getContents();
            //            trap($content);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $result = null;
            //            trap("error ".$e->getMessage());
        }

        return $result;
    }

    public function calculateTokens(string $text): ?int
    {
        $body = [
            'model' => $this->model,
            'messages' => $this->_prepareMessagesArray([
                'role' => 'user',
                'content' => $text,
            ]),
        ];
        $guzzle = new \GuzzleHttp\Client;
        $response = $guzzle->post('https://api.anthropic.com/v1/messages/count_tokens', [
            'headers' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ],
            'debug' => $this->debug,
            'timeout' => $this->timeout,
            'json' => $body,
        ]);
        $output = $response->getBody()->getContents();
        $result = json_decode($output, true);
        if ($response and is_null($result)) {
            throw new \Exception('Json decode error: ' . json_last_error_msg() . " | json: $output");
        }

        return $result['input_tokens'] ?? null;
    }
}
