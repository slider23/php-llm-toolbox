<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
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

// Setup tools
$toolExecutor = new ToolExecutor();
$toolExecutor->registerTools([
    new CalculatorTool(),
    new WeatherTool(),
    new TimeTool()
]);

// Prepare messages
$messages = [
    SystemMessage::make('You are a helpful assistant with access to tools. Use the appropriate tools to answer user questions.'),
    UserMessage::make('What is 15 + 27? Also, what time is it now?')
];

echo "=== OpenRouter Client Example ===\n";

$openrouterApiKey = $_ENV['OPENROUTER_API_KEY'] ?? null;
if ($openrouterApiKey) {
    $client = new OpenrouterClient('anthropic/claude-3-5-sonnet', $openrouterApiKey);
    $client->setToolExecutor($toolExecutor);
    $client->timeout = 30;

    try {
        $response = $client->request($messages);
        
        echo "Response: " . $response->assistantContent . "\n";
        echo "Tools used: " . ($response->toolsUsed ? 'Yes' : 'No') . "\n";
        
        if ($response->toolsUsed) {
            echo "Number of tool calls: " . count($response->toolCalls) . "\n";
            
            foreach ($response->toolCalls as $i => $toolCall) {
                echo "Tool " . ($i + 1) . ": " . $toolCall['function']['name'] . "\n";
                echo "  Arguments: " . $toolCall['function']['arguments'] . "\n";
                
                // Execute tool
                $toolResult = $toolExecutor->executeTool(
                    $toolCall['function']['name'],
                    json_decode($toolCall['function']['arguments'], true)
                );
                
                echo "  Result: " . ($toolResult->success ? 'Success' : 'Failed') . "\n";
                if ($toolResult->success) {
                    echo "  Data: " . json_encode($toolResult->data) . "\n";
                } else {
                    echo "  Error: " . $toolResult->error . "\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "OpenRouter Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "OpenRouter API key not configured\n";
}

echo "\n=== Anthropic Client Example ===\n";

$anthropicApiKey = $_ENV['ANTHROPIC_API_KEY'] ?? null;
if ($anthropicApiKey) {
    $client = new AnthropicClient('claude-3-5-sonnet-20241022', $anthropicApiKey);
    $client->setToolExecutor($toolExecutor);
    $client->timeout = 30;

    try {
        $response = $client->request($messages);
        
        echo "Response: " . $response->assistantContent . "\n";
        echo "Tools used: " . ($response->toolsUsed ? 'Yes' : 'No') . "\n";
        
        if ($response->toolsUsed) {
            echo "Number of tool calls: " . count($response->toolCalls) . "\n";
            
            foreach ($response->toolCalls as $i => $toolCall) {
                echo "Tool " . ($i + 1) . ": " . $toolCall['function']['name'] . "\n";
                echo "  Arguments: " . $toolCall['function']['arguments'] . "\n";
                
                // Execute tool
                $toolResult = $toolExecutor->executeTool(
                    $toolCall['function']['name'],
                    json_decode($toolCall['function']['arguments'], true)
                );
                
                echo "  Result: " . ($toolResult->success ? 'Success' : 'Failed') . "\n";
                if ($toolResult->success) {
                    echo "  Data: " . json_encode($toolResult->data) . "\n";
                } else {
                    echo "  Error: " . $toolResult->error . "\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "Anthropic Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Anthropic API key not configured\n";
}