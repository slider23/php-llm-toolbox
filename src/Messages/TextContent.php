<?php

namespace Slider23\PhpLlmToolbox\Messages;

final class TextContent
{
    public static function make($content, ?array $cacheAddonArray = []): array
    {
        $contentArray = [
            'type' => 'text',
            'text' => $content,
        ];

        if(is_array($cacheAddonArray)) {
            foreach ($cacheAddonArray as $key => $value) {
                if (is_array($value)) {
                    $contentArray[$key] = $value;
                } else {
                    $contentArray[$key] = [$value];
                }
            }
        }
        return $contentArray;
    }
}