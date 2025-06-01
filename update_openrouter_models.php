<?php

include('vendor/autoload.php');

$result = \Slider23\PhpLlmToolbox\Updater::updateModels();
if ($result['status'] === 'success') {
    echo $result['message'] . PHP_EOL;
} else {
    echo "Error updating models: " . $result['message'] . PHP_EOL;
}