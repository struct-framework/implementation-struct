<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

/**
 * @internal
 */
readonly class Property
{
    public function __construct(
        public Parameter $parameter,
        public ?bool $isReadOnly,
    ) {
    }
}
