<?php

namespace Slider23\PhpLlmToolbox\Dto;

class SearchResultDto
{
    public string $url;
    public string $title;
    public array $data;

    public function __construct(string $url, string $title, array $data = [])
    {
        $this->url = $url;
        $this->title = $title;
        $this->data = $data;
    }
}