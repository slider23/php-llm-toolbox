<?php

namespace Slider23\PhpLlmToolbox\Dto;

use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class LlmResponseDto
{
    public ?string $id = null;
    public ?string $model = null;
    public ?string $vendor = null;
    public ?string $assistant_content = null;
    public ?string $assistant_reasoning_content = null;
    public ?string $finish_reason = null;

    public ?int $inputTokens = null;
    public ?int $outputTokens = null;
    public ?int $cacheCreationInputTokens = null;
    public ?int $cacheReadInputTokens = null;
    public ?int $thinkTokens = null;
    public ?int $citationTokens = null;
    public ?int $totalTokens = null;
    public ?float $cost = null;
    public ?string $perplexitySearchContextSize = null;
    public ?int $perplexityCitationTokens = null;
    public ?int $perplexityNumSearchQueries = null;
    public ?int $httpStatusCode = null;
    public ?string $status = null;
    public bool $error = false;
    public ?string $errorMessage = null;

    public ?array $citations = [];
    /**
     * @var SearchResultDto[]
     */
    public ?array $search_results = [];

    public array $rawResponse = [];

    public function __construct()
    {

    }

    public function _extractThinking(): void
    {
        if(strpos($this->assistant_content, "<thinking>") !== false){
            preg_match("/<thinking>(.*?)<\/thinking>/s", $this->assistant_content, $matches);
            $this->assistant_reasoning_content = $matches[1] ?? null;
            $this->assistant_content = preg_replace("/<thinking>.*?<\/thinking>/s", "", $this->assistant_content);
        }
        if(strpos($this->assistant_content, "<think>") !== false){
            preg_match("/<think>(.*?)<\/think>/s", $this->assistant_content, $matches);
            $this->assistant_reasoning_content = $matches[1] ?? null;
            $this->assistant_content = preg_replace("/<think>.*?<\/think>/s", "", $this->assistant_content);
        }
    }
}
