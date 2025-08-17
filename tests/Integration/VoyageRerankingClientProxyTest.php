<?php

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageRerankingClient;
use Slider23\PhpLlmToolbox\Dto\RerankingDto;

class VoyageRerankingClientProxyTest extends TestCase
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
    public function testRerankingRequestViaProxy()
    {
        $model = "rerank-2";
        $client = new VoyageRerankingClient($model, $this->apiKey);

        $client->timeout = 10;

        $query = "What is the capital of France?";
        $documents = [
            "Paris is the capital and most populous city of France.",
            "London is the capital of England.",
            "Berlin is the capital of Germany.",
            "Madrid is the capital of Spain."
        ];

        $client->setProxy(getenv('PROXY_URL'), getenv('PROXY_LOGIN'), getenv('PROXY_PASSWORD'));

        $response = $client->reranking($query, $documents);

        $this->assertInstanceOf(RerankingDto::class, $response);
        $this->assertEquals('success', $response->status);
        $this->assertEquals('voyage', $response->vendor);
        $this->assertEquals('rerank-2', $response->model);
        $this->assertIsArray($response->data);
        $this->assertNotEmpty($response->data, "Reranking data should not be empty.");
        $this->assertGreaterThan(0, $response->tokens, "Token count should be greater than 0.");
        $this->assertIsFloat($response->cost);
        $this->assertGreaterThan(0, $response->cost, "Cost should be greater than 0.");
        $this->assertNull($response->errorMessage);
    }
}