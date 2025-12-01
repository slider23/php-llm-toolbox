<?php

namespace Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\CerebrasClient;
use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Dto\Mappers\CerebrasResponseMapper;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\CityDto;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\GrammaticalAnalysisSchema;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\QuestionDto;
use Spiral\JsonSchemaGenerator\Generator;

class CerebrasClientTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('CEREBRAS_API_KEY') ?: $_ENV['CEREBRAS_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'Cerebras API key not configured in environment variables (CEREBRAS_API_KEY) or contains placeholder value.'
            );
        }
    }

    public function testGetModelList()
    {
        $client = new CerebrasClient("", $this->apiKey);
        $result = $client->getModels();
        $models = [];
        foreach($result as $item){
            $models[] = $item['id'];
        }
        $priceByModel = CerebrasResponseMapper::$pricesByModel;
        foreach($models as $model){
            $this->assertArrayHasKey($model, $priceByModel);
        }
        foreach($priceByModel as $model => $price){
            $this->assertContains($model, $models);
        }
    }

    public function testSuccessfulBaseRequest(): void
    {
        $model = "zai-glm-4.6";
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);
        $client->timeout = 10;

        $messages = [
            SystemMessage::make('Be precise and concise.'),
            UserMessage::make('What is the capital of France?')
        ];

        try {
            $response = $client->request($messages);
//            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertStringContainsStringIgnoringCase('Paris', $response->assistantContent, "Response content should mention Paris.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('cerebras', $response->vendor, "Response vendor should be 'cerebras'.");
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
        $model = "zai-glm-4.6";
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);

        $client->timeout = 10;
        $client->temperature = 0.7;
        $client->max_tokens = 50;
        $client->top_p = 0.9;

        $messages = [
            SystemMessage::make('Be creative and brief.'),
            UserMessage::make('Write a short poem about rain.')
        ];

        try {
            $client->setBody($messages);
            dump($client->getRequest());
            $response = $client->request();
            dump($response);
//            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            $this->assertNotEmpty($response->model, "Response model should not be empty.");
            $this->assertEquals('cerebras', $response->vendor, "Response vendor should be 'cerebras'.");
            $this->assertIsNumeric($response->inputTokens);
            $this->assertIsNumeric($response->outputTokens);
            $this->assertLessThanOrEqual(50, $response->outputTokens, "Response should not exceed 50 tokens.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithJsonFormat(): void
    {
        $model = "zai-glm-4.6";
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);

        $client->timeout = 10;
        $client->response_format = ['type' => 'json_object'];

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
            $client->setBody($messages);
            dump(json_encode($client->getRequest()));
            $response = $client->request();
//            $response->trap();
            $this->assertInstanceOf(LlmResponseDto::class, $response);
            $this->assertNotEquals('error', $response->status, "Response status should not be 'error'. Error message: " . $response->errorMessage);
            $this->assertNotEmpty($response->assistantContent, "Response content should not be empty.");
            
            // Verify it's valid JSON
            $decoded = json_decode($response->assistantContent, true);
//            trap($decoded);
            $this->assertIsArray($decoded, "Response should be valid JSON");
            $this->assertArrayHasKey('name', $decoded);
            $this->assertArrayHasKey('country', $decoded);
            
            $this->assertEquals('cerebras', $response->vendor, "Response vendor should be 'cerebras'.");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithJsonSchemaSimple(): void
    {
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);
        $client->timeout = 10;

        $generator = new Generator();
        $schema = $generator->generate(CityDto::class)->jsonSerialize();
        $client->setSchema("city_schema", $schema);
        $messages = [
            SystemMessage::make('Parse data from user request.'),
            UserMessage::make("Paris is city in France with population of 2,142,500.")
            ];
        try {
            $client->setBody($messages);
            dump($client->getRequest());
            $response = $client->request();
            dump($response);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRequestWithJsonSchemaArray(): void
    {
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);
        $client->timeout = 10;

        $generator = new Generator();
        $schema = $generator->generate(GrammaticalAnalysisSchema::class)->jsonSerialize();
        $client->setSchema("grammatic", $schema);
        $messages = [
            SystemMessage::make('Ты специалист по тибетскому языку, проведи грамматический анализ данной строки.'),
            UserMessage::make("དེས་འདི་ལྟར་ཆོས་ཐམས་ཅད་རང་བཞིན་མེད་པ་དང་། མཚན་མ་མེད་པ་དང་། མཚན་མ་ཐམས་ཅད་དང་རབ་ཏུ་བྲལ་བ་དང་། དངོས་པོ་མེད་པ་དང་། དངོས་པོ་ཐམས་ཅད་དང་བྲལ་བ་དང་། གང་ཟག་པ་མེད་པ་དང་། གང་ཟག་པ་ཐམས་ཅད་དང་བྲལ་བ་དང་། ངོ་བོ་ཉིད་ཀྱིས་སྟོང་པ་ཉིད་ལ་བསླབ་པར་བྱའོ།")
            ];
        try {
            $client->setBody($messages);
            dump($client->getRequest());
            $response = $client->request($messages);
            dump($response);
        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }
    public function testRequestWithJsonSchemaDocs(): void
    {
        $model = "gpt-oss-120b";
        $client = new CerebrasClient($model, $this->apiKey);
        $client->timeout = 10;

        $schema = '{
    type: "object",
    properties: {
        title: { type: "string" },
        director: { type: "string" }, 
        year: { type: "integer" },
    },
    required: ["title", "director", "year"],
    additionalProperties: false
}';
        $client->setSchema($schema);
        $client->response_format = '{
      type: "json_schema", 
      json_schema: {
        name: "movie_schema",
        strict: true,
        schema: '.$schema.'
      }
    }';
        $messages = [
            SystemMessage::make('Выдели данные из пользовательского запроса.'),
            UserMessage::make("Suggest a sci-fi movie from the 1990s")
            ];
        try {
            $client->setBody($messages);
            dump($client->getRequest());
            $response = $client->request();
            dump($response);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }


}