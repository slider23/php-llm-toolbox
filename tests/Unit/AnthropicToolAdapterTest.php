<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Tools\AnthropicToolAdapter;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;

class AnthropicToolAdapterTest extends TestCase
{
    public function testConvertToolDefinition(): void
    {
        $calculator = new CalculatorTool();
        $anthropicTool = AnthropicToolAdapter::convertToolDefinition($calculator);
        
        $this->assertEquals('calculator', $anthropicTool['name']);
        $this->assertEquals('Perform basic mathematical calculations (addition, subtraction, multiplication, division)', $anthropicTool['description']);
        $this->assertArrayHasKey('input_schema', $anthropicTool);
        $this->assertEquals('object', $anthropicTool['input_schema']['type']);
        $this->assertArrayHasKey('properties', $anthropicTool['input_schema']);
        $this->assertArrayHasKey('required', $anthropicTool['input_schema']);
        
        // Check properties
        $this->assertArrayHasKey('operation', $anthropicTool['input_schema']['properties']);
        $this->assertArrayHasKey('a', $anthropicTool['input_schema']['properties']);
        $this->assertArrayHasKey('b', $anthropicTool['input_schema']['properties']);
        
        // Check required fields
        $this->assertContains('operation', $anthropicTool['input_schema']['required']);
        $this->assertContains('a', $anthropicTool['input_schema']['required']);
        $this->assertContains('b', $anthropicTool['input_schema']['required']);
    }

    public function testConvertToolDefinitions(): void
    {
        $tools = [
            'calculator' => new CalculatorTool(),
            'weather' => new WeatherTool(),
            'time' => new TimeTool()
        ];
        
        $anthropicTools = AnthropicToolAdapter::convertToolDefinitions($tools);
        
        $this->assertCount(3, $anthropicTools);
        
        foreach ($anthropicTools as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('input_schema', $tool);
        }
        
        $toolNames = array_column($anthropicTools, 'name');
        $this->assertContains('calculator', $toolNames);
        $this->assertContains('get_weather', $toolNames);
        $this->assertContains('get_current_time', $toolNames);
    }

    public function testExtractToolCalls(): void
    {
        $responseData = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I will calculate that for you.'
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'calculator',
                    'input' => [
                        'operation' => 'add',
                        'a' => 5,
                        'b' => 3
                    ]
                ]
            ]
        ];
        
        $toolCalls = AnthropicToolAdapter::extractToolCalls($responseData);
        
        $this->assertCount(1, $toolCalls);
        $this->assertEquals('toolu_123', $toolCalls[0]['id']);
        $this->assertEquals('calculator', $toolCalls[0]['function']['name']);
        
        $arguments = json_decode($toolCalls[0]['function']['arguments'], true);
        $this->assertEquals('add', $arguments['operation']);
        $this->assertEquals(5, $arguments['a']);
        $this->assertEquals(3, $arguments['b']);
    }

    public function testExtractToolCallsWithMultipleTools(): void
    {
        $responseData = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I will help you with both the calculation and the weather.'
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'calculator',
                    'input' => [
                        'operation' => 'multiply',
                        'a' => 7,
                        'b' => 6
                    ]
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_456',
                    'name' => 'get_weather',
                    'input' => [
                        'location' => 'Paris',
                        'units' => 'celsius'
                    ]
                ]
            ]
        ];
        
        $toolCalls = AnthropicToolAdapter::extractToolCalls($responseData);
        
        $this->assertCount(2, $toolCalls);
        
        // Check first tool call
        $this->assertEquals('toolu_123', $toolCalls[0]['id']);
        $this->assertEquals('calculator', $toolCalls[0]['function']['name']);
        $calculatorArgs = json_decode($toolCalls[0]['function']['arguments'], true);
        $this->assertEquals('multiply', $calculatorArgs['operation']);
        $this->assertEquals(7, $calculatorArgs['a']);
        $this->assertEquals(6, $calculatorArgs['b']);
        
        // Check second tool call
        $this->assertEquals('toolu_456', $toolCalls[1]['id']);
        $this->assertEquals('get_weather', $toolCalls[1]['function']['name']);
        $weatherArgs = json_decode($toolCalls[1]['function']['arguments'], true);
        $this->assertEquals('Paris', $weatherArgs['location']);
        $this->assertEquals('celsius', $weatherArgs['units']);
    }

    public function testExtractToolCallsWithNoTools(): void
    {
        $responseData = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'This is a regular text response without tools.'
                ]
            ]
        ];
        
        $toolCalls = AnthropicToolAdapter::extractToolCalls($responseData);
        
        $this->assertCount(0, $toolCalls);
    }

    public function testExtractToolCallsWithInvalidData(): void
    {
        $responseData = [
            'content' => 'Invalid format - should be array'
        ];
        
        $toolCalls = AnthropicToolAdapter::extractToolCalls($responseData);
        
        $this->assertCount(0, $toolCalls);
    }

    public function testFormatToolResults(): void
    {
        $toolResults = [
            [
                'tool_call_id' => 'toolu_123',
                'role' => 'tool',
                'content' => '{"success": true, "data": {"result": 8}}'
            ],
            [
                'tool_call_id' => 'toolu_456',
                'role' => 'tool',
                'content' => '{"success": true, "data": {"location": "Paris", "temperature": 22}}'
            ]
        ];
        
        $formattedResults = AnthropicToolAdapter::formatToolResults($toolResults);
        
        $this->assertCount(2, $formattedResults);
        
        // Check first result
        $this->assertEquals('tool_result', $formattedResults[0]['type']);
        $this->assertEquals('toolu_123', $formattedResults[0]['tool_use_id']);
        $this->assertEquals('{"success": true, "data": {"result": 8}}', $formattedResults[0]['content']);
        
        // Check second result
        $this->assertEquals('tool_result', $formattedResults[1]['type']);
        $this->assertEquals('toolu_456', $formattedResults[1]['tool_use_id']);
        $this->assertEquals('{"success": true, "data": {"location": "Paris", "temperature": 22}}', $formattedResults[1]['content']);
    }

    public function testFormatToolResultsWithEmptyArray(): void
    {
        $toolResults = [];
        
        $formattedResults = AnthropicToolAdapter::formatToolResults($toolResults);
        
        $this->assertCount(0, $formattedResults);
    }
}