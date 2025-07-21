<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\Mappers\VoyageRerankingMapper;

class VoyageRerankingMapperTest extends TestCase
{
    public function testSuccessfulRerankingResponseRerank2(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.95,
                    'index' => 2,
                    'document' => 'Paris is the capital of France and one of the most visited cities in the world.'
                ],
                [
                    'relevance_score' => 0.78,
                    'index' => 0,
                    'document' => 'London is the capital city of England and the United Kingdom.'
                ],
                [
                    'relevance_score' => 0.45,
                    'index' => 1,
                    'document' => 'Berlin is the capital and largest city of Germany.'
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 150
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage', $dto->vendor);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(150, $dto->tokens);
        $this->assertNull($dto->errorMessage);
        $this->assertIsArray($dto->data);
        $this->assertCount(3, $dto->data);

        // Test first result (highest score)
        $this->assertEquals(0.95, $dto->data[0]['relevance_score']);
        $this->assertEquals(2, $dto->data[0]['index']);
        $this->assertEquals('Paris is the capital of France and one of the most visited cities in the world.', $dto->data[0]['document']);

        // Test cost calculation for rerank-2
        $expectedCost = 150 * (0.05 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulRerankingResponseRerank2Lite(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.87,
                    'index' => 1,
                    'document' => 'Machine learning is a subset of artificial intelligence.'
                ],
                [
                    'relevance_score' => 0.56,
                    'index' => 0,
                    'document' => 'Deep learning is a subset of machine learning.'
                ]
            ],
            'model' => 'rerank-2-lite',
            'usage' => [
                'total_tokens' => 80
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('voyage', $dto->vendor);
        $this->assertEquals('rerank-2-lite', $dto->model);
        $this->assertEquals(80, $dto->tokens);
        $this->assertIsArray($dto->data);
        $this->assertCount(2, $dto->data);

        // Test cost calculation for rerank-2-lite
        $expectedCost = 80 * (0.02 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testSuccessfulRerankingResponseWithoutDocuments(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.92,
                    'index' => 1
                ],
                [
                    'relevance_score' => 0.65,
                    'index' => 0
                ],
                [
                    'relevance_score' => 0.23,
                    'index' => 2
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 120
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(120, $dto->tokens);
        $this->assertIsArray($dto->data);
        $this->assertCount(3, $dto->data);

        // Test results without documents
        $this->assertEquals(0.92, $dto->data[0]['relevance_score']);
        $this->assertEquals(1, $dto->data[0]['index']);
        $this->assertArrayNotHasKey('document', $dto->data[0]);

        $this->assertEquals(0.65, $dto->data[1]['relevance_score']);
        $this->assertEquals(0, $dto->data[1]['index']);

        $this->assertEquals(0.23, $dto->data[2]['relevance_score']);
        $this->assertEquals(2, $dto->data[2]['index']);
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

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('error', $dto->status);
        $this->assertEquals('Invalid API key provided.', $dto->errorMessage);
        $this->assertNull($dto->model);
        $this->assertNull($dto->data);
        $this->assertNull($dto->tokens);
        $this->assertNull($dto->cost);
    }

    public function testResponseWithErrorNoMessage(): void
    {
        $responseData = [
            'error' => [
                'type' => 'invalid_request_error',
                'code' => 'insufficient_quota'
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('error', $dto->status);
        $this->assertNull($dto->errorMessage);
    }

    public function testResponseWithMissingData(): void
    {
        $responseData = [
            'object' => 'list',
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 100
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(100, $dto->tokens);
        $this->assertEmpty($dto->data);
    }

    public function testResponseWithMissingUsage(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.85,
                    'index' => 0
                ]
            ],
            'model' => 'rerank-2'
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(0, $dto->tokens);
        $this->assertEquals(0, $dto->cost);
        $this->assertIsArray($dto->data);
        $this->assertCount(1, $dto->data);
    }

    public function testResponseWithMissingModel(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.75,
                    'index' => 0
                ]
            ],
            'usage' => [
                'total_tokens' => 50
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertNull($dto->model);
        $this->assertEquals(50, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost calculation without model
        $this->assertIsArray($dto->data);
    }

    public function testResponseWithUnknownModel(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.89,
                    'index' => 0
                ]
            ],
            'model' => 'unknown-rerank-model',
            'usage' => [
                'total_tokens' => 75
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('unknown-rerank-model', $dto->model);
        $this->assertEquals(75, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost calculation for unknown model
    }

    public function testResponseWithZeroTokens(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.67,
                    'index' => 0
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 0
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(0, $dto->tokens);
        $this->assertEquals(0, $dto->cost); // No cost for zero tokens
    }

    public function testResponseWithEmptyDataArray(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 25
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(25, $dto->tokens);
        $this->assertIsArray($dto->data);
        $this->assertEmpty($dto->data);
        
        // Cost should still be calculated
        $expectedCost = 25 * (0.05 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testComplexRerankingResponse(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.98,
                    'index' => 3,
                    'document' => 'Artificial Intelligence is revolutionizing machine learning and deep learning technologies.'
                ],
                [
                    'relevance_score' => 0.91,
                    'index' => 0,
                    'document' => 'Machine learning algorithms are used in artificial intelligence applications.'
                ],
                [
                    'relevance_score' => 0.84,
                    'index' => 1,
                    'document' => 'Deep learning networks are a subset of machine learning techniques.'
                ],
                [
                    'relevance_score' => 0.76,
                    'index' => 4,
                    'document' => 'Natural language processing uses AI and ML for text analysis.'
                ],
                [
                    'relevance_score' => 0.42,
                    'index' => 2,
                    'document' => 'Traditional programming differs from machine learning approaches.'
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 300
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('success', $dto->status);
        $this->assertEquals('rerank-2', $dto->model);
        $this->assertEquals(300, $dto->tokens);
        $this->assertIsArray($dto->data);
        $this->assertCount(5, $dto->data);

        // Verify results are ordered by relevance score (descending)
        $this->assertGreaterThanOrEqual($dto->data[1]['relevance_score'], $dto->data[0]['relevance_score']);
        $this->assertGreaterThanOrEqual($dto->data[2]['relevance_score'], $dto->data[1]['relevance_score']);
        $this->assertGreaterThanOrEqual($dto->data[3]['relevance_score'], $dto->data[2]['relevance_score']);
        $this->assertGreaterThanOrEqual($dto->data[4]['relevance_score'], $dto->data[3]['relevance_score']);

        // Test cost calculation
        $expectedCost = 300 * (0.05 / 1_000_000);
        $this->assertEquals($expectedCost, $dto->cost);
    }

    public function testPricesByModelStructure(): void
    {
        $prices = VoyageRerankingMapper::$pricesByModel;
        
        // Test that all expected models are present
        $expectedModels = ['rerank-2', 'rerank-2-lite'];
        
        foreach ($expectedModels as $model) {
            $this->assertArrayHasKey($model, $prices, "Model $model should be present in prices");
            $this->assertArrayHasKey('inputTokens', $prices[$model], "Model $model should have inputTokens price");
            $this->assertIsFloat($prices[$model]['inputTokens'], "Model $model inputTokens price should be float");
            $this->assertGreaterThan(0, $prices[$model]['inputTokens'], "Model $model inputTokens price should be positive");
        }

        // Test specific pricing values
        $this->assertEquals(0.05 / 1_000_000, $prices['rerank-2']['inputTokens']);
        $this->assertEquals(0.02 / 1_000_000, $prices['rerank-2-lite']['inputTokens']);
    }

    public function testDefaultVendorValue(): void
    {
        $responseData = [
            'object' => 'list',
            'data' => [
                [
                    'relevance_score' => 0.8,
                    'index' => 0
                ]
            ],
            'model' => 'rerank-2',
            'usage' => [
                'total_tokens' => 50
            ]
        ];

        $dto = VoyageRerankingMapper::makeRerankingDto($responseData);

        $this->assertEquals('voyage', $dto->vendor);
    }
}