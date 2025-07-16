<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;
use Slider23\PhpLlmToolbox\Tools\AnthropicToolAdapter;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Get API key from environment
$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;

if (!$apiKey) {
    echo "Error: ANTHROPIC_API_KEY not set in environment variables\n";
    exit(1);
}

// Create Anthropic client
$client = new AnthropicClient('claude-3-5-sonnet-20241022', $apiKey);

// Setup tools
$toolExecutor = new ToolExecutor();
$toolExecutor->registerTools([
    new CalculatorTool(),
    new WeatherTool(),
    new TimeTool()
]);

// Integrate tools with client
$client->setToolExecutor($toolExecutor);

// Optional: Set tool choice to force using a specific tool
// $client->tool_choice = ["type" => "tool", "name" => "calculator"];

// Prepare messages
$messages = [
    SystemMessage::make('You are a helpful assistant with access to tools. Use the appropriate tools to answer user questions.'),
    UserMessage::make('What is 42 + 58? Also, what time is it now?')
];

echo "Making request to Anthropic Claude with tools...\n";

try {
    $response = $client->request($messages);
    
    echo "Response received:\n";
    echo "Content: " . $response->assistantContent . "\n";
    echo "Model: " . $response->model . "\n";
    echo "Input tokens: " . $response->inputTokens . "\n";
    echo "Output tokens: " . $response->outputTokens . "\n";
    echo "Cost: $" . $response->cost . "\n";
    
    // Check if tools were called
    if (isset($response->rawResponse['content'])) {
        $toolUsed = false;
        echo "\nTool usage analysis:\n";
        
        foreach ($response->rawResponse['content'] as $content) {
            if (isset($content['type']) && $content['type'] === 'tool_use') {
                $toolUsed = true;
                echo "- Tool used: " . $content['name'] . "\n";
                echo "  Tool ID: " . $content['id'] . "\n";
                echo "  Input: " . json_encode($content['input'], JSON_PRETTY_PRINT) . "\n";
                
                // Execute tool and show result
                $toolResult = $toolExecutor->executeTool($content['name'], $content['input']);
                echo "  Result: " . json_encode($toolResult->toArray(), JSON_PRETTY_PRINT) . "\n";
            }
        }
        
        if ($toolUsed) {
            echo "\nExtracting and executing all tool calls:\n";
            $toolCalls = AnthropicToolAdapter::extractToolCalls($response->rawResponse);
            $toolResults = $toolExecutor->executeToolCalls($toolCalls);
            
            foreach ($toolResults as $i => $result) {
                echo "Tool call " . ($i + 1) . ":\n";
                echo "  ID: " . $result['tool_call_id'] . "\n";
                echo "  Result: " . $result['content'] . "\n";
            }
        } else {
            echo "No tools were used in this response.\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}