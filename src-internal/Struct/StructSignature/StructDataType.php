<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

use Struct\Attribute\ArrayList;

/**
 * @internal
 */
readonly class StructDataType
{

    /**
     * @param class-string $className
     */
    public function __construct(
        public StructBaseDataType $structBaseDataType,
        public ?string            $className = null,
    ) {
    }
}
