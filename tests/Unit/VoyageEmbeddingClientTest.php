<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageEmbeddingClient;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\WrongJsonException;

class VoyageEmbeddingClientTest extends TestCase
{
    public function testConstructor(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        $this->assertEquals('voyage-3-large', $client->model);
        $this->assertEquals('test-api-key', $client->apiKey);
        $this->assertEquals(60, $client->timeout);
        $this->assertFalse($client->debug);
        $this->assertNull($client->input_type);
        $this->assertTrue($client->truncation);
        $this->assertNull($client->output_dimensions);
        $this->assertEquals('float', $client->output_dtype);
        $this->assertNull($client->encoding_format);
    }

    public function testJsonDecodeSuccess(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        $json = '{"test": "value", "number": 123}';
        $result = $client->jsonDecode($json);
        
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['test']);
        $this->assertEquals(123, $result['number']);
    }

    public function testJsonDecodeInvalidJson(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        $this->expectException(WrongJsonException::class);
        $client->jsonDecode('invalid json');
    }

    public function testThrowIfErrorWithCurlError(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
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
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
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
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
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
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
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
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
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
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://httpbin.org/status/200');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curl);
        
        $response = [
            'data' => [
                [
                    'embedding' => [0.1, 0.2, 0.3]
                ]
            ]
        ];
        
        // Should not throw any exception
        $client->throwIfError($curl, $response);
        
        curl_close($curl);
        
        // If we reach this point, no exception was thrown
        $this->assertTrue(true);
    }

    public function testConfigurableProperties(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        // Test setting all configurable properties
        $client->timeout = 120;
        $client->debug = true;
        $client->input_type = 'query';
        $client->truncation = false;
        $client->output_dimensions = 1024;
        $client->output_dtype = 'int8';
        $client->encoding_format = 'base64';
        
        $this->assertEquals(120, $client->timeout);
        $this->assertTrue($client->debug);
        $this->assertEquals('query', $client->input_type);
        $this->assertFalse($client->truncation);
        $this->assertEquals(1024, $client->output_dimensions);
        $this->assertEquals('int8', $client->output_dtype);
        $this->assertEquals('base64', $client->encoding_format);
    }

    public function testInputTypeHandling(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        // Test when no input_type is set and none passed to method
        $client->input_type = null;
        $reflection = new \ReflectionMethod($client, 'createEmbedding');
        $reflection->setAccessible(true);
        
        // We can't easily test the actual HTTP call without integration tests,
        // but we can verify the input_type behavior through reflection or
        // by mocking the curl calls
        
        // Test when input_type is set on client
        $client->input_type = 'document';
        // Method parameter should override client setting
        // This would be tested in integration tests with actual API calls
        
        $this->assertEquals('document', $client->input_type);
    }

    public function testDefaultValues(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3.5', 'api-key-123');
        
        // Verify all default values
        $this->assertEquals('voyage-3.5', $client->model);
        $this->assertEquals('api-key-123', $client->apiKey);
        $this->assertEquals(60, $client->timeout);
        $this->assertFalse($client->debug);
        $this->assertNull($client->input_type);
        $this->assertTrue($client->truncation);
        $this->assertNull($client->output_dimensions);
        $this->assertEquals('float', $client->output_dtype);
        $this->assertNull($client->encoding_format);
    }

    public function testInputTypeOptions(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        // Test valid input_type values
        $validTypes = [null, 'query', 'document'];
        
        foreach ($validTypes as $type) {
            $client->input_type = $type;
            $this->assertEquals($type, $client->input_type);
        }
    }

    public function testOutputDtypeOptions(): void
    {
        $client = new VoyageEmbeddingClient('voyage-3-large', 'test-api-key');
        
        // Test different output_dtype values
        $dtypes = ['float', 'int8', 'uint8', 'binary', 'ubinary'];
        
        foreach ($dtypes as $dtype) {
            $client->output_dtype = $dtype;
            $this->assertEquals($dtype, $client->output_dtype);
        }
    }

    public function testModelVariations(): void
    {
        $models = [
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
        
        foreach ($models as $model) {
            $client = new VoyageEmbeddingClient($model, 'test-key');
            $this->assertEquals($model, $client->model);
        }
    }
}