<?php

namespace Slider23\PhpLlmToolbox;

class Updater
{
    public static function updateModels(string $vendor, string $apiKey): array
    {
        $curl = curl_init();

        if($vendor === 'openrouter' OR $vendor === 'all') {

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://openrouter.ai/api/v1/models",
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => "Error: Unable to fetch models from OpenRouter. HTTP Code: $httpCode"
                ];
            }
            $responseArray = json_decode($response, true);
            if (isset($responseArray['error'])) {
                return [
                    'status' => 'error',
                    'message' => "Error: {$responseArray['error']}"
                ];
            }

            $modelsById = [];
            $numModels = count($responseArray['data']);
            foreach($responseArray['data'] as $model) {
                if(isset($model['id'])){
                    $modelsById[$model['id']] = $model;
                }
            }

            $path =  './resources/openrouter_models.json';
            file_put_contents($path, json_encode($modelsById, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return [
            'status' => 'success',
            'message' => "Vendor: $vendor . Updated $numModels models.",
        ];
    }
}