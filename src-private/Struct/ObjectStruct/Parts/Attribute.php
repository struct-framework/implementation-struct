<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct\Parts;

readonly class Attribute
{
    /**
     * @param array<mixed> $arguments
     */
    public function __construct(
        public string $name,
        public int $target,
        public bool $isRepeated,
        public array $arguments,
    ) {
    }
}
