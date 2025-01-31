<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

use Struct\Attribute\ArrayList;

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
        public bool $hasDefaultValue,
        public mixed $defaultValue,
        #[ArrayList(StructBaseDataType::class)]
        public array $structDataTypes,
        public ?StructArrayType $structArrayType,
    ) {
    }
}
