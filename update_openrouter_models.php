<?php

include('vendor/autoload.php');

// read arguments from command line
$apiKey = $argv[1] ?? null;
if(!$apiKey) {
    // Try to read API key from .env file if not provided as argument
    if (file_exists('.env')) {
        $envFile = file_get_contents('.env');
        preg_match('/OPENROUTER_API_KEY=([^\s]+)/', $envFile, $matches);
        if (isset($matches[1])) {
            $apiKey = $matches[1];
        }
    }
}
if(!$apiKey) {
    echo "Usage: php update_openrouter_models.php <openrouterApiKey> or fill .env file: cp .env.example .env && nano .env " . PHP_EOL;
    exit(1);
}

$result = \Slider23\PhpLlmToolbox\Updater::updateModels("openrouter", $apiKey);
if ($result['status'] === 'success') {
    echo $result['message'] . PHP_EOL;
} else {
    echo "Error updating models: " . $result['message'] . PHP_EOL;
}