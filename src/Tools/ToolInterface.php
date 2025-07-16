<?php

namespace Slider23\PhpLlmToolbox\Tools;

interface ToolInterface
{
    /**
     * Get tool name for LLM
     */
    public function getName(): string;

    /**
     * Get tool description for LLM
     */
    public function getDescription(): string;

    /**
     * Get tool parameters schema for LLM
     */
    public function getParametersSchema(): array;

    /**
     * Execute tool with given parameters
     */
    public function execute(array $parameters): ToolResult;

    /**
     * Get tool definition for LLM API
     */
    public function getDefinition(): array;
}