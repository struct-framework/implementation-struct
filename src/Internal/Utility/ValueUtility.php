<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Contracts\DataTypeInterface;
use Struct\Exception\InvalidStructException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;

/**
 * @internal
 */
class ValueUtility
{
    /**
     * @param array<StructDataType> $structDataTypes
     */
    public static function processValue(array $structDataTypes, ?Value $value): ?Value
    {
        if ($value === null) {
            return null;
        }
        $valueData = $value->valueData;
        $valueDataType = StructDataTypeHelper::findDataTypeFromValue($valueData);
        if (count($structDataTypes) === 1) {
            $structDataType = $structDataTypes[0];
            $value = self::_processValue($structDataType, $valueData, $valueDataType);
            return new Value($value);
        }
        return $value;
    }

    protected static function _processValue(StructDataType $structDataType, StructBaseDataType $valueDataType, mixed $valueData): mixed
    {
        if (self::_valueIsSameDataType($structDataType, $valueDataType, $valueData) === true) {
            return $valueData;
        }
        $phpType = StructDataTypeHelper::findPhpType($structDataType->structUnderlyingDataType);
        if ($phpType !== $valueDataType) {
            throw new InvalidStructException(1739028724, 'Bla');
        }
        $value = match ($structDataType->structUnderlyingDataType) {
            StructBaseDataType::Boolean,
            StructBaseDataType::Integer,
            StructBaseDataType::Float,
            StructBaseDataType::String,
            StructBaseDataType::Enum => self::_processEnum($structDataType, $value),
            StructBaseDataType::DateTime => self::_processDateTime($value),
            StructBaseDataType::DataType => self::_processDataType($structDataType, $value),
            StructBaseDataType::Array,
            StructBaseDataType::Struct => null,
        };
        return $value;
    }

    protected static function _processDataType(StructDataType $structDataType, Value $value): ?Value
    {
        $className = $structDataType->className;
        if (is_a($className, DataTypeInterface::class, true) === false) {
            return null;
        }
        $valueData = $value->valueData;
        if (is_string($valueData) === false) {
            return null;
        }
        $dataType = DataTypeFactory::create($structDataType->className, $valueData);
        return new Value($dataType);
    }

    protected static function _processEnum(StructDataType $structDataType, Value $value): ?Value
    {
        $valueData = $value->valueData;
        if (
            is_int($valueData) === false &&
            is_string($valueData) === false
        ) {
            return null;
        }
        $className = $structDataType->className;
        if (is_a($className, \UnitEnum::class, true) === false) {
            return null;
        }
        if (is_a($className, \BackedEnum::class, true) === true) {
            $enum = $className::tryFrom($value->valueData);
            $value = new Value($enum);
            return $value;
        }
        $cases = $className::cases();
        foreach ($cases as $case) {
            if ($case->name === $value->valueData) {
                $value = new Value($case);
                return $value;
            }
        }
        return null;
    }

    protected static function _processDateTime(Value $value): ?Value
    {
        $valueData = $value->valueData;
        if (is_a($valueData, \DateTimeInterface::class, true) === true) {
            return $value;
        }
        $dateTime = null;
        if (is_string($valueData) === true) {
            $dateTime = new \DateTimeImmutable($valueData);
        }
        if ($dateTime === null) {
            $dateTime = new \DateTimeImmutable('1900-01-01 00:00:00', new \DateTimeZone('UTC'));
        }
        $value = new Value($dateTime);
        return $value;
    }

    protected static function _valueIsSameDataType(StructDataType $structDataType, StructBaseDataType $valueDataType, mixed $valueData): bool
    {
        if ($structDataType->structUnderlyingDataType === $valueDataType) {
            return $valueData;
        }
        if ($structDataType->className === null) {
            return false;
        }
        if (is_a($valueData, $structDataType->className, true) === false) {
            return false;
        }
        return true;
    }
}
