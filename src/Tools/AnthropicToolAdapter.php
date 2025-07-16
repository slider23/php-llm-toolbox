<?php

namespace Slider23\PhpLlmToolbox\Tools;

class AnthropicToolAdapter
{
    /**
     * Convert standard tool definition to Anthropic format
     */
    public static function convertToolDefinition(ToolInterface $tool): array
    {
        return [
            'name' => $tool->getName(),
            'description' => $tool->getDescription(),
            'input_schema' => $tool->getParametersSchema()
        ];
    }

    /**
     * Convert multiple tools to Anthropic format
     */
    public static function convertToolDefinitions(array $tools): array
    {
        return array_values(array_map(
            fn(ToolInterface $tool) => self::convertToolDefinition($tool),
            $tools
        ));
    }

    /**
     * Extract tool calls from Anthropic response
     */
    public static function extractToolCalls(array $responseData): array
    {
        $toolCalls = [];
        
        if (!isset($responseData['content']) || !is_array($responseData['content'])) {
            return $toolCalls;
        }

        foreach ($responseData['content'] as $content) {
            if (isset($content['type']) && $content['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id' => $content['id'],
                    'function' => [
                        'name' => $content['name'],
                        'arguments' => json_encode($content['input'])
                    ]
                ];
            }
        }

        return $toolCalls;
    }

    /**
     * Format tool results for Anthropic API
     */
    public static function formatToolResults(array $toolResults): array
    {
        $formattedResults = [];
        
        foreach ($toolResults as $result) {
            $formattedResults[] = [
                'type' => 'tool_result',
                'tool_use_id' => $result['tool_call_id'],
                'content' => $result['content']
            ];
        }

        return $formattedResults;
    }
}