<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

use Struct\Attribute\ArrayList;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;

/**
 * @internal
 */
readonly class StructElement
{
    /**
     * @param array<StructDataType> $structDataTypes
     */
    public function __construct(
        public string $name,
        public bool $isAllowsNull,
        public ?Value $defaultValue,
        #[ArrayList(StructDataType::class)]
        public array $structDataTypes,
        public ?StructArrayType $structArrayType,
    ) {
    }
}
