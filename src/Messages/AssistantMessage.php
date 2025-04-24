<?php

namespace App\Services\AiVendors;

class AssistantMessage
{
    public static function make(string $content): array
    {
        return [
            'role' => 'assistant',
            'content' => $content
        ];
    }
}
