<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit\Dto;

use Spiral\JsonSchemaGenerator\Attribute\Field;

class CityDto
{
    public function __construct(
        #[Field(title: 'name', description: 'Name of city')]
        public readonly string $name,
        #[Field(title: 'country', description: 'Name of country')]
        public readonly string $country,
        #[Field(title: 'population', description: 'Population of the city')]
        public readonly int $population,
    ) {
    }
}