<?php

namespace Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class OpenrouterClientReasoningTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENROUTER_API_KEY') ?: $_ENV['OPENROUTER_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenRouter API key not configured in environment variables (OPENROUTER_API_KEY) or contains placeholder value.'
            );
        }
    }

    public function testGeminiReasoning(): void
    {
        $model = "google/gemini-3-flash-preview";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->enableReasoning();
        $client->include_reasoning = true;
        $client->timeout = 100;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];
        $client->setBody($messages);

        try {
            dump($client->getRequest());
            $response = $client->request();
            dump($response);
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

            $this->assertNotNull($response->reasoningContent, "Reasoning content should not be null.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }

        sleep(3);
        $cost = $client->fetchCost($response->id);
        $this->assertIsFloat($cost, "Cost should be a float value.");
    }

    public function testOpenaiReasoning(): void
    {
        $model = "openai/gpt-oss-120b";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->enableReasoning();
        $client->include_reasoning = true;
        $client->timeout = 100;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];
        $client->setBody($messages);

        try {
            dump($client->getRequest());
            $response = $client->request();
            dump($response);
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

            $this->assertNotNull($response->reasoningContent, "Reasoning content should not be null.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }

        sleep(3);
        $cost = $client->fetchCost($response->id);
        $this->assertIsFloat($cost, "Cost should be a float value.");
    }

    public function testGeminiNoReasoning(): void
    {
        $model = "google/gemini-3-flash-preview";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->disableReasoning();
        $client->include_reasoning = true;
        $client->timeout = 100;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];
        $client->setBody($messages);

        try {
            dump($client->getRequest());
            $response = $client->request();
            dump($response);
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

            $this->assertNull($response->reasoningContent, "Reasoning content should be null.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }

        sleep(3);
        $cost = $client->fetchCost($response->id);
        $this->assertIsFloat($cost, "Cost should be a float value.");
    }

    public function testAnthropicReasoning()
    {
        $model = "anthropic/claude-haiku-4.5";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->enableReasoning("low", 500);

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        $client->setBody($messages);

        try {
            dump($client->getRequest());
            $response = $client->request();
            dump($response);
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

            $this->assertNotNull($response->reasoningContent, "Reasoning content should not be null.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }

        sleep(3);
        $cost = $client->fetchCost($response->id);
        $this->assertIsFloat($cost, "Cost should be a float value.");
    }

}