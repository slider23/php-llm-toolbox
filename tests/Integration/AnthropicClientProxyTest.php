<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class AnthropicClientProxyTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('ANTHROPIC_API_KEY') ? $_ENV['ANTHROPIC_API_KEY'] : null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'Anthropic API key not configured in environment variables (ANTHROPIC_API_KEY) or contains placeholder value.'
            );
        }
    }
    public function testSingleRequestViaProxy()
    {
        $model = "claude-3-5-haiku-20241022";
        $client = new AnthropicClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        $client->setProxy(getenv('PROXY_URL'), getenv('PROXY_LOGIN'), getenv('PROXY_PASSWORD'));

        echo "\nChecking proxy connectivity...\n";
        echo "Proxy URL: " . getenv('PROXY_URL') . "\n";
        $checkResult = $client->checkProxy();
        $proxyIp = explode(":",getenv('PROXY_URL'))[1] ?? '';
        $proxyIp = preg_replace('/^\/\//', '', $proxyIp);
        $this->assertStringContainsString($proxyIp, $checkResult, "Proxy check did not return expected IP. Response: $checkResult");
        echo "Proxy check successful, IP: $checkResult\n";

        $response = $client->request($messages);

        $this->assertInstanceOf(LlmResponseDto::class, $response);
        $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
        $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
        $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
        $this->assertNotEmpty($response->model, "Response model should not be empty.");
        $this->assertEquals('anthropic', $response->vendor, "Response vendor should be 'anthropic'.");
        $this->assertIsNumeric($response->inputTokens);
        $this->assertIsNumeric($response->outputTokens);
        $this->assertIsFloat($response->cost);
        $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");
        var_dump($response->rawResponse);
    }
}

