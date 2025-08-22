<?php

namespace Slider23\PhpLlmToolbox\Dto;

class BatchInfoDto
{
    public string $vendor; // anthropic, openai, etc.
    public string $id;
    public string $type; // message_batch, embedding_batch, etc.
    public string $status; // pending, processing, completed, failed

    public ?string $createdAt;
    public ?string $endedAt;
    public ?string $expiresAt;
    public ?string $cancelInitiatedAt;


    public int $processing = 0;
    public int $succeeded = 0;
    public int $errored = 0;
    public int $canceled = 0;
    public int $expired = 0;

}