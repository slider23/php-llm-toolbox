<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\DeepseekClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class DeepseekClientTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        // Проверяем наличие API ключа в переменных окружения
        $this->apiKey = getenv('DEEPSEEK_API_KEY') ? $_ENV['DEEPSEEK_API_KEY'] : null;

        if (empty($this->apiKey) || $this->apiKey === 'your-deepseek-api-key-here') {
            $this->markTestSkipped(
                'Deepseek API key not configured in environment variables (DEEPSEEK_API_KEY) or contains placeholder value.'
            );
        }
    }

    public function testSuccessfulBaseRequest(): void
    {
        $model = "deepseek-chat"; // Используем модель deepseek-chat
        $client = new DeepseekClient($model, $this->apiKey);

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
            $this->assertEquals('deepseek', $response->vendor, "Response vendor should be 'deepseek'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

//            file_put_contents(__DIR__."/../stubs/{$model}_response.json", json_encode($response->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

}
