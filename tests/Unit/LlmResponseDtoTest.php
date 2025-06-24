<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slider23\PhpLlmToolbox\Dto\LlmResponseDto;

class LlmResponseDtoTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function testExtractThinking(): void
    {
        $dto = new LlmResponseDto();
        $dto->assistant_content = "<thinking>I am 
thinking about the 
next steps.</thinking>
This is a test response.";
        $dto->_extractThinking();
        $this->assertEquals("
This is a test response.", $dto->assistant_content);
    }

    public function testExtractThink(): void
    {
        $dto = new LlmResponseDto();
        $dto->assistant_content = "<think>I am 
thinking about the 
next steps.</think>
This is a test response.";
        $dto->_extractThinking();
        $this->assertEquals("
This is a test response.", $dto->assistant_content);
    }
}
