<?php

namespace Slider23\PhpLlmToolbox\Traits;

trait SOTrait
{
    public ?array $response_format = null;

    public function setSchema(string $name, array $schemaArray, bool $strict = true)
    {
        if( !isset($schemaArray['type'])) $schemaArray['type'] = 'object';

        // Replace 'definitions' with '$defs' recursively
        $schemaArray = $this->replaceDefinitionsWithDefs($schemaArray);

        $response_format = [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => $name,
                'strict' => $strict,
                'schema' => $schemaArray
            ]
        ];
        $this->response_format = $response_format;
    }

    private function replaceDefinitionsWithDefs(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            // Replace 'definitions' key with '$defs'
            $newKey = $key === 'definitions' ? '$defs' : $key;

            if (is_array($value)) {
                // Recursively process arrays
                $result[$newKey] = $this->replaceDefinitionsWithDefs($value);
            } elseif (is_string($value)) {
                // Replace 'definitions' in string values (like JSON references)
                $result[$newKey] = str_replace('#/definitions/', '#/$defs/', $value);
            } else {
                $result[$newKey] = $value;
            }
        }

        // Add 'additionalProperties' => false if 'required' key exists at this level
        if (isset($result['required']) && !isset($result['additionalProperties'])) {
            $result['additionalProperties'] = false;
        }

        return $result;
    }
}