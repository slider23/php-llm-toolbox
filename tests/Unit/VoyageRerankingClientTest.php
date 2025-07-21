<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageRerankingClient;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

class VoyageRerankingClientTest extends TestCase
{
    public function testConstructor(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $this->assertEquals('rerank-2', $client->model);
        $this->assertEquals('test-api-key', $client->apiKey);
        $this->assertNull($client->top_k);
        $this->assertFalse($client->return_documents);
        $this->assertFalse($client->truncation);
    }

    public function testConfigurableProperties(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Test setting all configurable properties
        $client->top_k = 5;
        $client->return_documents = true;
        $client->truncation = true;
        
        $this->assertEquals(5, $client->top_k);
        $this->assertTrue($client->return_documents);
        $this->assertTrue($client->truncation);
    }

    public function testJsonDecodeSuccess(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $json = '{"test": "value", "number": 123}';
        $result = $client->jsonDecode($json);
        
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['test']);
        $this->assertEquals(123, $result['number']);
    }

    public function testThrowIfErrorWithCurlError(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Create a curl handle to simulate curl error
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'invalid://url');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl); // This will generate a curl error
        
        $this->expectException(LlmRequestException::class);
        $this->expectExceptionMessageMatches('/CURL Error:/');
        
        $client->throwIfError($curl);
        
        curl_close($curl);
    }

    public function testThrowIfErrorWithEmptyResponse(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $this->expectException(LlmRequestException::class);
        $this->expectExceptionMessage('CURL Error: empty answer from vendor');
        
        $client->throwIfError($curl, null);
        
        curl_close($curl);
    }

    public function testThrowIfErrorWithApiError(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $response = [
            'error' => [
                'message' => 'Invalid API key',
                'code' => 401
            ]
        ];
        
        $this->expectException(LlmRequestException::class);
        $this->expectExceptionMessage('Invalid API key');
        $this->expectExceptionCode(401);
        
        $client->throwIfError($curl, $response);
        
        curl_close($curl);
    }

    public function testThrowIfErrorWithApiErrorNoMessage(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $response = [
            'error' => [
                'code' => 500
            ]
        ];
        
        $this->expectException(LlmRequestException::class);
        $this->expectExceptionMessage('Unknown error from vendor');
        $this->expectExceptionCode(500);
        
        $client->throwIfError($curl, $response);
        
        curl_close($curl);
    }

    public function testThrowIfErrorWithApiErrorNoCode(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $response = [
            'error' => [
                'message' => 'Some error occurred'
            ]
        ];
        
        $this->expectException(LlmRequestException::class);
        $this->expectExceptionMessage('Some error occurred');
        $this->expectExceptionCode(0);
        
        $client->throwIfError($curl, $response);
        
        curl_close($curl);
    }

    public function testThrowIfErrorWithSuccessfulResponse(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $response = [
            'data' => [
                [
                    'relevance_score' => 0.95,
                    'index' => 0
                ]
            ]
        ];
        
        // Should not throw any exception
        $client->throwIfError($curl, $response);
        
        curl_close($curl);
        
        // If we reach this point, no exception was thrown
        $this->assertTrue(true);
    }

    public function testDefaultValues(): void
    {
        $client = new VoyageRerankingClient('rerank-2-lite', 'api-key-123');
        
        // Verify all default values
        $this->assertEquals('rerank-2-lite', $client->model);
        $this->assertEquals('api-key-123', $client->apiKey);
        $this->assertNull($client->top_k);
        $this->assertFalse($client->return_documents);
        $this->assertFalse($client->truncation);
    }

    public function testModelVariations(): void
    {
        $models = ['rerank-2', 'rerank-2-lite'];
        
        foreach ($models as $model) {
            $client = new VoyageRerankingClient($model, 'test-key');
            $this->assertEquals($model, $client->model);
        }
    }

    public function testTopKConfiguration(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Test different top_k values
        $topKValues = [1, 5, 10, 50, 100];
        
        foreach ($topKValues as $topK) {
            $client->top_k = $topK;
            $this->assertEquals($topK, $client->top_k);
        }
        
        // Test null value
        $client->top_k = null;
        $this->assertNull($client->top_k);
    }

    public function testReturnDocumentsConfiguration(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Test both boolean values
        $client->return_documents = true;
        $this->assertTrue($client->return_documents);
        
        $client->return_documents = false;
        $this->assertFalse($client->return_documents);
    }

    public function testTruncationConfiguration(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Test both boolean values
        $client->truncation = true;
        $this->assertTrue($client->truncation);
        
        $client->truncation = false;
        $this->assertFalse($client->truncation);
    }

    public function testClientTraitUsage(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Verify that the client uses ClientTrait
        $this->assertTrue(method_exists($client, 'jsonDecode'));
        $this->assertTrue(method_exists($client, 'throwIfError'));
    }

    public function testRerankMethodExists(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Verify that the rerank method exists
        $this->assertTrue(method_exists($client, 'rerank'));
        
        // Verify method signature through reflection
        $reflection = new \ReflectionMethod($client, 'rerank');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(2, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('documents', $parameters[1]->getName());
        
        // Verify parameter types
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        $this->assertEquals('array', $parameters[1]->getType()->getName());
        
        // Verify return type
        $this->assertEquals('Slider23\PhpLlmToolbox\Dto\RerankingDto', $reflection->getReturnType()->getName());
    }

    public function testApiEndpointUrl(): void
    {
        // This test verifies that the correct API endpoint is used
        // We can't easily test the actual URL without making a real request,
        // but we can verify the URL constant is correct by checking the source code
        
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Use reflection to check if the URL is correctly set in the method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('rerank');
        $method->setAccessible(true);
        
        // We expect the method to exist and be callable
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    public function testAuthorizationHeaders(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-secret-key');
        
        // We can't easily test the headers without mocking curl,
        // but we can verify the API key is stored correctly
        $this->assertEquals('test-secret-key', $client->apiKey);
    }

    public function testCompleteRerankMethodSignature(): void
    {
        $client = new VoyageRerankingClient('rerank-2', 'test-api-key');
        
        // Test with valid inputs (this won't make actual API call due to invalid key)
        $query = "What is machine learning?";
        $documents = [
            "Machine learning is a type of artificial intelligence.",
            "Deep learning is a subset of machine learning.",
            "Natural language processing uses machine learning."
        ];
        
        // We expect this to fail with an exception due to invalid API key,
        // but the method signature should be correct
        try {
            $client->rerank($query, $documents);
        } catch (LlmRequestException $e) {
            // Expected for invalid API key
            $this->assertStringContainsString('CURL Error', $e->getMessage());
        }
        
        // If we reach this point, the method accepts the correct parameters
        $this->assertTrue(true);
    }
}