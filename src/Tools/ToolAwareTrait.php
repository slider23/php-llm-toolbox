<?php

namespace Slider23\PhpLlmToolbox\Tools;

trait ToolAwareTrait
{
    protected ?ToolExecutor $toolExecutor = null;

    public function setToolExecutor(ToolExecutor $toolExecutor): void
    {
        $this->toolExecutor = $toolExecutor;
        $this->tools = $toolExecutor->getToolDefinitions();
    }

    public function getToolExecutor(): ?ToolExecutor
    {
        return $this->toolExecutor;
    }

    public function hasTools(): bool
    {
        return $this->toolExecutor !== null && count($this->toolExecutor->getTools()) > 0;
    }

    /**
     * Check if response contains tool calls and execute them
     */
    protected function handleToolCalls(array $responseData): ?array
    {
        if (!$this->hasTools()) {
            return null;
        }

        $toolCalls = $responseData['choices'][0]['message']['tool_calls'] ?? null;
        
        if (!$toolCalls) {
            return null;
        }

        return $this->toolExecutor->executeToolCalls($toolCalls);
    }
}