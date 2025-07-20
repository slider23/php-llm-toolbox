<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageEmbeddingClient;
use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class VoyageEmbeddingClientTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('VOYAGE_API_KEY') ?: $_ENV['VOYAGE_API_KEY'] ?? null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'Voyage API key not configured in environment variables (VOYAGE_API_KEY) or contains placeholder value.'
            );
        }
    }

    public function testSuccessfulEmbeddingWithVoyage3Large(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', $this->apiKey);
        $client->timeout = 10;

        $text = "This is a test sentence for embedding generation.";

        try {
            $response = $client->createEmbedding($text);

            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertEquals('voyage', $response->vendor);
            $this->assertEquals('voyage-3-large', $response->model);
            $this->assertNotEmpty($response->embedding, "Embedding should not be empty.");
            $this->assertIsArray($response->embedding, "Embedding should be an array.");
            $this->assertGreaterThan(0, count($response->embedding), "Embedding should have dimensions.");
            $this->assertGreaterThan(0, $response->tokens, "Token count should be greater than 0.");
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");
            $this->assertNull($response->errorMessage);

            // Verify embedding values are floats
            foreach ($response->embedding as $value) {
                $this->assertIsFloat($value);
            }

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testSuccessfulEmbeddingWithVoyage35(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;

        $text = "Machine learning is transforming how we process data.";

        try {
            $response = $client->createEmbedding($text);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertEquals('voyage', $response->vendor);
            $this->assertEquals('voyage-3.5', $response->model);
            $this->assertNotEmpty($response->embedding);
            $this->assertIsArray($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testSuccessfulEmbeddingWithVoyage35Lite(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5-lite', $this->apiKey);
        $client->timeout = 10;

        $text = "Lightweight embedding model for fast processing.";

        try {
            $response = $client->createEmbedding($text);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertEquals('voyage-3.5-lite', $response->model);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmbeddingWithQueryInputType(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;

        $text = "What is the capital of France?";

        try {
            $response = $client->createEmbedding($text, 'query');
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmbeddingWithDocumentInputType(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;

        $text = "Paris is the capital and most populous city of France. It is located in northern central France.";

        try {
            $response = $client->createEmbedding($text, 'document');
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmbeddingWithClientInputType(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;
        $client->input_type = 'document'; // Set on client

        $text = "This text will use the client's default input type.";

        try {
            $response = $client->createEmbedding($text); // No input type parameter
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmbeddingWithCustomOutputDimensions(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;
        $client->output_dimension = 512; // Reduce dimensions

        $text = "Custom dimension embedding test.";

        try {
            $response = $client->createEmbedding($text);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertLessThanOrEqual(512, count($response->embedding), "Embedding should have at most 512 dimensions.");
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmbeddingWithTruncationDisabled(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5-lite', $this->apiKey);
        $client->timeout = 10;
        $client->truncation = false;

        $text = "Short text for truncation test.";

        try {
            $response = $client->createEmbedding($text);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testCodeEmbeddingWithVoyageCode3(): void
    {
        $client = new VoyageEmbeddingClient('voyage-code-3', $this->apiKey);
        $client->timeout = 10;

        $code = "function calculateSum(a, b) { return a + b; }";

        try {
            $response = $client->createEmbedding($code);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertEquals('voyage-code-3', $response->model);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testEmptyInput(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;

        $text = "";

        try {
            $response = $client->createEmbedding($text);
            
            // Voyage should handle empty input gracefully or return an error
            if ($response->status === 'error') {
                $this->assertNotEmpty($response->errorMessage);
            } else {
                $this->assertEquals('success', $response->status);
            }

        } catch (LlmRequestException $e) {
            // Expected if API rejects empty input
            $this->assertStringContainsString('input', strtolower($e->getMessage()));
        }
    }

    public function testVeryLongInput(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5-lite', $this->apiKey);
        $client->timeout = 15;
        $client->truncation = true; // Enable truncation for long input

        // Create a very long text
        $text = str_repeat("This is a very long sentence that will be repeated many times to test the embedding API with large inputs. ", 1000);

        try {
            $response = $client->createEmbedding($text);
            
            // Should succeed with truncation enabled
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertNotEmpty($response->embedding);
            $this->assertGreaterThan(0, $response->tokens);

        } catch (LlmRequestException $e) {
            // Expected if input is too long even with truncation
            $this->assertStringContainsString('token', strtolower($e->getMessage()));
        }
    }


    public function testCostCalculationAccuracy(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', $this->apiKey);
        $client->timeout = 10;

        $text = "Cost calculation test for voyage embeddings.";

        try {
            $response = $client->createEmbedding($text);
            
            $this->assertInstanceOf(EmbeddingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost);

            // Verify cost calculation matches expected rate for voyage-3.5 (0.06 per 1M tokens)
            $expectedCost = $response->tokens * (0.06 / 1_000_000);
            $this->assertEquals($expectedCost, $response->cost, '', 0.0000001);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }
}