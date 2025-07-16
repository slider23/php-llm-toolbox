<?php

namespace Slider23\PhpLlmToolbox\Tools;

use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class ToolExecutor
{
    /** @var ToolInterface[] */
    private array $tools = [];

    public function registerTool(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function registerTools(array $tools): void
    {
        foreach ($tools as $tool) {
            $this->registerTool($tool);
        }
    }

    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function getToolDefinitions(): array
    {
        return array_values(array_map(fn(ToolInterface $tool) => $tool->getDefinition(), $this->tools));
    }

    public function executeTool(string $name, array $parameters): ToolResult
    {
        $tool = $this->getTool($name);
        if (!$tool) {
            return ToolResult::error("Tool '{$name}' not found");
        }

        try {
            return $tool->execute($parameters);
        } catch (\Exception $e) {
            return ToolResult::error("Tool execution failed: " . $e->getMessage());
        }
    }

    /**
     * Execute tool calls from LLM response
     */
    public function executeToolCalls(array $toolCalls): array
    {
        $results = [];
        
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'] ?? '';
            $parameters = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $results[] = [
                    'tool_call_id' => $toolCall['id'] ?? '',
                    'role' => 'tool',
                    'content' => json_encode(['error' => 'Invalid JSON in tool arguments'])
                ];
                continue;
            }

            $result = $this->executeTool($toolName, $parameters);
            
            $results[] = [
                'tool_call_id' => $toolCall['id'] ?? '',
                'role' => 'tool',
                'content' => json_encode($result->toArray())
            ];
        }

        return $results;
    }
}