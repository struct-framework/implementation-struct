<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\ObjectSignature\Parts;

/**
 * @internal
 */
readonly class NamedType
{
    public function __construct(
        public string $type,
        public bool $isBuiltin,
    ) {
    }
}
