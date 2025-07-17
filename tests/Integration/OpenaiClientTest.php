<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenaiClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\CityDto;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\QuestionDto;
use Spiral\JsonSchemaGenerator\Generator;

class OpenaiClientTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('OPENAI_API_KEY') ?: $_ENV['OPENAI_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'OpenAI API key not configured in environment variables (OPENAI_API_KEY) or contains placeholder value.'
            );
        }
    }

    public function testSuccessfulBaseRequest(): void
    {
        $model = "gpt-4o-mini";
        $client = new OpenaiClient($model, $this->apiKey);

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $response = $client->request($messages);
            $response->trap();
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

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithCustomParameters(): void
    {
        $model = "gpt-4o-mini";
        $client = new OpenaiClient($model, $this->apiKey);

        $client->timeout = 10;
        $client->temperature = 0.7;
        $client->max_tokens = 50;
        $client->top_p = 0.9;
        $client->frequency_penalty = 0.1;
        $client->presence_penalty = 0.1;

        $messages = [
            SystemMessage::make('Be creative and brief.'),
            UserMessage::make('Write a short poem about rain.')
        ];

        try {
            $response = $client->request($messages);
            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('openai', $response->vendor, "Response vendor should be 'openai'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithJsonFormat(): void
    {
        $model = "gpt-4o-mini";
        $client = new OpenaiClient($model, $this->apiKey);

        $client->timeout = 10;
        $client->response_format = ["type" => "json_object"];

        $generator = new Generator();
        $schemaCity = json_encode($generator->generate(CityDto::class)->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $messages = [
            SystemMessage::make('You are a helpful assistant designed to output JSON. Always respond with valid JSON.'),
            UserMessage::make("Generate a JSON object with information about Paris, France. 
            Schema: 
            $schemaCity
            ")
        ];

        try {
            $response = $client->request($messages);
            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            
            // Verify it's valid JSON
            $decoded = json_decode($response->assistantContent, true);
            trap($decoded);
            $this->assertIsArray($decoded, "Response should be valid JSON");
            $this->assertArrayHasKey('name', $decoded);
            $this->assertArrayHasKey('country', $decoded);
            
            $this->assertEquals('openai', $response->vendor, "Response vendor should be 'openai'.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testCreateEmbedding(): void
    {
        $client = new OpenaiClient('gpt-4o-mini', $this->apiKey);
        
        $text = "This is a test sentence for embedding.";
        
        try {
            $response = $client->createEmbedding($text);
            
            $this->assertArrayHasKey('object', $response);
            $this->assertEquals('list', $response['object']);
            $this->assertArrayHasKey('data', $response);
            $this->assertIsArray($response['data']);
            $this->assertGreaterThan(0, count($response['data']));
            
            $embedding = $response['data'][0];
            $this->assertArrayHasKey('object', $embedding);
            $this->assertEquals('embedding', $embedding['object']);
            $this->assertArrayHasKey('embedding', $embedding);
            $this->assertIsArray($embedding['embedding']);
            $this->assertGreaterThan(1000, count($embedding['embedding'])); // Embedding should have many dimensions
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testModerateContent(): void
    {
        $client = new OpenaiClient('gpt-4o-mini', $this->apiKey);
        
        $text = "This is a normal, appropriate message.";
        
        try {
            $response = $client->moderateContent($text);
            
            $this->assertArrayHasKey('id', $response);
            $this->assertArrayHasKey('model', $response);
            $this->assertArrayHasKey('results', $response);
            $this->assertIsArray($response['results']);
            $this->assertGreaterThan(0, count($response['results']));
            
            $result = $response['results'][0];
            $this->assertArrayHasKey('flagged', $result);
            $this->assertArrayHasKey('categories', $result);
            $this->assertArrayHasKey('category_scores', $result);
            $this->assertIsBool($result['flagged']);
            $this->assertFalse($result['flagged']); // Should not be flagged for normal content
            
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithOrganizationAndProject(): void
    {
        $model = "gpt-4o-mini";
        $client = new OpenaiClient($model, $this->apiKey, 'test-org', 'test-project');

        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is 2+2?')
        ];

        try {
            $client->setBody($messages);
            // Just test that the body is set correctly - we can't test actual org/project without valid ones
            $this->assertArrayHasKey('model', $client->body);
            $this->assertArrayHasKey('messages', $client->body);
            $this->assertEquals($model, $client->body['model']);
            
        } catch (Exception $e) {
            // This is expected if org/project are invalid
            $this->assertStringContainsString('organization', $e->getMessage());
        }
    }
}