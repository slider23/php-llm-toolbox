<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\Mappers\VoyageResponseMapper;

class VoyageResponseMapperTest extends TestCase
{
    public function testSuccessfulEmbeddingResponse(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.2, 0.3, -0.1, 0.5],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3-large',
            'usage' => [
                'total_tokens' => 100
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage', $dto->vendor);
        $this->assertEquals('voyage-3-large', $dto->model);
        $this->assertEquals([0.1, 0.2, 0.3, -0.1, 0.5], $dto->embedding);
        $this->assertEquals(100, $dto->tokens);
        $this->assertNull($dto->errorMessage);
        
        // Test cost calculation for voyage-3-large
        $expectedCost = 100 * (0.18 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyage35(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.4, -0.2, 0.8, 0.1, -0.5, 0.3],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3.5',
            'usage' => [
                'total_tokens' => 250
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage', $dto->vendor);
        $this->assertEquals('voyage-3.5', $dto->model);
        $this->assertEquals([0.4, -0.2, 0.8, 0.1, -0.5, 0.3], $dto->embedding);
        $this->assertEquals(250, $dto->tokens);
        
        // Test cost calculation for voyage-3.5
        $expectedCost = 250 * (0.06 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyage35Lite(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.7, 0.2, -0.4],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3.5-lite',
            'usage' => [
                'total_tokens' => 50
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage', $dto->vendor);
        $this->assertEquals('voyage-3.5-lite', $dto->model);
        $this->assertEquals([0.7, 0.2, -0.4], $dto->embedding);
        $this->assertEquals(50, $dto->tokens);
        
        // Test cost calculation for voyage-3.5-lite
        $expectedCost = 50 * (0.02 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyageCode3(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.9, -0.3, 0.5],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-code-3',
            'usage' => [
                'total_tokens' => 150
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage-code-3', $dto->model);
        $this->assertEquals(150, $dto->tokens);
        
        // Test cost calculation for voyage-code-3
        $expectedCost = 150 * (0.18 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyageFinance2(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.2, -0.1, 0.7],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-finance-2',
            'usage' => [
                'total_tokens' => 80
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('voyage-finance-2', $dto->model);
        $this->assertEquals(80, $dto->tokens);
        
        // Test cost calculation for voyage-finance-2
        $expectedCost = 80 * (0.12 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyageLaw2(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [-0.3, 0.6, 0.1],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-law-2',
            'usage' => [
                'total_tokens' => 120
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('voyage-law-2', $dto->model);
        $this->assertEquals(120, $dto->tokens);
        
        // Test cost calculation for voyage-law-2
        $expectedCost = 120 * (0.12 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyageCode2(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.8, -0.2, 0.4, -0.1],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-code-2',
            'usage' => [
                'total_tokens' => 200
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('voyage-code-2', $dto->model);
        $this->assertEquals(200, $dto->tokens);
        
        // Test cost calculation for voyage-code-2
        $expectedCost = 200 * (0.12 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseRerank2(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.5, 0.3, -0.7, 0.2],
                    'index' => 0
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 75
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(75, $dto->tokens);
        
        // Test cost calculation for rerank-2
        $expectedCost = 75 * (0.05 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseRerank2Lite(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, -0.4, 0.9],
                    'index' => 0
                ]
            ],
            'model' => 'rerank-2-lite',
            'usage' => [
                'total_tokens' => 30
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('rerank-2-lite', $dto->model);
        $this->assertEquals(30, $dto->tokens);
        
        // Test cost calculation for rerank-2-lite
        $expectedCost = 30 * (0.02 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulEmbeddingResponseVoyageMultimodal3(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.6, 0.1, -0.3, 0.8, -0.2],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-multimodal-3',
            'usage' => [
                'total_tokens' => 180
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('voyage-multimodal-3', $dto->model);
        $this->assertEquals(180, $dto->tokens);
        
        // Test cost calculation for voyage-multimodal-3
        $expectedCost = 180 * (0.12 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testResponseWithError(): void
    {
        $responseData = [
            'error' => [
                'message' => 'Invalid API key provided.',
                'type' => 'invalid_request_error',
                'code' => 'invalid_api_key'
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('error', $dto->status);
        $this->assertEquals('Invalid API key provided.', $dto->errorMessage);
        $this->assertNull($dto->model);
        $this->assertEmpty($dto->embedding);
        $this->assertEquals(0, $dto->tokens);
        $this->assertNull($dto->cost);
    }

    public function testResponseWithErrorNoMessage(): void
    {
        $responseData = [
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'invalid_api_key'
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('error', $dto->status);
        $this->assertNull($dto->errorMessage);
    }

    public function testResponseWithMissingEmbedding(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3-large',
            'usage' => [
                'total_tokens' => 100
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage-3-large', $dto->model);
        $this->assertEmpty($dto->embedding);
        $this->assertEquals(100, $dto->tokens);
    }

    public function testResponseWithMissingUsage(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.2, 0.3],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3-large'
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage-3-large', $dto->model);
        $this->assertEquals([0.1, 0.2, 0.3], $dto->embedding);
        $this->assertEquals(0, $dto->tokens);
        $this->assertEquals(0, $dto->cost);
    }

    public function testResponseWithMissingModel(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.2, 0.3],
                    'index' => 0
                ]
            ],
            'usage' => [
                'total_tokens' => 100
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertNull($dto->model);
        $this->assertEquals([0.1, 0.2, 0.3], $dto->embedding);
        $this->assertEquals(100, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost calculation without model
    }

    public function testResponseWithUnknownModel(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.2, 0.3],
                    'index' => 0
                ]
            ],
            'model' => 'unknown-voyage-model',
            'usage' => [
                'total_tokens' => 100
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('unknown-voyage-model', $dto->model);
        $this->assertEquals([0.1, 0.2, 0.3], $dto->embedding);
        $this->assertEquals(100, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost calculation for unknown model
    }

    public function testResponseWithZeroTokens(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [0.1, 0.2, 0.3],
                    'index' => 0
                ]
            ],
            'model' => 'voyage-3-large',
            'usage' => [
                'total_tokens' => 0
            ]
        ];

        $dto = VoyageResponseMapper::makeEmbeddingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage-3-large', $dto->model);
        $this->assertEquals([0.1, 0.2, 0.3], $dto->embedding);
        $this->assertEquals(0, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost for zero tokens
    }

    public function testPricesByModelStructure(): void
    {
        $prices = VoyageResponseMapper::$pricesByModel;
        
        // Test that all expected models are present
        $expectedModels = [
            'voyage-3-large',
            'voyage-3.5',
            'voyage-3.5-lite',
            'voyage-code-3',
            'voyage-finance-2',
            'voyage-law-2',
            'voyage-code-2',
            'rerank-2',
            'rerank-2-lite',
            'voyage-multimodal-3'
        ];
        
        foreach ($expectedModels as $model) {
            $this->assertArrayHasKey($model, $prices, "Model $model should be present in prices");
            $this->assertArrayHasKey('inputTokens', $prices[$model], "Model $model should have inputTokens price");
            $this->assertIsFloat($prices[$model]['inputTokens'], "Model $model inputTokens price should be float");
            $this->assertGreaterThan(0, $prices[$model]['inputTokens'], "Model $model inputTokens price should be positive");
        }
    }
}