<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\AnthropicClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\TextContent;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class AnthropicClientTest extends TestCase
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

    public function testSuccessfulBaseRequest(): void
    {
        $model = "claude-3-5-haiku-20241022";
        $client = new AnthropicClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $client->setBody($messages);
            $response = $client->request();

            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistant_content, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistant_content, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('anthropic', $response->vendor, "Response vendor should be 'anthropic'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

//            file_put_contents(__DIR__."/../stubs/{$model}_response.json", json_encode($response->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testSuccessfulBaseRequestWithCache(): void
    {
        $model = "claude-3-5-haiku-20241022";
        $client = new AnthropicClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make(TextContent::make('Be precise and concise.', ["cache_control" => ["type" => "ephemeral"]])),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $response = $client->request($messages);

            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistant_content, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistant_content, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('anthropic', $response->vendor, "Response vendor should be 'anthropic'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

//            file_put_contents(__DIR__."/../stubs/{$model}_cached_response.json", json_encode($response->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }


    public function testTokenCalculation(): void
    {
        $model = "claude-3-5-haiku-20241022";
        $client = new AnthropicClient($model, $this->apiKey);

        $text = "What is the capital of France?";
        $tokenCount = $client->calculateTokens($text);

        $this->assertIsInt($tokenCount);
        $this->assertGreaterThan(0, $tokenCount);
    }
}