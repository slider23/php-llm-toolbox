<?php

namespace Slider23\PhpLlmToolbox\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Clients\VoyageRerankingClient;
use Slider23\PhpLlmToolbox\Dto\RerankingDto;
use Slider23\PhpLlmToolbox\Exceptions\LlmRequestException;
use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class VoyageRerankingClientTest extends TestCase
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

    public function testSuccessfulRerankingWithRerank2(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);

        $query = "What is machine learning?";
        $documents = [
            "Deep learning is a subset of machine learning that uses neural networks.",
            "Paris is the capital city of France.",
            "Machine learning is a type of artificial intelligence that enables computers to learn.",
            "The weather today is sunny with a temperature of 25 degrees.",
            "Artificial intelligence includes machine learning and deep learning technologies."
        ];

        try {
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

            // Verify result structure
            foreach ($response->data as $result) {
                $this->assertArrayHasKey('relevance_score', $result);
                $this->assertArrayHasKey('index', $result);
                $this->assertIsFloat($result['relevance_score']);
                $this->assertIsInt($result['index']);
                $this->assertGreaterThanOrEqual(0, $result['relevance_score']);
                $this->assertLessThanOrEqual(1, $result['relevance_score']);
                $this->assertGreaterThanOrEqual(0, $result['index']);
                $this->assertLessThan(count($documents), $result['index']);
            }

            // Verify results are sorted by relevance score (descending)
            for ($i = 0; $i < count($response->data) - 1; $i++) {
                $this->assertGreaterThanOrEqual(
                    $response->data[$i + 1]['relevance_score'],
                    $response->data[$i]['relevance_score'],
                    "Results should be sorted by relevance score in descending order"
                );
            }

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testSuccessfulRerankingWithRerank2Lite(): void
    {
        $client = new VoyageRerankingClient('rerank-2-lite', $this->apiKey);

        $query = "Capital cities in Europe";
        $documents = [
            "London is the capital of the United Kingdom.",
            "Machine learning algorithms process data.",
            "Paris is the capital of France.",
            "Berlin is the capital of Germany.",
            "Artificial intelligence is growing rapidly."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertEquals('rerank-2-lite', $response->model);
            $this->assertIsArray($response->data);
            $this->assertNotEmpty($response->data);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);

            // Verify cost calculation for rerank-2-lite
            $expectedCost = $response->tokens * (0.02 / 1_000_000);
            $this->assertEquals($expectedCost, $response->cost, '', 0.0000001);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRerankingWithReturnDocuments(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);
        $client->return_documents = true;

        $query = "Programming languages";
        $documents = [
            "Python is a popular programming language for data science.",
            "The ocean is blue and vast.",
            "JavaScript is used for web development.",
            "Cats are domestic animals.",
            "Java is an object-oriented programming language."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertNotEmpty($response->data);

            // When return_documents is true, each result should include the document text
            foreach ($response->data as $result) {
                $this->assertArrayHasKey('relevance_score', $result);
                $this->assertArrayHasKey('index', $result);
                $this->assertArrayHasKey('document', $result, "Document should be included when return_documents is true");
                $this->assertIsString($result['document']);
                $this->assertContains($result['document'], $documents, "Returned document should match one of the input documents");
            }

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRerankingWithTopK(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);
        $client->top_k = 3;

        $query = "Artificial intelligence";
        $documents = [
            "Machine learning is a subset of artificial intelligence.",
            "Cooking recipes for Italian pasta.",
            "Deep learning uses neural networks for AI.",
            "Sports statistics from last season.",
            "Natural language processing is an AI field.",
            "Travel destinations in Asia.",
            "Computer vision is part of artificial intelligence."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertLessThanOrEqual(3, count($response->data), "Should return at most top_k results");
            $this->assertGreaterThan(0, count($response->data), "Should return at least one result");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRerankingWithTruncation(): void
    {
        $client = new VoyageRerankingClient('rerank-2-lite', $this->apiKey);
        $client->truncation = true;

        $query = "Technology trends";
        $documents = [
            "Artificial intelligence and machine learning are revolutionizing technology. " . str_repeat("Long text content to test truncation behavior. ", 50),
            "Climate change is affecting global weather patterns.",
            "Blockchain technology is used in cryptocurrencies and decentralized applications. " . str_repeat("More content to make this document longer. ", 30),
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertNotEmpty($response->data);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRerankingWithSingleDocument(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);

        $query = "Machine learning";
        $documents = [
            "Machine learning is a powerful tool for data analysis and prediction."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertCount(1, $response->data);
            $this->assertEquals(0, $response->data[0]['index']);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testRerankingRelevanceScoring(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);

        $query = "Python programming language";
        $documents = [
            "Python is a high-level programming language known for its simplicity and readability.",
            "The Python programming language was created by Guido van Rossum in 1991.",
            "Elephants are large mammals that live in Africa and Asia.",
            "Python libraries like NumPy and Pandas are popular for data science.",
            "Cars require regular maintenance to operate efficiently."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertNotEmpty($response->data);

            // The first few results should have higher relevance scores for Python-related documents
            $pythonRelated = 0;
            for ($i = 0; $i < min(3, count($response->data)); $i++) {
                $index = $response->data[$i]['index'];
                $document = $documents[$index];
                if (stripos($document, 'python') !== false) {
                    $pythonRelated++;
                }
            }

            $this->assertGreaterThanOrEqual(1, $pythonRelated, "Top results should include Python-related documents");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testComplexRerankingScenario(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);
        $client->return_documents = true;
        $client->top_k = 5;

        $query = "Climate change and environmental impact";
        $documents = [
            "Global warming is causing ice caps to melt and sea levels to rise.",
            "Machine learning algorithms can predict stock market trends.",
            "Renewable energy sources like solar and wind power help reduce carbon emissions.",
            "The latest smartphone models feature improved camera technology.",
            "Deforestation contributes significantly to climate change and biodiversity loss.",
            "Electric vehicles are becoming more popular as an eco-friendly transportation option.",
            "Social media platforms are changing how people communicate.",
            "Carbon capture technology may help mitigate the effects of climate change.",
            "Artificial intelligence is revolutionizing healthcare diagnostics.",
            "Plastic pollution in oceans is harming marine ecosystems and wildlife."
        ];

        try {
            $response = $client->reranking($query, $documents);
//            trap($response);
            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertLessThanOrEqual(5, count($response->data));
            $this->assertGreaterThan(0, count($response->data));

            // Verify that climate/environment related documents rank higher
            $environmentalTerms = ['climate', 'environmental', 'carbon', 'renewable', 'deforestation', 'electric', 'pollution'];
            $topResults = array_slice($response->data, 0, 3);
            $environmentalCount = 0;

            foreach ($topResults as $result) {
                $this->assertArrayHasKey('document', $result);
                $document = strtolower($result['document']);
                
                foreach ($environmentalTerms as $term) {
                    if (strpos($document, $term) !== false) {
                        $environmentalCount++;
                        break;
                    }
                }
            }

            $this->assertGreaterThanOrEqual(2, $environmentalCount, "Top 3 results should include environmental content");

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testCostCalculationAccuracy(): void
    {
        $client = new VoyageRerankingClient('rerank-2', $this->apiKey);

        $query = "Cost calculation test for reranking";
        $documents = [
            "First test document for cost calculation.",
            "Second test document for cost calculation.",
            "Third test document for cost calculation."
        ];

        try {
            $response = $client->reranking($query, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertGreaterThan(0, $response->tokens);
            $this->assertIsFloat($response->cost);
            $this->assertGreaterThan(0, $response->cost);

            // Verify cost calculation matches expected rate for rerank-2 (0.05 per 1M tokens)
            $expectedCost = $response->tokens * (0.05 / 1_000_000);
            $this->assertEquals($expectedCost, $response->cost, '', 0.0000001);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }

    public function testLongQueryAndDocuments(): void
    {
        $client = new VoyageRerankingClient('rerank-2-lite', $this->apiKey);
        $client->truncation = true;

        $longQuery = "What are the implications of artificial intelligence and machine learning technologies on modern society, including their impact on employment, privacy, ethics, and future technological development?";
        
        $documents = [
            "Artificial intelligence is transforming industries by automating complex tasks, improving efficiency, and enabling new capabilities. However, this technological advancement raises important questions about job displacement, as AI systems become capable of performing tasks traditionally done by humans. The impact on employment varies across sectors, with some jobs being eliminated while new roles emerge that require different skill sets.",
            "Privacy concerns in the digital age have become increasingly prominent as AI systems collect and analyze vast amounts of personal data. Machine learning algorithms can infer sensitive information about individuals from seemingly innocuous data points, raising questions about consent, data protection, and the right to privacy. Regulatory frameworks like GDPR attempt to address these concerns but struggle to keep pace with technological advancement.",
            "The ethical implications of AI development include issues of bias, fairness, and accountability. Machine learning models can perpetuate or amplify existing societal biases present in training data, leading to discriminatory outcomes in areas such as hiring, lending, and criminal justice. Ensuring AI systems are fair, transparent, and accountable requires ongoing effort from developers, policymakers, and society as a whole."
        ];

        try {
            $response = $client->reranking($longQuery, $documents);

            $this->assertInstanceOf(RerankingDto::class, $response);
            $this->assertEquals('success', $response->status);
            $this->assertIsArray($response->data);
            $this->assertNotEmpty($response->data);

        } catch (LlmVendorException $e) {
            $this->fail("LlmVendorException was thrown: " . $e->getMessage());
        }
    }
}