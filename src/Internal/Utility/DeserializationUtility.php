<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Internal\Helper\EnumHelper;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructValueType;

/**
 * @internal
 */
class DeserializationUtility
{
    public static function processValue(mixed $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        $processedValue = self::_processValue($valueData, $structDataTypeCollection);
        if ($processedValue === null) {
            return null;
        }
        return $processedValue;
    }

    protected static function _processValue(mixed $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        $processedValue = self::_findBaseType($valueData);
        if ($processedValue !== null) {
            return $processedValue;
        }
        $processedValue = self::_findDefinedType($valueData);
        if ($processedValue !== null) {
            return $processedValue;
        }
        $processedValue = self::_findStructType($valueData);
        if ($processedValue !== null) {
            return $processedValue;
        }
        $processedValue = self::_findStringIntArray($valueData, $structDataTypeCollection);
        if ($processedValue !== null) {
            return $processedValue;
        }

        return null;
    }

    protected static function _findBaseType(mixed $valueData): ?StructValueType
    {
        $structUnderlyingDataType = null;
        $value = null;
        if (is_null($valueData) === true) {
            $value = new Value(null);
        }
        if (is_bool($valueData) === true) {
            $structUnderlyingDataType = StructUnderlyingDataType::Boolean;
            $value = new Value($valueData);
        }
        if (is_float($valueData) === true) {
            $structUnderlyingDataType = StructUnderlyingDataType::Float;
            $value = new Value($valueData);
        }
        if ($value === null) {
            return null;
        }
        $structValueType = new StructValueType(
            $structUnderlyingDataType,
            null,
            $valueData,
            $value,
        );
        return $structValueType;
    }

    protected static function _findStringIntArray(mixed $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        if (is_int($valueData) === true) {
            return self::_findInt($valueData, $structDataTypeCollection);
        }
        if (is_string($valueData) === true) {
            return self::_findString($valueData, $structDataTypeCollection);
        }
        if (is_array($valueData) === true) {
            return self::_findArray($valueData, $structDataTypeCollection);
        }
        return null;
    }

    protected static function _findInt(mixed $rawDataValue, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        if ($structDataTypeCollection->unclearInt === true) {
            $structValueType = new StructValueType(
                StructUnderlyingDataType::Integer,
                null,
                $rawDataValue,
                null,
            );
            return $structValueType;
        }
        $structValueType = self::_findMatchingDataType(
            $rawDataValue,
            $structDataTypeCollection,
            [
                StructUnderlyingDataType::EnumInt,
            ],
            StructUnderlyingDataType::Integer
        );
        return $structValueType;
    }

    protected static function _findString(string $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        if ($structDataTypeCollection->unclearString === true) {
            $structValueType = new StructValueType(
                StructUnderlyingDataType::String,
                null,
                $valueData,
                new Value($valueData),
            );
            return $structValueType;
        }
        $structValueType = self::_findMatchingDataType(
            $valueData,
            $structDataTypeCollection,
            [
                StructUnderlyingDataType::Enum,
                StructUnderlyingDataType::EnumString,
                StructUnderlyingDataType::DateTime,
                StructUnderlyingDataType::DataType,
            ],
            StructUnderlyingDataType::String
        );
        return $structValueType;
    }

    /**
     * @param array<mixed> $rawDataValue
     */
    protected static function _findArray(array $rawDataValue, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        if (array_is_list($rawDataValue) === true) {
            $structValueType = new StructValueType(
                StructUnderlyingDataType::ArrayList,
                null,
                $rawDataValue,
                null
            );
            return $structValueType;
        }
        $structValueType = self::_findMatchingDataType(
            $rawDataValue,
            $structDataTypeCollection,
            [
                StructUnderlyingDataType::Struct,
            ],
            StructUnderlyingDataType::Array
        );
        return $structValueType;
    }

