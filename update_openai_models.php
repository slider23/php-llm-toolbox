<?php

include('vendor/autoload.php');

// read arguments from command line
$apiKey = $argv[1] ?? null;
if(!$apiKey) {
    // Try to read API key from .env file if not provided as argument
    if (file_exists('.env')) {
        $envFile = file_get_contents('.env');
        preg_match('/OPENAI_API_KEY=([^\s]+)/', $envFile, $matches);
        if (isset($matches[1])) {
            $apiKey = $matches[1];
        }
    }
}
if(!$apiKey) {
    echo "Usage: php update_openai_models.php <openaiApiKey> or fill .env file: cp .env.example .env && nano .env " . PHP_EOL;
    exit(1);
}

$result = \Slider23\PhpLlmToolbox\Updater::updateModels("openai", $apiKey);
if ($result['status'] === 'success') {
    echo $result['message'] . PHP_EOL;
} else {
    echo "Error updating models: " . $result['message'] . PHP_EOL;
}