<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Messages;

final class UserMessage
{
    public static function make(string $content): array
    {
        return [
            'role' => 'user',
            'content' => $content,
        ];
    }
}