    /**
     * @param array<StructUnderlyingDataType> $structUnderlyingDataTypes
     */
    protected static function _findMatchingDataType(mixed $rawDataValue, StructDataTypeCollection $structDataTypeCollection, array $structUnderlyingDataTypes, StructUnderlyingDataType $defaultStructUnderlyingDataType): StructValueType
    {
        $structValueType = null;
        foreach ($structDataTypeCollection->structDataTypes as $structDataType) {
            $structUnderlyingDataType = $structDataType->structUnderlyingDataType;
            if (in_array($structUnderlyingDataType, $structUnderlyingDataTypes, true) === false) {
                continue;
            }
            $structValueType = new StructValueType(
                $structUnderlyingDataType,
                $structDataType->className,
                $rawDataValue,
                null,
            );
            break;
        }
        if ($structValueType !== null) {
            return $structValueType;
        }
        $value = null;
        if (is_array($rawDataValue) === false) {
            $value = new Value($rawDataValue);
        }
        $structValueType = new StructValueType(
            $defaultStructUnderlyingDataType,
            null,
            $rawDataValue,
            $value,
        );
        return $structValueType;
    }

    protected static function _findStructType(mixed $rawDataValue): ?StructValueType
    {
        if(is_object($rawDataValue) === false) {
            return null;
        }

        $structUnderlyingDataType = null;
        $className = null;
        $value = null;

        if (is_a($rawDataValue, StructInterface::class) === true) {
            $structUnderlyingDataType = StructUnderlyingDataType::Struct;
            $className = $rawDataValue::class;
            $value = new Value($rawDataValue);
        }
        if (is_a($rawDataValue, DataTypeInterface::class) === true) {
            $structUnderlyingDataType = StructUnderlyingDataType::DataType;
            $className = $rawDataValue::class;
            $value = new Value($rawDataValue);
        }
        if (is_a($rawDataValue, \DateTimeInterface::class) === true) {
            $structUnderlyingDataType = StructUnderlyingDataType::DateTime;
            $value = new Value($rawDataValue);
        }
        if (is_a($rawDataValue, \UnitEnum::class) === true) {
            $structUnderlyingDataType = EnumHelper::findStructUnderlyingDataType($rawDataValue);
            $className = $rawDataValue::class;
            $value = new Value($rawDataValue);
        }
        if ($structUnderlyingDataType === null) {
            return null;
        }
        $structValueType = new StructValueType(
            $structUnderlyingDataType,
            $className,
            $rawDataValue,
            $value,
        );
        return $structValueType;
    }

    public static function _findDefinedType(mixed $rawDataValue): ?StructValueType
    {
        if (is_array($rawDataValue) === false) {
            return null;
        }
        if (count($rawDataValue) !== 2) {
            return null;
        }
        if (array_key_exists('structType', $rawDataValue) === false) {
            return null;
        }
        if (array_key_exists('value', $rawDataValue) === false) {
            return null;
        }
        $structType = $rawDataValue['structType'];
        $value = $rawDataValue['value'];
        if (is_string($structType) === false) {
            return null;
        }
        $structUnderlyingDataType = null;
        $className = null;
        if (
            is_a($structType, \DateTimeInterface::class, true) === true &&
            is_string($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::DateTime;
        }
        if (
            is_a($structType, DataTypeInterface::class, true) === true &&
            is_string($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::DataType;
            $className = $structType;
        }
        if (is_a($structType, \UnitEnum::class, true) === true) {
            $structUnderlyingDataType = EnumHelper::findStructUnderlyingDataType($structType);
            $className = $structType;
        }
        if (
            is_a($structType, StructInterface::class, true) === true &&
            is_array($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::Struct;
            $className = $structType;
        }
        if ($structUnderlyingDataType === null) {
            return null;
        }
        $structValueType = new StructValueType(
            $structUnderlyingDataType,
            $className,
            $value,
            null,
        );
        return $structValueType;
    }

    /**
     * @param array<mixed>|object|null $data
     */
    public static function findValue(null|array|object $data, string $key): ?Value
    {
        if ($data === null) {
            return null;
        }
        if (is_array($data) === true) {
            if (array_key_exists($key, $data) === false) {
                return null;
            }
            $value = $data[$key];
            return new Value($value);
        }
        if (property_exists($data, $key) === true) {
            try {
                $value = $data->{$key}; // @phpstan-ignore property.dynamicName
                return new Value($value);
            } catch (\Throwable) {
            }
        }
        $getterName = 'get' . ucfirst($key);
        if (method_exists($data, $getterName) === true) {
            try {
                $value = $data->$getterName(); // @phpstan-ignore method.dynamicName
                return new Value($value);
            } catch (\Throwable) {
            }
        }
        return null;
    }
}
