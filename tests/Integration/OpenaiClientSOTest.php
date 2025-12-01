<?php

namespace Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\CerebrasClient;
use Slider23\PhpLlmToolbox\Clients\OpenaiClient;
use Slider23\PhpLlmToolbox\Clients\OpenaiEmbeddingClient;
use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;
use Slider23\PhpLlmToolbox\Messages\SystemMessage;
use Slider23\PhpLlmToolbox\Messages\UserMessage;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\CityDto;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\GrammaticalAnalysisSchema;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\QuestionDto;
use Spiral\JsonSchemaGenerator\Generator;

class OpenaiClientSOTest extends TestCase
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

    public function testSOArray(): void
    {
        $model = "gpt-5-mini";
        $client = new OpenaiClient($model, $this->apiKey);
        $client->timeout = 120;

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
}