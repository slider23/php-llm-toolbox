<?php

declare(strict_types=1);

namespace Slider23\PhpLlmToolbox\Messages;

final class SystemMessage
{
    public static function make(array|string $content): array
    {
        $promptArray = [
            'role' => 'system',
            'content' => $content,
        ];
        return $promptArray;
    }
}
