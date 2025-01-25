<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\ObjectSignature;

use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\IntersectionType;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Visibility;

/**
 * @internal
 */
readonly class Method
{
    /**
     * @param array<Parameter> $parameters
     * @param array<Attribute> $attributes
     * @param array<NamedType|IntersectionType>|null $returnTypes
     */
    public function __construct(
        public string $name,
        public ?array $returnTypes,
        public bool $returnAllowsNull,
        public Visibility $visibility,
        public ?bool $isStatic,
        public array $parameters,
        public array $attributes,
    ) {
    }
}
