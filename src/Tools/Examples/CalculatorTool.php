<?php

namespace Slider23\PhpLlmToolbox\Tools\Examples;

use Slider23\PhpLlmToolbox\Tools\AbstractTool;
use Slider23\PhpLlmToolbox\Tools\ToolResult;

class CalculatorTool extends AbstractTool
{
    public function getName(): string
    {
        return 'calculator';
    }

    public function getDescription(): string
    {
        return 'Perform basic mathematical calculations (addition, subtraction, multiplication, division)';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'operation' => [
                    'type' => 'string',
                    'enum' => ['add', 'subtract', 'multiply', 'divide'],
                    'description' => 'The mathematical operation to perform'
                ],
                'a' => [
                    'type' => 'number',
                    'description' => 'The first number'
                ],
                'b' => [
                    'type' => 'number',
                    'description' => 'The second number'
                ]
            ],
            'required' => ['operation', 'a', 'b']
        ];
    }

    public function execute(array $parameters): ToolResult
    {
        try {
            $this->validateParameters($parameters);
            
            $operation = $this->getParameter($parameters, 'operation');
            $a = $this->getParameter($parameters, 'a');
            $b = $this->getParameter($parameters, 'b');

            $result = match ($operation) {
                'add' => $a + $b,
                'subtract' => $a - $b,
                'multiply' => $a * $b,
                'divide' => $b != 0 ? $a / $b : throw new \InvalidArgumentException('Division by zero'),
                default => throw new \InvalidArgumentException('Invalid operation')
            };

            return ToolResult::success([
                'operation' => $operation,
                'a' => $a,
                'b' => $b,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return ToolResult::error($e->getMessage());
        }
    }
}