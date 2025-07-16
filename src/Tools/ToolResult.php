<?php

namespace Slider23\PhpLlmToolbox\Tools;

class ToolResult
{
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data,
        public readonly ?string $error = null
    ) {}

    public static function success(mixed $data): self
    {
        return new self(true, $data);
    }

    public static function error(string $error): self
    {
        return new self(false, null, $error);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error
        ];
    }
}