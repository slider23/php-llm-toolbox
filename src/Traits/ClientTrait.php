<?php

namespace Slider23\PhpLlmToolbox\Traits;

use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

trait ClientTrait
{
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