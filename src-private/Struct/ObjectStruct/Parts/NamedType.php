<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct\Parts;

readonly class NamedType
{
    public function __construct(
        public string $type,
        public bool $isBuiltin,
    ) {
    }
}
