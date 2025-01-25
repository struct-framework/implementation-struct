<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct;



use Struct\Struct\Internal\Struct\StructSignature\Property;

/**
 * @internal
 */
readonly class StructSignature
{
    /**
     * @param array<Property> $properties
     */
    public function __construct(
        public array $properties,
    ) {
    }
}
