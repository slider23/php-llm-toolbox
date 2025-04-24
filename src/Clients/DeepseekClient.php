<?php

namespace App\Services\AiVendors;

use App\Dto\AnthropicResponseDto;
use OpenAI;

class DeepseekClient
{
    public string $model;
    public string $apiKey;
    public int $maxTokens = 8192;
    public string $responseFormat = 'text'; // json

    public array $price = [
        "deepseek-chat" => [

        ]
    ];
    public OpenAI\Factory $openaiClient;

    public function __construct(string $model = "deepseek-chat", string $apiKey = null)
    {
        $this->model = $model;
        $this->apiKey = $apiKey ?? config('services.deepseek.api_key');
        $this->openaiClient = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri('https://api.deepseek.com/beta');
    }

    public function request(array $messages)
    {
        $preparedMessages = [];
        if(isset($messages['role']) && isset($messages['content'])) {
            // вариант, когда передан один элемент
            $preparedMessages[] = $messages;
        }else{
            // вариант, когда передан массив истории переписки или few-shot
            foreach ($messages as $message) {
                $preparedMessages[] = $message;
            }
        }

        $body = [
            "messages" => $preparedMessages,
            'model' => $this->model,
            'frequency_penalty' => 0,
            'max_tokens' => $this->maxTokens,
            'presence_penalty' => 0,
            'response_format' => [
                'type' => $this->responseFormat
            ],
            'stop' => null,
            'stream' => false,
            'stream_options' => null,
            'temperature' => 1,
            'top_p' => 1,
            'tools' => null,
            'tool_choice' => 'none',
            'logprobs' => false,
            'top_logprobs' => null
        ];
//        trap($body);
        $bodyJson = json_encode($body);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.deepseek.com/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $bodyJson,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '.$this->apiKey
            ),
        ));
        $response = curl_exec($curl);
        trap($response);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($statusCode != 200){
            trap($body);
            throw new \Exception("Deepseek API error: $response");
        }
        curl_close($curl);
        $result = json_decode($response, true);
        trap(json_last_error_msg());
//        trap($result);
        return $result;
    }
}
