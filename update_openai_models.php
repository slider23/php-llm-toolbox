<?php

include('vendor/autoload.php');

// CLI: php update_openai_models.php <OPENAI_API_KEY> [--file=output.json]
$apiKey = $argv[1] ?? null;

// Try to read from .env if not provided
if (!$apiKey) {
    if (file_exists('.env')) {
        $envFile = file_get_contents('.env');
        if (preg_match('/^OPENAI_API_KEY=([^\r\n#]+)/m', $envFile, $m)) {
            $apiKey = trim($m[1]);
        }
    }
}
if (!$apiKey) {
    fwrite(STDERR, "Usage: php update_openai_models.php <OPENAI_API_KEY> or set OPENAI_API_KEY in .env\n");
    exit(1);
}

// Parse optional --file=
$outputFile = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $outputFile = substr($arg, 7);
    }
}

/**
 * Fetch models list from OpenAI
 */
function fetchModels(string $apiKey): array
{
    $ch = curl_init('https://api.openai.com/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException('cURL error: ' . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 400) {
        throw new RuntimeException("OpenAI API error HTTP {$code}: {$raw}");
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        throw new RuntimeException('Unexpected API response structure');
    }
    return $data['data'];
}

/**
 * Pricing patterns.
 * NOTE: Values are examples based on public pricing pages (USD per 1K tokens).
 * cached_input_per_1k: if omitted => 25% of input_per_1k (OpenAI prompt caching discount).
 * Extend / adjust as pricing evolves.
 */
function pricingPatterns(): array
{
    return [
        [
            'pattern' => '/^gpt-4o$/',
            'pricing' => ['inputTokens' => 0.005, 'outputTokens' => 0.015],
        ],
        [
            'pattern' => '/^gpt-4o-mini$/',
            'pricing' => ['inputTokens' => 0.00015, 'outputTokens' => 0.0006],
        ],
        [
            'pattern' => '/^gpt-4\.1$/',
            'pricing' => ['inputTokens' => 0.01, 'outputTokens' => 0.03],
        ],
        [
            'pattern' => '/^gpt-4\.1-mini$/',
            'pricing' => ['inputTokens' => 0.003, 'outputTokens' => 0.009],
        ],
        [
            'pattern' => '/^gpt-4-turbo$/',
            'pricing' => ['inputTokens' => 0.01, 'outputTokens' => 0.03],
        ],
        [
            'pattern' => '/^gpt-3\.5-turbo/',
            'pricing' => ['inputTokens' => 0.0015, 'outputTokens' => 0.002],
        ],
        [
            'pattern' => '/^o3-mini$/',
            'pricing' => ['inputTokens' => 0.005, 'outputTokens' => 0.015],
        ],
        [
            'pattern' => '/^text-embedding-3-(small|large)$/',
            'pricing' => ['inputTokens' => 0.00002, 'outputTokens' => 0.00002],
        ],
    ];
}

/**
 * Attach pricing info to each model item.
 */
function attachPricing(array $models): array
{
    $patterns = pricingPatterns();
    foreach ($models as &$model) {
        $id = $model['id'] ?? '';
        $pricing = null;
        foreach ($patterns as $entry) {
            if (preg_match($entry['pattern'], $id)) {
                $pricing = $entry['pricing'];
                break;
            }
        }
        if ($pricing) {
            if (isset($pricing['inputTokens'])) {
                if (!isset($pricing['cacheCreationInputTokens'])) {
                    $pricing['cacheCreationInputTokens'] = $pricing['inputTokens'];
                }
                if (!isset($pricing['cacheReadInputTokens'])) {
                    $pricing['cacheReadInputTokens'] = round($pricing['inputTokens'] * 0.25, 9);
                }
            }
        }
        $model['pricing'] = $pricing ?? null;
    }
    unset($model);
    return $models;
}

try {
    $models = fetchModels($apiKey);
    // Normalize minimal fields + pricing
    $normalized = [];
    foreach ($models as $m) {
        $normalized[] = [
            'id'       => $m['id'] ?? null,
            'owned_by' => $m['owned_by'] ?? null,
            'created'  => $m['created'] ?? null,
            'object'   => $m['object'] ?? null,
        ];
    }
    $withPricing = attachPricing($normalized);

    // Новый формат: ассоциативный объект модель -> цены
    $modelsMap = [];
    foreach ($withPricing as $m) {
        if (!isset($m['pricing']) || !$m['pricing']) {
            continue; // пропускаем модели без конфигурированных цен
        }
        $p = $m['pricing'];
        // Гарантируем порядок ключей
        $modelsMap[$m['id']] = [
            'inputTokens' => $p['inputTokens'],
            'outputTokens' => $p['outputTokens'],
            'cacheCreationInputTokens' => $p['cacheCreationInputTokens'],
            'cacheReadInputTokens' => $p['cacheReadInputTokens'],
        ];
    }

    $result = [
        'vendor' => 'openai',
        'generated_at' => date('c'),
        'models' => $modelsMap,
        'pricing_note' => 'Models map: {modelId: {inputTokens, outputTokens, cacheCreationInputTokens (=input), cacheReadInputTokens (~25% of input)}}. Verify with official pricing.',
        'currency' => 'USD',
        'unit' => 'per_1k_tokens'
    ];

    $path = './resources/openai_models.json';
    $json = json_encode($modelsMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
    }
    file_put_contents($path, $json);
    echo "OpenAI models updated successfully. Saved: $path" . PHP_EOL;


} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}