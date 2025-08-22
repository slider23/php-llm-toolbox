<?php

namespace Slider23\PhpLlmToolbox\Clients;

use Slider23\PhpLlmToolbox\Dto\BatchInfoDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\AnthropicResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;
use Slider23\PhpLlmToolbox\Helper;
use Slider23\PhpLlmToolbox\Tools\ToolAwareTrait;
use Slider23\PhpLlmToolbox\Tools\AnthropicToolAdapter;
use Slider23\PhpLlmToolbox\Traits\ProxyTrait;

final class AnthropicClient extends LlmVendorClient implements LlmVendorClientInterface
{
    use ToolAwareTrait;
    use ProxyTrait;
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

    private function _extractSystemMessage(array $messages): array
    {
        $systemArray = [];
        $filteredMessages = [];
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
        return [$systemArray, $filteredMessages];
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
        $this->applyProxy($curl);
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
        if(empty($batchRequests)) {
            throw new \InvalidArgumentException('Batch requests cannot be empty.');
        }
        if(count($batchRequests) > 100_000) {
            throw new \InvalidArgumentException('Batch requests cannot exceed 100 items.');
        }
        if(isset($batchRequests['custom_id'])) {
            $batchRequests = [$batchRequests]; // Anthropic expects batch requests as an array of requests
        }

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
        $this->applyProxy($curl);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        $response = curl_exec($curl);
        $result = json_decode($response, true);

        curl_close($curl);

        return $result['id'] ?? null;
    }

    public function makeBatchRequest(array $messages, string $customId): array
    {
        [$prompt, $messages] = $this->_extractSystemMessage($messages);
        return [
            'custom_id' => $customId,
            'params' => [
                'model' => $this->model,
                'max_tokens' => $this->max_tokens,
                'temperature' => $this->temperature,
                'system' => $prompt,
                'messages' => $messages
            ]
        ];
    }

    public function retrieveMessageBatchInfo(string $batchId): BatchInfoDto
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
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

        $dto = AnthropicResponseMapper::makeBatchInfoDto($result);

        return $dto;
    }

    /**
     * @param string $batchId
     * @return array<LlmResponseDto>|null
     * @throws LlmVendorException
     * @throws WrongJsonException
     */
    public function retrieveMessageBatchResults(string $batchId): ?array
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.anthropic.com/v1/messages/batches/$batchId/results",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $items = explode("\n", $response);
        $arrayResponseDto = [];
        foreach ($items as $item) {
            $itemResult = $this->jsonDecode($item);
            if(isset($itemResult['error'])) {
                if($itemResult['error']['type'] === 'not_found_error') {
                    return null;
                } else {
                    throw new LlmVendorException("Error in batch result: " . $itemResult['error']['message']);
                }
            }
            $dto = AnthropicResponseMapper::makeDto($itemResult['result']['message'] ?? []);
            if($dto){
                $dto->customId = $itemResult['custom_id'];
                $result[$itemResult['custom_id']] = $dto;
            }
        }

        return $result;
    }

    public function cancelBatch(string $messageBatchId): BatchInfoDto
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.anthropic.com/v1/messages/batches/$messageBatchId/cancel",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        $this->applyProxy($curl);
        $response = curl_exec($curl);
        curl_close($curl);

        $result = $this->jsonDecode($response);
        $this->throwIfError($curl, $result);

//        dump("Cancel batch response:", $response);

        return AnthropicResponseMapper::makeBatchInfoDto($result);
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
        $this->applyProxy($curl);

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
