<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\ToolResult;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;

class ToolsTest extends TestCase
{
    private ToolExecutor $toolExecutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->toolExecutor = new ToolExecutor();
    }

    public function testToolRegistration(): void
    {
        $calculator = new CalculatorTool();
        $this->toolExecutor->registerTool($calculator);

        $this->assertEquals($calculator, $this->toolExecutor->getTool('calculator'));
        $this->assertCount(1, $this->toolExecutor->getTools());
    }

    public function testMultipleToolsRegistration(): void
    {
        $tools = [
            new CalculatorTool(),
            new WeatherTool(),
            new TimeTool()
        ];

        $this->toolExecutor->registerTools($tools);

        $this->assertCount(3, $this->toolExecutor->getTools());
        $this->assertNotNull($this->toolExecutor->getTool('calculator'));
        $this->assertNotNull($this->toolExecutor->getTool('get_weather'));
        $this->assertNotNull($this->toolExecutor->getTool('get_current_time'));
    }

    public function testToolDefinitions(): void
    {
        $this->toolExecutor->registerTool(new CalculatorTool());
        $definitions = $this->toolExecutor->getToolDefinitions();

        $this->assertCount(1, $definitions);
        $this->assertEquals('function', $definitions[0]['type']);
        $this->assertEquals('calculator', $definitions[0]['function']['name']);
        $this->assertArrayHasKey('description', $definitions[0]['function']);
        $this->assertArrayHasKey('parameters', $definitions[0]['function']);
    }

    public function testCalculatorToolExecution(): void
    {
        $calculator = new CalculatorTool();
        
        // Test addition
        $result = $calculator->execute([
            'operation' => 'add',
            'a' => 5,
            'b' => 3
        ]);
        
        $this->assertTrue($result->success);
        $this->assertEquals(8, $result->data['result']);
        
        // Test multiplication
        $result = $calculator->execute([
            'operation' => 'multiply',
            'a' => 4,
            'b' => 7
        ]);
        
        $this->assertTrue($result->success);
        $this->assertEquals(28, $result->data['result']);
    }

    public function testCalculatorDivisionByZeroError(): void
    {
        $calculator = new CalculatorTool();
        
        $result = $calculator->execute([
            'operation' => 'divide',
            'a' => 10,
            'b' => 0
        ]);
        
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Division by zero', $result->error);
    }

    public function testWeatherToolExecution(): void
    {
        $weather = new WeatherTool();
        
        $result = $weather->execute([
            'location' => 'London',
            'units' => 'celsius'
        ]);
        
        $this->assertTrue($result->success);
        $this->assertEquals('London', $result->data['location']);
        $this->assertEquals('celsius', $result->data['units']);
        $this->assertArrayHasKey('temperature', $result->data);
        $this->assertArrayHasKey('condition', $result->data);
    }

    public function testTimeToolExecution(): void
    {
        $time = new TimeTool();
        
        $result = $time->execute([
            'timezone' => 'Europe/London',
            'format' => 'Y-m-d'
        ]);
        
        $this->assertTrue($result->success);
        $this->assertEquals('Europe/London', $result->data['timezone']);
        $this->assertArrayHasKey('current_time', $result->data);
        $this->assertArrayHasKey('timestamp', $result->data);
    }

    public function testToolResultSuccess(): void
    {
        $result = ToolResult::success(['test' => 'data']);
        
        $this->assertTrue($result->success);
        $this->assertEquals(['test' => 'data'], $result->data);
        $this->assertNull($result->error);
    }

    public function testToolResultError(): void
    {
        $result = ToolResult::error('Test error message');
        
        $this->assertFalse($result->success);
        $this->assertNull($result->data);
        $this->assertEquals('Test error message', $result->error);
    }

    public function testToolResultToArray(): void
    {
        $successResult = ToolResult::success(['key' => 'value']);
        $successArray = $successResult->toArray();
        
        $this->assertEquals([
            'success' => true,
            'data' => ['key' => 'value'],
            'error' => null
        ], $successArray);
        
        $errorResult = ToolResult::error('Error message');
        $errorArray = $errorResult->toArray();
        
        $this->assertEquals([
            'success' => false,
            'data' => null,
            'error' => 'Error message'
        ], $errorArray);
    }

    public function testToolCallsExecution(): void
    {
        $this->toolExecutor->registerTool(new CalculatorTool());
        
        $toolCalls = [
            [
                'id' => 'call_1',
                'function' => [
                    'name' => 'calculator',
                    'arguments' => json_encode([
                        'operation' => 'add',
                        'a' => 10,
                        'b' => 5
                    ])
                ]
            ]
        ];
        
        $results = $this->toolExecutor->executeToolCalls($toolCalls);
        
        $this->assertCount(1, $results);
        $this->assertEquals('call_1', $results[0]['tool_call_id']);
        $this->assertEquals('tool', $results[0]['role']);
        
        $content = json_decode($results[0]['content'], true);
        $this->assertTrue($content['success']);
        $this->assertEquals(15, $content['data']['result']);
    }

    public function testToolCallsWithInvalidJson(): void
    {
        $this->toolExecutor->registerTool(new CalculatorTool());
        
        $toolCalls = [
            [
                'id' => 'call_1',
                'function' => [
                    'name' => 'calculator',
                    'arguments' => 'invalid json'
                ]
            ]
        ];
        
        $results = $this->toolExecutor->executeToolCalls($toolCalls);
        
        $this->assertCount(1, $results);
        $content = json_decode($results[0]['content'], true);
        $this->assertArrayHasKey('error', $content);
        $this->assertStringContainsString('Invalid JSON', $content['error']);
    }
}