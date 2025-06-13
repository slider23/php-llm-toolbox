<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\PerplexityClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class PerplexityClientTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = $_ENV['PERPLEXITY_API_KEY'] ?? null;

        if (!$this->apiKey) {
            $this->markTestSkipped(
                'Perplexity API key not configured in environment variables (PERPLEXITY_API_KEY).'
            );
        }
    }

    public function testSuccessfulBaseRequest(): void
    {
        $client = new PerplexityClient("sonar", $this->apiKey);

        $client->timeout = 10; // Можно установить меньший таймаут для тестов

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $response = $client->request($messages);

            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistant_content, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistant_content, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithInvalidApiKey(): void
    {
        $this->expectException(LlmVendorException::class);

        // Используем заведомо неверный API ключ
        $client = new PerplexityClient("sonar", 'invalid-api-key');
        $messages = [
            UserMessage::make("Hello")
        ];
        $client->request($messages);
    }
}
