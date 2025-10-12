<?php

namespace Slider23\PhpLlmToolbox\Clients;

use JsonException;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

abstract class LlmVendorClient
{
    public array $body = [];
    public array $response = [];
    public bool $debug = false;
    public bool $forceProxy = true; // use proxy by default

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function debug(array $data): void
    {
        if($this->debug) {
            if(function_exists("trap")) trap($data);
        }
    }
    public function normalizeMessagesArray(array $messages): array
    {
        $twoDimensionsArray = [];
        if(isset($messages['content'])) {
            // when a single element is passed
            $twoDimensionsArray[] = $messages;
        }else{
            // when an array of conversation history or few-shot is passed
            foreach ($messages as $message) {
                $twoDimensionsArray[] = $message;
            }
        }
        return $twoDimensionsArray;
    }

    abstract public function request(array $messages): LlmResponseDto;

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

    public function enableRequestWithoutProxy()
    {
        $this->forceProxy = false;
    }

    public function getRequestAndResponse()
    {
        return [$this->body, $this->response];
    }
}
