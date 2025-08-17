<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenaiClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class OpenaiClientProxyTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENAI_API_KEY') ? $_ENV['OPENAI_API_KEY'] : null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenAI API key not configured in environment variables (OPENAI_API_KEY) or contains placeholder value.'
            );
        }
    }
    public function testSingleRequestViaProxy()
    {
        $model = "gpt-4o-mini-2024-07-18";
        $client = new OpenaiClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        $client->setProxy(getenv('PROXY_URL'), getenv('PROXY_LOGIN'), getenv('PROXY_PASSWORD'));

        $response = $client->request($messages);
//        $response->trap();

        $this->assertInstanceOf(LlmResponseDto::class, $response);
        $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
        $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
        $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
        $this->assertNotEmpty($response->model, "Response model should not be empty.");
        $this->assertEquals('openai', $response->vendor, "Response vendor should be 'openai'.");
        $this->assertIsNumeric($response->inputTokens);
        $this->assertIsNumeric($response->outputTokens);
        $this->assertIsFloat($response->cost);
        $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");
    }
}