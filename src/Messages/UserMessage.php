<?php

namespace App\Services\AiVendors;

class UserMessage
{
    public static function make(string $content): array
    {
        return [
            'role' => 'user',
            'content' => $content
        ];
    }
}
