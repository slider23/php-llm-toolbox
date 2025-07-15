<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit;
use Slider23\PhpLlmToolbox\Tests\Unit\Dto\QuestionDto;
use Spiral\JsonSchemaGenerator\Generator;

class JsonSchemaTest extends \PHPUnit\Framework\TestCase
{

    public function testUseDtoForJson()
    {
        $generator = new Generator();
        $schema = $generator->generate(QuestionDto::class);
        trap($schema);
        $result = $schema->jsonSerialize();
        trap($result);

        $this->assertArrayHasKey("properties", $result);
        $this->assertArrayHasKey("title", $result['properties']);
    }

}