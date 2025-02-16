<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

/**
 * @internal
 */
readonly class StructDataType
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public StructUnderlyingDataType $structUnderlyingDataType,
        public ?UnclearDataType $clearDataType,
        public ?string $className,
        public ?bool $isAbstract,
    ) {
    }
}
