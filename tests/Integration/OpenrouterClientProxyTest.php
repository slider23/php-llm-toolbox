<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class OpenrouterClientProxyTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENROUTER_API_KEY') ? $_ENV['OPENROUTER_API_KEY'] : null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenRouter API key not configured in environment variables (OPENROUTER_API_KEY) or contains placeholder value.'
            );
        }
    }
    public function testSingleRequestViaProxy()
    {
        $model = "anthropic/claude-3-5-haiku-20241022";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        $client->setProxy(getenv('PROXY_URL'), getenv('PROXY_LOGIN'), getenv('PROXY_PASSWORD'));

        $response = $client->request($messages);

        $this->assertInstanceOf(LlmResponseDto::class, $response);
        $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
        $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
        $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
        $this->assertNotEmpty($response->model, "Response model should not be empty.");
        $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
        $this->assertIsNumeric($response->inputTokens);
        $this->assertIsNumeric($response->outputTokens);
    }
}