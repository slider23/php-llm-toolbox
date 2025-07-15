<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit\Dto;

use Spiral\JsonSchemaGenerator\Attribute\Field;

class QuestionDto
{
    public function __construct(
        #[Field(title: 'Question', description: 'Text of question')]
        public readonly string $title,
        #[Field(title: 'Year', description: 'The year of the movie')]
        public readonly int $year,
        #[Field(title: 'Description', description: 'The description of the question')]
        public readonly ?string $description = null,
        #[Field(title: 'Release Status', description: 'The release status of the movie')]
        public readonly ?Answer $answer = null,
    ) {
    }
}