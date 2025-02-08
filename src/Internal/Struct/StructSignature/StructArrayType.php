<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

use Struct\Attribute\ArrayList;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataType;

/**
 * @internal
 */
readonly class StructArrayType
{
    /**
     * @param array<StructDataType> $structDataTypes
     */
    public function __construct(
        public StructArrayTypeOption $structArrayTypeOption,
        #[ArrayList(StructDataType::class)]
        public ?array $structDataTypes,
    ) {
    }
}
