<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Tools\ToolExecutor;
use Slider23\PhpLlmToolbox\Tools\Examples\CalculatorTool;
use Slider23\PhpLlmToolbox\Tools\Examples\WeatherTool;
use Slider23\PhpLlmToolbox\Tools\Examples\TimeTool;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Get API key from environment
$apiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;

if (!$apiKey) {
    echo "Error: OPENROUTER_API_KEY not set in environment variables\n";
    exit(1);
}

// Create OpenRouter client
$client = new OpenrouterClient('anthropic/claude-3-5-sonnet', $apiKey);

// Setup tools
$toolExecutor = new ToolExecutor();
$toolExecutor->registerTools([
    new CalculatorTool(),
    new WeatherTool(),
    new TimeTool()
]);

// Integrate tools with client
$client->setToolExecutor($toolExecutor);

// Prepare messages
$messages = [
    SystemMessage::make('You are a helpful assistant with access to tools. Use the appropriate tools to answer user questions.'),
    UserMessage::make('What is 25 + 17? Also, what time is it now?')
];

echo "Making request to LLM with tools...\n";

try {
    $response = $client->request($messages);
    
    echo "Response received:\n";
    echo "Content: " . $response->assistantContent . "\n";
    echo "Model: " . $response->model . "\n";
    echo "Input tokens: " . $response->inputTokens . "\n";
    echo "Output tokens: " . $response->outputTokens . "\n";
    
    // Check if tools were called
    if (isset($response->rawResponse['choices'][0]['message']['tool_calls'])) {
        echo "\nTools were called:\n";
        $toolCalls = $response->rawResponse['choices'][0]['message']['tool_calls'];
        
        foreach ($toolCalls as $call) {
            echo "- Tool: " . $call['function']['name'] . "\n";
            echo "  Arguments: " . $call['function']['arguments'] . "\n";
            
            // Execute tool and show result
            $toolResult = $toolExecutor->executeTool(
                $call['function']['name'],
                json_decode($call['function']['arguments'], true)
            );
            
            echo "  Result: " . json_encode($toolResult->toArray(), JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}