<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Helper;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use Struct\Contracts\DataTypeInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructValueType;
use UnitEnum;

/**
 * @internal
 */
class FormatHelper
{
    public static function formatDateTime(DateTimeInterface $value): string
    {
        return $value->format('c');
    }

    public static function formatEnum(UnitEnum $value): string|int
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        return $value->name;
    }

    public static function formatDataType(DataTypeInterface $value): string
    {
        $formattedValue = $value->serializeToString();
        return $formattedValue;
    }

    public static function buildDateTime(StructValueType $structValueType): DateTimeImmutable
    {
        $dataValue = $structValueType->rawDataValue;
        return new DateTimeImmutable($dataValue);
    }

    /**
     * @template T of DataTypeInterface
     * @param  class-string<T> $typeClassName
     * @return T
     */
    public static function buildDataType(StructValueType $structValueType): DataTypeInterface
    {
        $className = $structValueType->className;
        $dataValue = $structValueType->rawDataValue;
        return DataTypeFactory::create($className, $dataValue);
    }

    /**
     * @template T of \StringBackedEnum
     * @param  class-string<T> $typeClassName
     * @return T
     */
    public static function buildEnumInt(StructValueType $structValueType): \BackedEnum
    {
        $className = $structValueType->className;
        $dataValue = $structValueType->rawDataValue;
        return $className::tryFrom($dataValue);
    }

    /**
     * @template T of \StringBackedEnum
     * @param  class-string<T> $typeClassName
     * @return T
     */
    public static function buildEnumString(StructValueType $structValueType): \BackedEnum
    {
        $className = $structValueType->className;
        $dataValue = $structValueType->rawDataValue;
        return $className::tryFrom($dataValue);
    }

    /**
     * @template T of \UnitEnum
     * @param  class-string<T> $typeClassName
     * @return T
     */
    public static function buildEnum(StructValueType $structValueType): \UnitEnum
    {
        $className = $structValueType->className;
        $dataValue = $structValueType->rawDataValue;
        foreach ($className::cases() as $case) {
            if ($case->name === $dataValue) {
                return $case;
            }
        }
        throw new UnexpectedException(1739092984);
    }

    public static function buildStructDataType(StructValueType $structValueType): mixed
    {
        $result = match ($structValueType->structUnderlyingDataType) {
            StructUnderlyingDataType::String,
            StructUnderlyingDataType::Boolean,
            StructUnderlyingDataType::Integer,
            StructUnderlyingDataType::Float      => $structValueType->rawDataValue,
            StructUnderlyingDataType::Enum       => self::buildEnum($structValueType),
            StructUnderlyingDataType::EnumString => self::buildEnumString($structValueType),
            StructUnderlyingDataType::EnumInt    => self::buildEnumInt($structValueType),
            StructUnderlyingDataType::DateTime   => self::buildDateTime($structValueType),
            StructUnderlyingDataType::DataType   => self::buildDataType($structValueType),
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::ArrayList,
            StructUnderlyingDataType::Struct => throw new \Exception('To be implemented'),
        };
        return $result;
    }

    public static function buildValue(StructValueType $structValueType): ?Value
    {
        if ($structValueType->value !== null) {
            return $structValueType->value;
        }
        $dataValue = self::buildStructDataType($structValueType);
        return new Value($dataValue);
    }
}
