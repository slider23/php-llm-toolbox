<?php

namespace Slider23\PhpLlmToolbox\Clients;

use JsonException;
use OpenAI\Exceptions\UnserializableResponse;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

abstract class LlmVendorClient
{
    private bool $isDebug = false;

    public function enableDebug()
    {
        $this->isDebug = true;
    }

    public function disableDebug()
    {
        $this->isDebug = false;
    }

    public function debug(array $data): void
    {
        if($this->isDebug) {
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

    public function throwIfError($curl, array $response): void
    {
        if (isset($response['error'])) {
            throw new LlmRequestException($response['error']);
        }
        if(curl_errno($curl)) {
            $error = curl_error($curl);
            $errorCode = curl_errno($curl);
            throw new LlmRequestException("CURL Error: ".$error, $errorCode);
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
