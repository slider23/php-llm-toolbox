<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;
use Slider23\PhpLlmToolbox\Tools\AnthropicToolAdapter;

class AnthropicClientToolsTest extends TestCase
{
    private ?string $apiKey;
    private ToolExecutor $toolExecutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('ANTHROPIC_API_KEY') ?: $_ENV['ANTHROPIC_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'Anthropic API key not configured in environment variables (ANTHROPIC_API_KEY) or contains placeholder value.'
            );
        }

        // Setup tool executor with example tools
        $this->toolExecutor = new ToolExecutor();
        $this->toolExecutor->registerTools([
            new CalculatorTool(),
            new WeatherTool(),
            new TimeTool()
        ]);
    }

    public function testToolsRegistration(): void
    {
        $client = new AnthropicClient("claude-3-5-sonnet-20241022", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);

        $this->assertTrue($client->hasTools());
        $this->assertCount(3, $client->getToolExecutor()->getTools());
        
        // Test Anthropic format conversion
        $tools = $client->getToolExecutor()->getTools();
        $anthropicTools = AnthropicToolAdapter::convertToolDefinitions($tools);
        
        $this->assertCount(3, $anthropicTools);
        
        // Verify structure for each tool
        foreach ($anthropicTools as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('input_schema', $tool);
            $this->assertArrayHasKey('type', $tool['input_schema']);
            $this->assertArrayHasKey('properties', $tool['input_schema']);
        }

        // Check specific tool names
        $toolNames = array_column($anthropicTools, 'name');
        $this->assertContains('calculator', $toolNames);
        $this->assertContains('get_weather', $toolNames);
        $this->assertContains('get_current_time', $toolNames);
    }

    public function testAnthropicToolAdapter(): void
    {
        $calculator = new CalculatorTool();
        $anthropicTool = AnthropicToolAdapter::convertToolDefinition($calculator);
        
        $this->assertEquals('calculator', $anthropicTool['name']);
        $this->assertEquals('Perform basic mathematical calculations (addition, subtraction, multiplication, division)', $anthropicTool['description']);
        $this->assertArrayHasKey('input_schema', $anthropicTool);
        $this->assertEquals('object', $anthropicTool['input_schema']['type']);
        $this->assertArrayHasKey('properties', $anthropicTool['input_schema']);
        $this->assertArrayHasKey('required', $anthropicTool['input_schema']);
        $this->assertContains('operation', $anthropicTool['input_schema']['required']);
        $this->assertContains('a', $anthropicTool['input_schema']['required']);
        $this->assertContains('b', $anthropicTool['input_schema']['required']);
    }

    public function testCalculatorToolExecution(): void
    {
        $result = $this->toolExecutor->executeTool('calculator', [
            'operation' => 'add',
            'a' => 15,
            'b' => 27
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals(42, $result->data['result']);
        $this->assertEquals('add', $result->data['operation']);
    }

    public function testLlmRequestWithToolsSupport(): void
    {
        $client = new AnthropicClient("claude-3-5-sonnet-20241022", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 30;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the calculator tool to solve mathematical problems.'),
            UserMessage::make('What is 25 + 17?')
        ];

        try {
            $response = $client->request($messages);
            
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('anthropic', $response->vendor, "Response vendor should be 'anthropic'.");
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                $this->assertGreaterThan(0, count($response->toolCalls));
                
                // Verify calculator tool was called
                $calculatorCall = null;
                foreach ($response->toolCalls as $call) {
                    if ($call['function']['name'] === 'calculator') {
                        $calculatorCall = $call;
                        break;
                    }
                }
                
                if ($calculatorCall) {
                    $this->assertNotNull($calculatorCall);
                    $this->assertEquals('calculator', $calculatorCall['function']['name']);
                    
                    $arguments = json_decode($calculatorCall['function']['arguments'], true);
                    $this->assertEquals('add', $arguments['operation']);
                    $this->assertEquals(25, $arguments['a']);
                    $this->assertEquals(17, $arguments['b']);
                    
                    // Execute tool and verify result
                    $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                    $this->assertIsArray($toolResults);
                    $this->assertGreaterThan(0, count($toolResults));
                    
                    $toolResult = json_decode($toolResults[0]['content'], true);
                    $this->assertTrue($toolResult['success']);
                    $this->assertEquals(42, $toolResult['data']['result']);
                }
            }
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testLlmRequestWithWeatherTool(): void
    {
        $client = new AnthropicClient("claude-3-5-sonnet-20241022", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 30;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the weather tool to get weather information.'),
            UserMessage::make('What is the weather like in London?')
        ];

        try {
            $response = $client->request($messages);
            
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                
                // Verify weather tool was called
                $weatherCall = null;
                foreach ($response->toolCalls as $call) {
                    if ($call['function']['name'] === 'get_weather') {
                        $weatherCall = $call;
                        break;
                    }
                }
                
                if ($weatherCall) {
                    $this->assertNotNull($weatherCall);
                    $this->assertEquals('get_weather', $weatherCall['function']['name']);
                    
                    $arguments = json_decode($weatherCall['function']['arguments'], true);
                    $this->assertArrayHasKey('location', $arguments);
                    
                    // Execute tool calls and verify result
                    $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                    
                    // Verify weather tool was used
                    foreach ($toolResults as $result) {
                        $toolResult = json_decode($result['content'], true);
                        if ($toolResult['success'] && isset($toolResult['data']['location'])) {
                            $this->assertArrayHasKey('temperature', $toolResult['data']);
                            $this->assertArrayHasKey('condition', $toolResult['data']);
                            break;
                        }
                    }
                }
            }
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testLlmRequestWithTimeTool(): void
    {
        $client = new AnthropicClient("claude-3-5-sonnet-20241022", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 30;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the time tool to get current time information.'),
            UserMessage::make('What time is it now?')
        ];

        try {
            $response = $client->request($messages);
            
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                
                // Verify time tool was called
                $timeCall = null;
                foreach ($response->toolCalls as $call) {
                    if ($call['function']['name'] === 'get_current_time') {
                        $timeCall = $call;
                        break;
                    }
                }
                
                if ($timeCall) {
                    $this->assertNotNull($timeCall);
                    $this->assertEquals('get_current_time', $timeCall['function']['name']);
                    
                    // Execute tool calls and verify result
                    $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                    
                    // Verify time tool was used
                    foreach ($toolResults as $result) {
                        $toolResult = json_decode($result['content'], true);
                        if ($toolResult['success'] && isset($toolResult['data']['current_time'])) {
                            $this->assertArrayHasKey('timestamp', $toolResult['data']);
                            $this->assertArrayHasKey('timezone', $toolResult['data']);
                            break;
                        }
                    }
                }
            }
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testToolErrorHandling(): void
    {
        $result = $this->toolExecutor->executeTool('calculator', [
            'operation' => 'divide',
            'a' => 10,
            'b' => 0
        ]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Division by zero', $result->error);
    }

    public function testInvalidToolCall(): void
    {
        $result = $this->toolExecutor->executeTool('nonexistent_tool', []);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Tool \'nonexistent_tool\' not found', $result->error);
    }

    public function testToolChoiceParameter(): void
    {
        $client = new AnthropicClient("claude-3-5-sonnet-20241022", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->tool_choice = ["type" => "tool", "name" => "calculator"];

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools.'),
            UserMessage::make('What is 5 + 3?')
        ];

        $client->setBody($messages);
        
        $this->assertArrayHasKey('tool_choice', $client->body);
        $this->assertEquals(["type" => "tool", "name" => "calculator"], $client->body['tool_choice']);
    }
}