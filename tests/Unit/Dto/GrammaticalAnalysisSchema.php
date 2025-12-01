<?php

namespace Slider23\PhpLlmToolbox\Tests\Unit\Dto;

use Spiral\JsonSchemaGenerator\Attribute\Field;

class GrammaticalAnalysisSchema
{
    #[Field(description: 'Размышления о постановленной задаче, формирование последовательности действий')]
    public string $reasoning;
    /**
     * @var WordSchema[]
     */
    public array $words;
}

class WordSchema {
    #[Field(description: 'Cлово в нормальной форме')]
    public string $word;
    #[Field(description: 'Часть речи, к которой относится слово')]
    #[Field]
    public GrammaticalPometa $partOfSpeech;
}

enum GrammaticalPometa: string
{
    case Verb = 'глагол';
    case Noun = 'существительное';
    case Adjective = 'местный падеж';
    case Adverb = 'частица';
    case Preposition = 'предлог';
    case Conjunction = 'соединительная частица';
    case Pronoun = 'имя собственное';
    case Interjection = 'наречие';
    case Determiner = 'подлежащее';
    case Particle = 'отрицание';
    case AuxiliaryVerb = 'повелительная форма от глагола';
    case Predeterminer = 'предлог направления';
    case Postposition = 'составной глагол в будущем времени';
    case Other = 'другое';
}