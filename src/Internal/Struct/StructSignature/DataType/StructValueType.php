<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

use DateTimeInterface;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use UnitEnum;

/**
 * @internal
 */
readonly class StructValueType
{
    /**
     * @param null|class-string<UnitEnum>|class-string<DateTimeInterface>|class-string<StructInterface>|class-string<DataTypeInterface> $className
     */
    public function __construct(
        public ?StructUnderlyingDataType $structUnderlyingDataType,
        public ?string $className,
        public mixed $rawDataValue,
        public ?Value $value,
    ) {
    }
}
