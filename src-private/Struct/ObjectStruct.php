<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct;

use Struct\Struct\Private\Struct\ObjectStruct\Method;
use Struct\Struct\Private\Struct\ObjectStruct\Parameter;
use Struct\Struct\Private\Struct\ObjectStruct\Property;

readonly class ObjectStruct
{
    /**
     * @param array<Parameter> $constructorArguments
     * @param array<Property> $properties
     * @param array<Method> $methods
     */
    public function __construct(
        public array $constructorArguments,
        public array $properties,
        public array $methods,
    )
    {}
}

