<?php

namespace Slider23\PhpLlmToolbox\Tools;

abstract class AbstractTool implements ToolInterface
{
    public function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => $this->getParametersSchema()
            ]
        ];
    }

    /**
     * Validate parameters against schema
     */
    protected function validateParameters(array $parameters): void
    {
        $schema = $this->getParametersSchema();
        
        if (!isset($schema['required'])) {
            return;
        }

        foreach ($schema['required'] as $requiredParam) {
            if (!isset($parameters[$requiredParam])) {
                throw new \InvalidArgumentException("Missing required parameter: {$requiredParam}");
            }
        }
    }

    /**
     * Get parameter with default value
     */
    protected function getParameter(array $parameters, string $name, mixed $default = null): mixed
    {
        return $parameters[$name] ?? $default;
    }
}