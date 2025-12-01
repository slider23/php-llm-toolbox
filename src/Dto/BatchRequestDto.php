<?php

namespace Slider23\PhpLlmToolbox\Dto;

class BatchRequestDto
{
    public string $customId;
    public array $requests;

    public function __construct(string $customId, array $requests)
    {
        $this->customId = $customId;
        $this->setRequests($requests);
    }

    public function normalizeRequests()
    {
        if(isset($this->requests['role'])){
            $this->requests = [$this->requests];
        }
    }

    public function setRequests(array $requests): void
    {
        $this->requests = $requests;
        $this->normalizeRequests();
    }
}