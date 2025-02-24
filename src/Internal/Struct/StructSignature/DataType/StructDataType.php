<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

use DateTimeInterface;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use UnitEnum;

/**
 * @internal
 */
readonly class StructDataType
{
    /**
     * @param null|class-string<UnitEnum>|class-string<DateTimeInterface>|class-string<StructInterface>|class-string<DataTypeInterface> $className
     */
    public function __construct(
        public StructUnderlyingDataType $structUnderlyingDataType,
        public ?UnclearDataType $clearDataType,
        public ?string $className,
        public ?bool $isAbstract,
    ) {
    }
}
