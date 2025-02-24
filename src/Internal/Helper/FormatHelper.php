<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Helper;

use BackedEnum;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use Struct\Contracts\DataTypeInterface;
use Struct\Exception\DeserializeException;
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
        /** @var string $rawDataValue */
        $rawDataValue = $structValueType->rawDataValue;
        try {
            $dateTime = new DateTimeImmutable($rawDataValue);
        } catch (DateMalformedStringException $e) {
            throw new DeserializeException(1740334702, 'Invalid value <'.$rawDataValue.'> for date time');
        }
        return $dateTime;
    }


    public static function buildDataType(StructValueType $structValueType): DataTypeInterface
    {
        /** @var class-string<DataTypeInterface> $className */
        $className = $structValueType->className;
        /** @var string $rawDataValue */
        $rawDataValue = $structValueType->rawDataValue;
        return DataTypeFactory::create($className, $rawDataValue);
    }


    public static function buildEnumInt(StructValueType $structValueType): \BackedEnum
    {
        /** @var class-string<BackedEnum> $className */
        $className = $structValueType->className;
        /** @var int $rawDataValue */
        $rawDataValue = $structValueType->rawDataValue;
        $enum = $className::tryFrom($rawDataValue);
        if($enum === null) {
            throw new DeserializeException(1740334619, 'Invalid value <'.$rawDataValue.'> for enum <'.$className.'>');
        }
        return $enum;
    }

    public static function buildEnumString(StructValueType $structValueType): \BackedEnum
    {
        /** @var class-string<BackedEnum> $className */
        $className = $structValueType->className;
        /** @var string $rawDataValue */
        $rawDataValue = $structValueType->rawDataValue;
        $enum = $className::tryFrom($rawDataValue);
        if($enum === null) {
            throw new DeserializeException(1740334609, 'Invalid value <'.$rawDataValue.'> for enum <'.$className.'>');
        }
        return $enum;
    }


    public static function buildEnum(StructValueType $structValueType): UnitEnum
    {
        $className = $structValueType->className;
        $rawDataValue = $structValueType->rawDataValue;
        if($className === null) {
            throw new UnexpectedException(1740334919);
        }
        if(is_a($className, UnitEnum::class, true) === false) {
            throw new UnexpectedException(1740334806);
        }
        if(is_string($rawDataValue) === false) {
            throw new UnexpectedException(1740334850);
        }
        foreach ($className::cases() as $case) {
            if ($case->name === $rawDataValue) {
                return $case;
            }
        }
        throw new DeserializeException(1739092984, 'Invalid value < ' . $rawDataValue . '> for enum < '. $className . '>');
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
            null,
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::ArrayList,
            StructUnderlyingDataType::Struct => throw new UnexpectedException(1740315323),
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
