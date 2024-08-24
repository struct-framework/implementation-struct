<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct\Parts;

readonly class IntersectionType
{
    public function __construct(
        public array $namedTypes,
    ) {
    }
}
