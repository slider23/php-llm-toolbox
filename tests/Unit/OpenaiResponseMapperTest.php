<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenaiResponseMapper;

class OpenaiResponseMapperTest extends TestCase
{
    public function testSuccessfulResponse(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'The capital of France is Paris.'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 12,
                'completion_tokens' => 8,
                'total_tokens' => 20
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('openai', $dto->vendor);
        $this->assertEquals('chatcmpl-123', $dto->id);
        $this->assertEquals('gpt-4o-mini', $dto->model);
        $this->assertEquals('The capital of France is Paris.', $dto->assistantContent);
        $this->assertEquals('stop', $dto->finishReason);
        $this->assertEquals(12, $dto->inputTokens);
        $this->assertEquals(8, $dto->outputTokens);
        $this->assertEquals(20, $dto->totalTokens);
        $this->assertFalse($dto->toolsUsed);
        $this->assertEmpty($dto->toolCalls);
        $this->assertIsFloat($dto->cost);
        $this->assertGreaterThan(0, $dto->cost);
    }

    public function testResponseWithError(): void
    {
        $responseData = [
            'error' => [
                'message' => 'Invalid API key provided.',
                'type' => 'invalid_request_error',
                'param' => null,
                'code' => 'invalid_api_key'
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('error', $dto->status);
        $this->assertEquals('openai', $dto->vendor);
        $this->assertEquals('Invalid API key provided.', $dto->errorMessage);
        $this->assertNull($dto->httpStatusCode);
    }

    public function testResponseWithToolCalls(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'I will calculate that for you.',
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'calculator',
                                    'arguments' => '{"operation": "add", "a": 5, "b": 3}'
                                ]
                            ]
                        ]
                    ],
                    'finish_reason' => 'tool_calls'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 12,
                'total_tokens' => 32
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('openai', $dto->vendor);
        $this->assertEquals('chatcmpl-123', $dto->id);
        $this->assertEquals('gpt-4o-mini', $dto->model);
        $this->assertEquals('I will calculate that for you.', $dto->assistantContent);
        $this->assertEquals('tool_calls', $dto->finishReason);
        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(1, $dto->toolCalls);
        $this->assertEquals('call_123', $dto->toolCalls[0]['id']);
        $this->assertEquals('function', $dto->toolCalls[0]['type']);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        $this->assertEquals('{"operation": "add", "a": 5, "b": 3}', $dto->toolCalls[0]['function']['arguments']);
    }

    public function testResponseWithMultipleToolCalls(): void
    {
        $responseData = [
            'id' => 'chatcmpl-456',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'I will help you with both requests.',
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'calculator',
                                    'arguments' => '{"operation": "multiply", "a": 7, "b": 6}'
                                ]
                            ],
                            [
                                'id' => 'call_456',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_weather',
                                    'arguments' => '{"location": "London", "units": "celsius"}'
                                ]
                            ]
                        ]
                    ],
                    'finish_reason' => 'tool_calls'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 30,
                'completion_tokens' => 20,
                'total_tokens' => 50
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(2, $dto->toolCalls);
        
        // Check first tool call
        $this->assertEquals('call_123', $dto->toolCalls[0]['id']);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        $this->assertEquals('{"operation": "multiply", "a": 7, "b": 6}', $dto->toolCalls[0]['function']['arguments']);
        
        // Check second tool call
        $this->assertEquals('call_456', $dto->toolCalls[1]['id']);
        $this->assertEquals('get_weather', $dto->toolCalls[1]['function']['name']);
        $this->assertEquals('{"location": "London", "units": "celsius"}', $dto->toolCalls[1]['function']['arguments']);
    }

    public function testPricingCalculation(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 1000,
                'completion_tokens' => 500,
                'total_tokens' => 1500
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertIsFloat($dto->cost);
        $this->assertGreaterThan(0, $dto->cost);
        
        // Calculate expected cost for gpt-4o-mini
        $inputCost = 1000 * (0.15 / 1_000_000);
        $outputCost = 500 * (0.60 / 1_000_000);
        $expectedCost = $inputCost + $outputCost;
        
        $this->assertEquals($expectedCost, $dto->cost, '', 0.000001);
    }

    public function testPricingForGpt4o(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 1000,
                'completion_tokens' => 500,
                'total_tokens' => 1500
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertIsFloat($dto->cost);
        $this->assertGreaterThan(0, $dto->cost);
        
        // Calculate expected cost for gpt-4o
        $inputCost = 1000 * (2.50 / 1_000_000);
        $outputCost = 500 * (10.00 / 1_000_000);
        $expectedCost = $inputCost + $outputCost;
        
        $this->assertEquals($expectedCost, $dto->cost, '', 0.000001);
    }

    public function testPricingForUnknownModel(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'unknown-model',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 1000,
                'completion_tokens' => 500,
                'total_tokens' => 1500
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertNull($dto->cost);
    }

    public function testResponseWithoutUsage(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Test response'
                    ],
                    'finish_reason' => 'stop'
                ]
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertNull($dto->inputTokens);
        $this->assertNull($dto->outputTokens);
        $this->assertNull($dto->totalTokens);
        $this->assertNull($dto->cost);
    }

    public function testResponseWithThinkingContent(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4o-mini',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => '<thinking>Let me think about this question</thinking>The answer is 42.'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15
            ]
        ];

        $dto = OpenaiResponseMapper::makeDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('The answer is 42.', $dto->assistantContent);
        $this->assertEquals('Let me think about this question', $dto->assistantThinkingContent);
    }
}