<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;


use Struct\Contracts\DataTypeInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructDataType;

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
        if($value === null) {
            return null;
        }
        $structDataType = $structDataTypes[0];
        if(self::_valueIsSameDataType($structDataType, $value) === true) {
            return $value;
        }
        $value = match ($structDataType->structBaseDataType) {
            StructBaseDataType::Boolean => self::_processBool($value),
            StructBaseDataType::Integer => self::_processInt($value),
            StructBaseDataType::Float => self::_processFloat($value),
            StructBaseDataType::String => self::_processString($value),
            StructBaseDataType::Enum => self::_processEnum($structDataType, $value),
            StructBaseDataType::DateTime => self::_processDateTime($value),
            StructBaseDataType::Array => new Value([]),
            StructBaseDataType::DataType => self::_processDataType($structDataType, $value),
            StructBaseDataType::Struct => null,
        };
        return $value;
    }


    protected static function _processDataType(StructDataType $structDataType, Value $value): ?Value
    {
        $className = $structDataType->className;
        if(is_a($className, DataTypeInterface::class, true) === false) {
            return null;
        }
        $valueData = $value->valueData;
        if(is_string($valueData) === false) {
            return null;
        }
        $dataType = DataTypeFactory::create($structDataType->className, $valueData);
        return new Value($dataType);
    }

    protected static function _processEnum(StructDataType $structDataType, Value $value): ?Value
    {
        $valueData = $value->valueData;
        if(
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

    protected static function _processFloat(Value $value): ?Value
    {
        if(is_float($value->valueData) === false) {
            return null;
        }
        return $value;
    }

    protected static function _processBool(Value $value): ?Value
    {
        if(is_bool($value->valueData) === false) {
            return null;
        }
        return $value;
    }

    protected static function _processInt(Value $value): ?Value
    {
        if(is_int($value->valueData) === false) {
            return null;
        }
        return $value;
    }

    protected static function _processString(Value $value): ?Value
    {
        if(is_string($value->valueData) === false) {
            return null;
        }
        return $value;
    }

    protected static function _processDateTime(Value $value): ?Value
    {
        $valueData = $value->valueData;
        if(is_a($valueData, \DateTimeInterface::class, true) === true) {
            return $value;
        }
        $dateTime = null;
        if(is_string($valueData) === true) {
            $dateTime = new \DateTimeImmutable($valueData);
        }
        if($dateTime === null) {
            $dateTime = new \DateTimeImmutable('1900-01-01 00:00:00', new \DateTimeZone('UTC'));
        }
        $value = new Value($dateTime);
        return $value;
    }

    protected static function _valueIsSameDataType(StructDataType $structDataType, Value $value): bool
    {
        if($structDataType->className === null) {
            return false;
        }
        if(is_a($value->valueData, $structDataType->className, true) === false) {
            return false;
        }
        return true;
    }
}
