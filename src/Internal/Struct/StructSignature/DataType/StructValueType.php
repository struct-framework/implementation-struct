<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

use Struct\Reflection\Internal\Struct\ObjectSignature\Value;

/**
 * @internal
 */
readonly class StructValueType
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public ?StructUnderlyingDataType $structUnderlyingDataType,
        public ?string $className,
        public mixed $rawDataValue,
        public ?Value $value = null,
    ) {
    }
}
