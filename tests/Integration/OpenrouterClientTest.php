<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;

class OpenrouterClientTest extends TestCase
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

    public function testSuccessfulBaseRequest(): void
    {
        $model = "openai/gpt-5-mini";
        $client = new OpenrouterClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $response = $client->request($messages);
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

//            file_put_contents(__DIR__."/../stubs/openrouter_gpt-4o-mini_response.json", json_encode($response->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }

        sleep(3);
        $cost = $client->fetchCost($response->id);
        $this->assertIsFloat($cost, "Cost should be a float value.");
    }

    public function testSuccessfulRequestWithProviders(): void
    {
        $model = "openai/gpt-4o-mini";
        $client = new OpenrouterClient($model, $this->apiKey, ['azure']);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of Germany?')
        ];

        try {
            $response = $client->request($messages);

            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Berlin', $response->assistantContent, "Response content should mention Berlin.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openrouter', $response->vendor, "Response vendor should be 'openrouter'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");

//            file_put_contents(__DIR__."/../stubs/{$model}_providers_response.json", json_encode($response->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());

        }
    }

    public function testOCR()
    {
        // https://openrouter.ai/docs/features/multimodal/images
        $imagePath = __DIR__ . '/../stubs/OCR.png';
        $imageContentType = "image/png"; // image/png, image/jpeg, image/webp, image/gif
        if(!file_exists($imagePath)){
            $this->markTestSkipped("OCR image file not found at $imagePath");
        }
        $imageData = base64_encode(file_get_contents($imagePath));
        $model = "google/gemini-2.5-flash";
        $client = new OpenrouterClient($model, $this->apiKey);
        $client->max_tokens = 20000;
        $client->timeout = 120;
        $client->temperature = 0;
        $client->top_p = 1;
        $client->presence_penalty = 0;
        $client->frequency_penalty = 0;
        $client->setBody([
            UserMessage::make([
                [
                    'type' => 'text',
                    'text' => 'Распознай текст с изображения. Не добавляй комментариев, дай только текст без перевода.'
                ],
                [
                    'type' => 'image_url',
                    'image_url' => "data:$imageContentType;base64,$imageData",
                ]
            ])
        ]);
        $response = $client->request();
        $this->assertStringContainsString("observability", $response->assistantContent, "Response content should mention 'observability'.");

    }


}