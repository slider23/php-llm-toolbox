<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\PerplexityResponseMapper;

class PerplexityResponseMapperTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testReplaceFootnotesWithLinks(): void
    {
        $text = "Text with a footnote1 [1]. 
Text with a footnote 2[2].
Text without footnotes.
";
        $links = [
            "https://example.com/footnote1",
            "https://example.com/footnote2",
        ];

        $expected = "Text with a footnote1 [>](https://example.com/footnote1). 
Text with a footnote 2[>](https://example.com/footnote2).
Text without footnotes.
";

        $result = PerplexityResponseMapper::replaceFootnotesWithLinks($text, $links);

        $this->assertEquals($expected, $result);

    }
}
