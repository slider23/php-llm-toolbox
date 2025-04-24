<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Messages;

final class SystemMessage
{
    public static function make(string $content): array
    {
        return [
            'role' => 'system',
            'content' => $content,
        ];
    }
}
