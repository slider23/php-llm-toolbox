<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenaiClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;

class OpenaiClientToolsTest extends TestCase
{
    private ?string $apiKey;
    private ToolExecutor $toolExecutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENAI_API_KEY') ?: $_ENV['OPENAI_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenAI API key not configured in environment variables (OPENAI_API_KEY) or contains placeholder value.'
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
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);

        $this->assertTrue($client->hasTools());
        $this->assertCount(3, $client->getToolExecutor()->getTools());
        
        $definitions = $client->getToolExecutor()->getToolDefinitions();
        $this->assertCount(3, $definitions);
        
        $toolNames = array_map(fn($def) => $def['function']['name'], $definitions);
        $this->assertContains('calculator', $toolNames);
        $this->assertContains('get_weather', $toolNames);
        $this->assertContains('get_current_time', $toolNames);
    }

    public function testCalculatorToolExecution(): void
    {
        $result = $this->toolExecutor->executeTool('calculator', [
            'operation' => 'add',
            'a' => 5,
            'b' => 3
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals(8, $result->data['result']);
        $this->assertEquals('add', $result->data['operation']);
    }

    public function testWeatherToolExecution(): void
    {
        $result = $this->toolExecutor->executeTool('get_weather', [
            'location' => 'New York',
            'units' => 'celsius'
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('New York', $result->data['location']);
        $this->assertEquals('celsius', $result->data['units']);
        $this->assertArrayHasKey('temperature', $result->data);
        $this->assertArrayHasKey('condition', $result->data);
    }

    public function testTimeToolExecution(): void
    {
        $result = $this->toolExecutor->executeTool('get_current_time', [
            'timezone' => 'UTC'
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('UTC', $result->data['timezone']);
        $this->assertArrayHasKey('current_time', $result->data);
        $this->assertArrayHasKey('timestamp', $result->data);
    }

    public function testLlmRequestWithToolsSupport(): void
    {
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 30;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the calculator tool to solve mathematical problems.'),
            UserMessage::make('What is 15 + 27?')
        ];

        try {
            $response = $client->request($messages);
            
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
//            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertEquals('tool_calls', $response->finishReason);
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openai', $response->vendor, "Response vendor should be 'openai'.");
            
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
                    
                    // Execute the tool call
                    $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                    $this->assertIsArray($toolResults);
                    $this->assertGreaterThan(0, count($toolResults));
                    
                    // Verify tool execution result
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
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 30;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the weather tool to get weather information.'),
            UserMessage::make('What is the weather like in Paris?')
        ];

        try {
            $response = $client->request($messages);
            
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
//            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertEquals('tool_calls', $response->finishReason);
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                
                // Execute the tool calls
                $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                $this->assertIsArray($toolResults);
                
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
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testLlmRequestWithTimeTool(): void
    {
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
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
//            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertEquals('tool_calls', $response->finishReason);
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                
                // Execute the tool calls
                $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                $this->assertIsArray($toolResults);
                
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
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testLlmRequestWithMultipleTools(): void
    {
        $client = new OpenaiClient("gpt-4o", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->timeout = 45;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools. Use the appropriate tools to answer user questions.'),
            UserMessage::make('What is 10 * 5 and what time is it now?')
        ];

        try {
            $response = $client->request($messages);
            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
//            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertEquals('tool_calls', $response->finishReason);
            
            // Check if tools were used in the response
            if ($response->toolsUsed) {
                $this->assertTrue($response->toolsUsed);
                $this->assertIsArray($response->toolCalls);
                
                // Execute the tool calls
                $toolResults = $client->getToolExecutor()->executeToolCalls($response->toolCalls);
                $this->assertIsArray($toolResults);
                
                // Check if multiple tools were used
                $toolNames = array_map(function($call) {
                    return $call['function']['name'];
                }, $response->toolCalls);
                
                // Should have used calculator and possibly time tool
                $this->assertContains('calculator', $toolNames);
            }
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testToolChoiceParameter(): void
    {
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->tool_choice = ["type" => "function", "function" => ["name" => "calculator"]];

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools.'),
            UserMessage::make('What is 5 + 3?')
        ];

        $client->setBody($messages);
        
        $this->assertArrayHasKey('tool_choice', $client->body);
        $this->assertEquals(["type" => "function", "function" => ["name" => "calculator"]], $client->body['tool_choice']);
    }

    public function testParallelToolCalls(): void
    {
        $client = new OpenaiClient("gpt-4o-mini", $this->apiKey);
        $client->setToolExecutor($this->toolExecutor);
        $client->parallel_tool_calls = true;

        $messages = [
            SystemMessage::make('You are a helpful assistant with access to tools.'),
            UserMessage::make('What is 8 + 2?')
        ];

        $client->setBody($messages);
        
        $this->assertArrayHasKey('parallel_tool_calls', $client->body);
        $this->assertTrue($client->body['parallel_tool_calls']);
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
}