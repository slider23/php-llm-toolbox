<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;

class OpenrouterClientToolsTest extends TestCase
{
    private ?string $apiKey;
    private ToolExecutor $toolExecutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENROUTER_API_KEY') ?: $_ENV['OPENROUTER_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenRouter API key not configured in environment variables (OPENROUTER_API_KEY) or contains placeholder value.'
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
        $client = new OpenrouterClient("openai/gpt-4o-mini", $this->apiKey);
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
        // Use context7 model as requested
        $client = new OpenrouterClient("anthropic/claude-3-5-sonnet", $this->apiKey);
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
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            
            // Check if tools were used in the response
            $rawResponse = $response->rawResponse;
            if (isset($rawResponse['choices'][0]['message']['tool_calls'])) {
                $toolCalls = $rawResponse['choices'][0]['message']['tool_calls'];
                $this->assertIsArray($toolCalls);
                $this->assertGreaterThan(0, count($toolCalls));
                
                // Verify calculator tool was called
                $calculatorCall = null;
                foreach ($toolCalls as $call) {
                    if ($call['function']['name'] === 'calculator') {
                        $calculatorCall = $call;
                        break;
                    }
                }
                
                if ($calculatorCall) {
                    $this->assertNotNull($calculatorCall);
                    $this->assertEquals('calculator', $calculatorCall['function']['name']);
                    
                    // Execute the tool call
                    $toolResults = $client->getToolExecutor()->executeToolCalls($toolCalls);
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
        $client = new OpenrouterClient("anthropic/claude-3-5-sonnet", $this->apiKey);
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
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            
            // Check if tools were used in the response
            $rawResponse = $response->rawResponse;
            if (isset($rawResponse['choices'][0]['message']['tool_calls'])) {
                $toolCalls = $rawResponse['choices'][0]['message']['tool_calls'];
                
                // Execute the tool calls
                $toolResults = $client->getToolExecutor()->executeToolCalls($toolCalls);
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