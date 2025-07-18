<?php

namespace Slider23\PhpLlmToolbox\Dto;

use Slider23\PhpLlmToolbox\Exceptions\LlmVendorException;

class LlmResponseDto
{
    public ?string $id = null;
    public ?string $model = null;
    public ?string $vendor = null;
    public ?string $assistantContent = null;
    public ?string $assistantThinkingContent = null;
    public ?string $finishReason = null;

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
    public ?array $searchResults = [];
    
    // Tool-related attributes
    public bool $toolsUsed = false;
    public ?array $toolCalls = [];
    public ?array $toolResults = [];

    public array $rawResponse = [];

    public function __construct()
    {

    }

    public function _extractThinking(): void
    {
        if(strpos($this->assistantContent, "<thinking>") !== false){
            preg_match("/<thinking>(.*?)<\/thinking>/s", $this->assistantContent, $matches);
            $this->assistantThinkingContent = $matches[1] ?? null;
            $this->assistantContent = preg_replace("/<thinking>.*?<\/thinking>/s", "", $this->assistantContent);
        }
        if(strpos($this->assistantContent, "<think>") !== false){
            preg_match("/<think>(.*?)<\/think>/s", $this->assistantContent, $matches);
            $this->assistantThinkingContent = $matches[1] ?? null;
            $this->assistantContent = preg_replace("/<think>.*?<\/think>/s", "", $this->assistantContent);
        }
    }

    public function trap()
    {
        if(function_exists("trap")){
            $copy = clone $this;
            $copy->rawResponse = [];
            trap($copy);
        }
    }
}
