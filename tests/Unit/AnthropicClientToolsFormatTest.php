<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class AnthropicClientToolsFormatTest extends TestCase
{
    public function testAnthropicToolsFormat(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        
        $toolExecutor = new ToolExecutor();
        $toolExecutor->registerTools([
            new CalculatorTool(),
            new WeatherTool()
        ]);
        
        $client->setToolExecutor($toolExecutor);
        
        $messages = [
            SystemMessage::make('You are a helpful assistant.'),
            UserMessage::make('What is 5 + 3?')
        ];
        
        $client->setBody($messages);
        
        // Verify the body structure matches Anthropic API format
        $this->assertArrayHasKey('model', $client->body);
        $this->assertArrayHasKey('system', $client->body);
        $this->assertArrayHasKey('messages', $client->body);
        $this->assertArrayHasKey('max_tokens', $client->body);
        $this->assertArrayHasKey('temperature', $client->body);
        $this->assertArrayHasKey('tools', $client->body);
        
        // Verify tools format
        $tools = $client->body['tools'];
        $this->assertIsArray($tools);
        $this->assertCount(2, $tools);
        
        // Check first tool (calculator)
        $calculatorTool = $tools[0];
        $this->assertEquals('calculator', $calculatorTool['name']);
        $this->assertEquals('Perform basic mathematical calculations (addition, subtraction, multiplication, division)', $calculatorTool['description']);
        $this->assertArrayHasKey('input_schema', $calculatorTool);
        $this->assertEquals('object', $calculatorTool['input_schema']['type']);
        $this->assertArrayHasKey('properties', $calculatorTool['input_schema']);
        $this->assertArrayHasKey('required', $calculatorTool['input_schema']);
        
        // Check second tool (weather)
        $weatherTool = $tools[1];
        $this->assertEquals('get_weather', $weatherTool['name']);
        $this->assertEquals('Get current weather information for a given location', $weatherTool['description']);
        $this->assertArrayHasKey('input_schema', $weatherTool);
        $this->assertEquals('object', $weatherTool['input_schema']['type']);
        $this->assertArrayHasKey('properties', $weatherTool['input_schema']);
        $this->assertArrayHasKey('required', $weatherTool['input_schema']);
    }

    public function testAnthropicToolChoiceFormat(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        
        $toolExecutor = new ToolExecutor();
        $toolExecutor->registerTool(new CalculatorTool());
        $client->setToolExecutor($toolExecutor);
        
        // Test different tool_choice formats
        $client->tool_choice = ["type" => "tool", "name" => "calculator"];
        
        $messages = [
            SystemMessage::make('You are a helpful assistant.'),
            UserMessage::make('What is 5 + 3?')
        ];
        
        $client->setBody($messages);
        
        $this->assertArrayHasKey('tool_choice', $client->body);
        $this->assertEquals(["type" => "tool", "name" => "calculator"], $client->body['tool_choice']);
    }

    public function testAnthropicRequestBodyWithoutTools(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        
        $messages = [
            SystemMessage::make('You are a helpful assistant.'),
            UserMessage::make('Hello!')
        ];
        
        $client->setBody($messages);
        
        // Should not have tools in body when no tools are registered
        $this->assertArrayNotHasKey('tools', $client->body);
        $this->assertArrayNotHasKey('tool_choice', $client->body);
    }

    public function testAnthropicSystemMessageFormat(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        
        $toolExecutor = new ToolExecutor();
        $toolExecutor->registerTool(new CalculatorTool());
        $client->setToolExecutor($toolExecutor);
        
        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools.'),
            UserMessage::make('What is 10 + 5?')
        ];
        
        $client->setBody($messages);
        
        // Check system message format
        $this->assertArrayHasKey('system', $client->body);
        $systemMessage = $client->body['system'];
        
        $this->assertIsArray($systemMessage);
        $this->assertCount(1, $systemMessage);
        $this->assertEquals('text', $systemMessage[0]['type']);
        $this->assertEquals('You are a helpful assistant with access to tools.', $systemMessage[0]['text']);
    }

    public function testAnthropicMessagesFormat(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        
        $toolExecutor = new ToolExecutor();
        $toolExecutor->registerTool(new CalculatorTool());
        $client->setToolExecutor($toolExecutor);
        
        $messages = [
            SystemMessage::make('You are a helpful assistant.'),
            UserMessage::make('What is 7 + 8?')
        ];
        
        $client->setBody($messages);
        
        // Check messages format (should not include system message)
        $this->assertArrayHasKey('messages', $client->body);
        $messages = $client->body['messages'];
        
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages); // Only user message, system is separate
        $this->assertEquals('user', $messages[0]['role']);
        $this->assertEquals('What is 7 + 8?', $messages[0]['content']);
    }

    public function testAnthropicParametersFormat(): void
    {
        $client = new AnthropicClient('claude-3-5-sonnet-20241022', 'test-key');
        $client->max_tokens = 1000;
        $client->temperature = 0.7;
        
        $toolExecutor = new ToolExecutor();
        $toolExecutor->registerTool(new CalculatorTool());
        $client->setToolExecutor($toolExecutor);
        
        $messages = [
            SystemMessage::make('You are a helpful assistant.'),
            UserMessage::make('Hello!')
        ];
        
        $client->setBody($messages);
        
        // Check parameters
        $this->assertEquals('claude-3-5-sonnet-20241022', $client->body['model']);
        $this->assertEquals(1000, $client->body['max_tokens']);
        $this->assertEquals(0.7, $client->body['temperature']);
    }
}