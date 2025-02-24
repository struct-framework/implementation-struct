<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

use Struct\Attribute\ArrayList;

/**
 * @internal
 */
readonly class StructDataTypeCollection
{
    /**
     * @param array<StructDataType> $structDataTypes
     */
    public function __construct(
        public bool $unclearInt,
        public bool $unclearString,
        public bool $unclearArray,
        #[ArrayList(StructDataType::class)]
        public array $structDataTypes,
    ) {
    }
}
