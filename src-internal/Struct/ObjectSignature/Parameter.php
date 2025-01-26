<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\ObjectSignature;

use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\IntersectionType;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\NamedType;

/**
 * @internal
 */
readonly class Parameter
{
    /**
     * @param array<NamedType|IntersectionType> $types
     * @param array<Attribute> $attributes
     */
    public function __construct(
        public string $name,
        public array $types,
        public bool $isPromoted,
        public bool $isAllowsNull,
        public bool $hasDefaultValue,
        public mixed $defaultValue,
        public array $attributes,
    ) {
    }
}
