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
        public bool $unclearInt = false,
        public bool $unclearString = false,
        public bool $unclearArray = false,
        #[ArrayList(StructDataType::class)]
        public array $structDataTypes,
    ) {
    }
}
