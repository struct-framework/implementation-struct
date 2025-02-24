<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Helper;

use BackedEnum;
use Exception\Unexpected\UnexpectedException;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use UnitEnum;

/**
 * @internal
 */
class EnumHelper
{
    /**
     * @param class-string<UnitEnum>|UnitEnum $enum
     */
    public static function findStructUnderlyingDataType(string|UnitEnum $enum): StructUnderlyingDataType
    {
        if(is_string($enum) === true) {
            if(is_a($enum, UnitEnum::class, true) === false) {
                throw new UnexpectedException(1740316167);
            }
            $enum = $enum::cases()[0];
        }
        return self::_findStructUnderlyingDataType($enum);
    }


    protected static function _findStructUnderlyingDataType(UnitEnum $enum): StructUnderlyingDataType
    {
        if (is_a($enum, BackedEnum::class) === true) {
            if(is_string($enum->value) === true) {
                return StructUnderlyingDataType::EnumString;
            }
            return StructUnderlyingDataType::EnumInt;
        }
        return StructUnderlyingDataType::Enum;
    }

}
