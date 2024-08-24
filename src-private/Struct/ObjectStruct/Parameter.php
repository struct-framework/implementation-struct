<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct;

use Struct\Struct\Private\Struct\ObjectStruct\Parts\IntersectionType;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\NamedType;

readonly class Parameter
{
    /**
     * @param array<NamedType|IntersectionType> $types
     * param array<Attribute> $attributes
     */
    public function __construct(
        public string $name,
        public array $types,
        public bool $isAllowsNull,
        public bool $hasDefaultValue,
        public mixed $defaultValue,
        public array $attributes,
        public ?string $arrayType,
        public bool $isArrayKeyList,
    )
    {}
}

