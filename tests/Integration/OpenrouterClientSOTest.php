<?php

namespace Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\OpenrouterClient;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\CityDto;
use Spiral\JsonSchemaGenerator\Generator;

class OpenrouterClientSOTest extends TestCase
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

    public function testSuccessfulSOBaseRequest(): void
    {
        $model = "openai/gpt-5-mini";
        $client = new OpenrouterClient($model, $this->apiKey);
        $client->timeout = 10;

        $generator = new Generator();
        $schema = $generator->generate(CityDto::class)->jsonSerialize();
        dump($schema);
        $client->setSchema("city", $schema);

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

}