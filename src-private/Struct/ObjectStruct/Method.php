<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct;

use Struct\Struct\Private\Struct\ObjectStruct\Parts\Attribute;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\NamedType;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\Visibility;

readonly class Method
{
    /**
     * @param array<Parameter> $parameters
     * @param array<Attribute> $attributes
     */
    public function __construct(
        public string $name,
        public ?NamedType $returnType,
        public bool $returnAllowsNull,
        public Visibility $visibility,
        public ?bool $isStatic,
        public array $parameters,
        public array $attributes,
    )
    {}
}

