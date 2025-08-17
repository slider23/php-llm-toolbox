<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageEmbeddingClient;
use Slider23\PhpLlmToolbox\Dto\EmbeddingDto;

class VoyageEmbeddingClientProxyTest extends TestCase
{
    private ?string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiKey = getenv('VOYAGE_API_KEY') ? $_ENV['VOYAGE_API_KEY'] : null;

        if (empty($this->apiKey)) {
            $this->markTestSkipped(
                'Voyage API key not configured in environment variables (VOYAGE_API_KEY) or contains placeholder value.'
            );
        }
    }
    public function testEmbeddingRequestViaProxy()
    {
        $model = "voyage-3.5";
        $client = new VoyageEmbeddingClient($model, $this->apiKey);

        $client->timeout = 10;

        $client->setProxy(getenv('PROXY_URL'), getenv('PROXY_LOGIN'), getenv('PROXY_PASSWORD'));

        $response = $client->embedding("Machine learning is transforming how we process data.");

        $this->assertInstanceOf(EmbeddingDto::class, $response);
        $this->assertEquals('success', $response->status);
        $this->assertEquals('voyage', $response->vendor);
        $this->assertEquals('voyage-3.5', $response->model);
        $this->assertNotEmpty($response->embedding);
        $this->assertIsArray($response->embedding);
        $this->assertGreaterThan(0, $response->tokens);
        $this->assertIsFloat($response->cost);
        $this->assertGreaterThan(0, $response->cost);
    }
}