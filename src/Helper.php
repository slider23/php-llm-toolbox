<?php

namespace Slider23\PhpLlmToolbox;

class Helper
{
    public static function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}