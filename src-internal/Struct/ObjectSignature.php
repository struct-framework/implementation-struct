<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct;

use Struct\Struct\Internal\Struct\ObjectSignature\Method;
use Struct\Struct\Internal\Struct\ObjectSignature\Parameter;
use Struct\Struct\Internal\Struct\ObjectSignature\Property;

/**
 * @internal
 */
readonly class ObjectSignature
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
    ) {
    }
}
