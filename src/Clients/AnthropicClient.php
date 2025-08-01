<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\AnthropicResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;
use Slider23\PhpLlmToolbox\Helper;
use Slider23\PhpLlmToolbox\Tools\ToolAwareTrait;
use Slider23\PhpLlmToolbox\Tools\AnthropicToolAdapter;

final class AnthropicClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ToolAwareTrait;
    public string $model;

    private string $apiKey;

    private ?string $apiVersion;

    public int $timeout = 180;

    public int $max_tokens = 4000; // 8192 max
    public float $temperature = 0;
    public int $thinking = 0; // количество токенов, которые будут использованы для размышлений
    
    public ?array $tools = null;
    public ?array $tool_choice = null;

    public bool $debug = false;

    public function __construct(string $model, ?string $apiKey = null, ?string $apiVersion = "2023-06-01")
    {
        $this->model = $model;
        $this->apiKey = $apiKey;
        $this->apiVersion = $apiVersion;
    }

    public function setBody(array $messages): void
    {
        $systemArray = [];
        $filteredMessages = [];
        // Extract prompt to Anthropic-specific system message
        foreach($messages as $message) {
            if($message['role'] === 'system') {
                $systemArray = $message['content'];
                if(! is_array($systemArray)) {
                    $systemArray = [
                        'type' => 'text',
                        'text' => $systemArray,
                    ];
                }
                if(isset($systemArray['type'])) {
                    $systemArray = [$systemArray]; // Anthropic expects system message as an array of content - not {'type': 'text', 'text': '...'} but [{'type': 'text', 'text': '...'}]
                }
            }else{
                $filteredMessages[] = $message;
            }
        }

        $this->body = [
            'model' => $this->model,
            'system' => $systemArray,
            'messages' => $this->_prepareMessagesArray($filteredMessages),
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
        ];
        if($this->thinking > 0) {
            $this->body['thinking'] = [
                "type" => "enabled",
                "budget_tokens" => $this->thinking
            ];
        }
        
        // Add tools if available
        if ($this->hasTools()) {
            $this->body['tools'] = AnthropicToolAdapter::convertToolDefinitions($this->toolExecutor->getTools());
        }
        if ($this->tool_choice !== null) {
            $this->body['tool_choice'] = $this->tool_choice;
        }
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

    public function request(array $messages = null): LlmResponseDto
    {
        if($messages) $this->setBody($messages);

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
            CURLOPT_POSTFIELDS => json_encode($this->body),
            CURLOPT_TIMEOUT => $this->timeout
        ]);
        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = AnthropicResponseMapper::makeDto($result);
        if($dto->status == "error") {
            throw new LlmVendorException("Anthropic error: ".$dto->errorMessage);
        }
        return $dto;
    }

    /**
     * Override handleToolCalls to work with Anthropic's format
     */
    protected function handleToolCalls(array $responseData): ?array
    {
        if (!$this->hasTools()) {
            return null;
        }

        $toolCalls = AnthropicToolAdapter::extractToolCalls($responseData);
        
        if (empty($toolCalls)) {
            return null;
        }

        $toolResults = $this->toolExecutor->executeToolCalls($toolCalls);
        return AnthropicToolAdapter::formatToolResults($toolResults);
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

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        return $result;
    }

    public function retrieveMessageBatchResults(string $messageBatchId)
    {
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
        $response = curl_exec($curl);
        curl_close($curl);

        $items = explode("\n", $response);
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->jsonDecode($item);
        }
        return $result;
    }

    public function cancelBatch(string $messageBatchId): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.anthropic.com/v1/messages/batches/$messageBatchId/cancel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        curl_exec($curl);
        curl_close($curl);

        $this->throwIfError($curl);
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
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.anthropic.com/v1/messages/count_tokens',
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
        curl_close($curl);

        $result = json_decode($response, true);
        if ($response && is_null($result)) {
            throw new \Exception('Json decode error: ' . json_last_error_msg() . " | json: $response");
        }

        return $result['input_tokens'] ?? null;
    }
}
