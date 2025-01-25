<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\ObjectSignature\Parts;

/**
 * @internal
 */
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
