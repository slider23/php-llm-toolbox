<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slider23\PhpLlmToolbox\Clients\OpenaiClient;
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
$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;

if (!$apiKey) {
    echo "Error: OPENAI_API_KEY not set in environment variables\n";
    exit(1);
}

echo "=== OpenAI Client Example ===\n";

// Create OpenAI client
$client = new OpenaiClient('gpt-4o-mini', $apiKey);

// Setup tools
$toolExecutor = new ToolExecutor();
$toolExecutor->registerTools([
    new CalculatorTool(),
    new WeatherTool(),
    new TimeTool()
]);

// Integrate tools with client
$client->setToolExecutor($toolExecutor);

// Optional: Configure tool behavior
$client->parallel_tool_calls = true;
$client->timeout = 30;

// Prepare messages
$messages = [
    SystemMessage::make('You are a helpful assistant with access to tools. Use the appropriate tools to answer user questions.'),
    UserMessage::make('What is 42 + 58? Also, what time is it now?')
];

echo "Making request to OpenAI with tools...\n";

try {
    $response = $client->request($messages);
    
    echo "Response received:\n";
    echo "Content: " . $response->assistantContent . "\n";
    echo "Model: " . $response->model . "\n";
    echo "Input tokens: " . $response->inputTokens . "\n";
    echo "Output tokens: " . $response->outputTokens . "\n";
    echo "Cost: $" . number_format($response->cost, 6) . "\n";
    echo "Tools used: " . ($response->toolsUsed ? 'Yes' : 'No') . "\n";
    
    if ($response->toolsUsed) {
        echo "Number of tool calls: " . count($response->toolCalls) . "\n";
        
        foreach ($response->toolCalls as $i => $toolCall) {
            echo "Tool " . ($i + 1) . ": " . $toolCall['function']['name'] . "\n";
            echo "  ID: " . $toolCall['id'] . "\n";
            echo "  Arguments: " . $toolCall['function']['arguments'] . "\n";
            
            // Execute tool and show result
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
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== OpenAI Client - Basic Usage (No Tools) ===\n";

// Example without tools
$basicClient = new OpenaiClient('gpt-4o-mini', $apiKey);
$basicClient->temperature = 0.7;
$basicClient->max_tokens = 100;

$basicMessages = [
    SystemMessage::make('You are a helpful assistant.'),
    UserMessage::make('Tell me a short joke about programming.')
];

try {
    $basicResponse = $basicClient->request($basicMessages);
    
    echo "Basic response:\n";
    echo "Content: " . $basicResponse->assistantContent . "\n";
    echo "Cost: $" . number_format($basicResponse->cost, 6) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== OpenAI Client - JSON Mode Example ===\n";

// Example with JSON response format
$jsonClient = new OpenaiClient('gpt-4o-mini', $apiKey);
$jsonClient->response_format = ["type" => "json_object"];

$jsonMessages = [
    SystemMessage::make('You are a helpful assistant designed to output JSON. Always respond with valid JSON.'),
    UserMessage::make('Create a JSON object with information about the color blue, including its RGB values and common associations.')
];

try {
    $jsonResponse = $jsonClient->request($jsonMessages);
    
    echo "JSON response:\n";
    echo "Content: " . $jsonResponse->assistantContent . "\n";
    
    // Verify it's valid JSON
    $decoded = json_decode($jsonResponse->assistantContent, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Valid JSON confirmed!\n";
        echo "Formatted: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Invalid JSON received\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== OpenAI Client - Embeddings Example ===\n";

try {
    $embedding = $client->createEmbedding("This is a test sentence for embedding generation.");
    
    echo "Embedding created successfully:\n";
    echo "Model: " . $embedding['model'] . "\n";
    echo "Dimensions: " . count($embedding['data'][0]['embedding']) . "\n";
    echo "First few values: " . json_encode(array_slice($embedding['data'][0]['embedding'], 0, 5)) . "\n";
    
} catch (Exception $e) {
    echo "Error creating embedding: " . $e->getMessage() . "\n";
}

echo "\n=== OpenAI Client - Moderation Example ===\n";

try {
    $moderation = $client->moderateContent("This is a normal, appropriate message for testing.");
    
    echo "Moderation result:\n";
    echo "Flagged: " . ($moderation['results'][0]['flagged'] ? 'Yes' : 'No') . "\n";
    echo "Categories: " . json_encode($moderation['results'][0]['categories']) . "\n";
    
} catch (Exception $e) {
    echo "Error with moderation: " . $e->getMessage() . "\n";
}