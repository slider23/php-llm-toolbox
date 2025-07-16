<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\Mappers\OpenrouterResponseMapper;
use Slider23\PhpLlmToolbox\Dto\Mappers\AnthropicResponseMapper;

class ResponseMappersToolsTest extends TestCase
{
    public function testOpenrouterResponseMapperWithTools(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'model' => 'gpt-4',
            'choices' => [
                [
                    'message' => [
                        'content' => 'I will calculate that for you.',
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
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
                'completion_tokens' => 10,
                'total_tokens' => 30
            ]
        ];

        $dto = OpenrouterResponseMapper::makeDto($responseData);

        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(1, $dto->toolCalls);
        $this->assertEquals('call_123', $dto->toolCalls[0]['id']);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        $this->assertEquals('{"operation": "add", "a": 5, "b": 3}', $dto->toolCalls[0]['function']['arguments']);
    }

    public function testOpenrouterResponseMapperWithoutTools(): void
    {
        $responseData = [
            'id' => 'chatcmpl-123',
            'model' => 'gpt-4',
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hello! How can I help you today?'
                    ],
                    'finish_reason' => 'stop'
                ]
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 8,
                'total_tokens' => 18
            ]
        ];

        $dto = OpenrouterResponseMapper::makeDto($responseData);

        $this->assertFalse($dto->toolsUsed);
        $this->assertEmpty($dto->toolCalls);
    }

    public function testAnthropicResponseMapperWithTools(): void
    {
        $responseData = [
            'id' => 'msg_123',
            'model' => 'claude-3-sonnet-20240229',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I will calculate that for you.'
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'calculator',
                    'input' => [
                        'operation' => 'add',
                        'a' => 5,
                        'b' => 3
                    ]
                ]
            ],
            'stop_reason' => 'tool_use',
            'usage' => [
                'input_tokens' => 20,
                'output_tokens' => 10
            ]
        ];

        $dto = AnthropicResponseMapper::makeDto($responseData);

        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(1, $dto->toolCalls);
        $this->assertEquals('toolu_123', $dto->toolCalls[0]['id']);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        
        $arguments = json_decode($dto->toolCalls[0]['function']['arguments'], true);
        $this->assertEquals('add', $arguments['operation']);
        $this->assertEquals(5, $arguments['a']);
        $this->assertEquals(3, $arguments['b']);
        
        $this->assertEquals('I will calculate that for you.', $dto->assistantContent);
    }

    public function testAnthropicResponseMapperWithoutTools(): void
    {
        $responseData = [
            'id' => 'msg_123',
            'model' => 'claude-3-sonnet-20240229',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Hello! How can I help you today?'
                ]
            ],
            'stop_reason' => 'end_turn',
            'usage' => [
                'input_tokens' => 10,
                'output_tokens' => 8
            ]
        ];

        $dto = AnthropicResponseMapper::makeDto($responseData);

        $this->assertFalse($dto->toolsUsed);
        $this->assertEmpty($dto->toolCalls);
        $this->assertEquals('Hello! How can I help you today?', $dto->assistantContent);
    }

    public function testAnthropicResponseMapperWithMultipleTools(): void
    {
        $responseData = [
            'id' => 'msg_123',
            'model' => 'claude-3-sonnet-20240229',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'I will help you with both requests.'
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'calculator',
                    'input' => [
                        'operation' => 'add',
                        'a' => 5,
                        'b' => 3
                    ]
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_456',
                    'name' => 'get_weather',
                    'input' => [
                        'location' => 'London'
                    ]
                ]
            ],
            'stop_reason' => 'tool_use',
            'usage' => [
                'input_tokens' => 25,
                'output_tokens' => 15
            ]
        ];

        $dto = AnthropicResponseMapper::makeDto($responseData);

        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(2, $dto->toolCalls);
        
        // Check first tool call
        $this->assertEquals('toolu_123', $dto->toolCalls[0]['id']);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        $calculatorArgs = json_decode($dto->toolCalls[0]['function']['arguments'], true);
        $this->assertEquals('add', $calculatorArgs['operation']);
        
        // Check second tool call
        $this->assertEquals('toolu_456', $dto->toolCalls[1]['id']);
        $this->assertEquals('get_weather', $dto->toolCalls[1]['function']['name']);
        $weatherArgs = json_decode($dto->toolCalls[1]['function']['arguments'], true);
        $this->assertEquals('London', $weatherArgs['location']);
        
        $this->assertEquals('I will help you with both requests.', $dto->assistantContent);
    }

    public function testAnthropicResponseMapperWithMixedContent(): void
    {
        $responseData = [
            'id' => 'msg_123',
            'model' => 'claude-3-sonnet-20240229',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'First, '
                ],
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'calculator',
                    'input' => [
                        'operation' => 'add',
                        'a' => 10,
                        'b' => 5
                    ]
                ],
                [
                    'type' => 'text',
                    'text' => ' and then some more text.'
                ]
            ],
            'stop_reason' => 'tool_use',
            'usage' => [
                'input_tokens' => 20,
                'output_tokens' => 12
            ]
        ];

        $dto = AnthropicResponseMapper::makeDto($responseData);

        $this->assertTrue($dto->toolsUsed);
        $this->assertIsArray($dto->toolCalls);
        $this->assertCount(1, $dto->toolCalls);
        $this->assertEquals('calculator', $dto->toolCalls[0]['function']['name']);
        
        // Check that text content is properly concatenated
        $this->assertEquals('First,  and then some more text.', $dto->assistantContent);
    }
}