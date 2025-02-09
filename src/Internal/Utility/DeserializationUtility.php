<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
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

    protected static function _findInt(mixed $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        $structValueType = self::_findMatchingDataType(
            $valueData,
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

    protected static function _findArray(array $valueData, StructDataTypeCollection $structDataTypeCollection): ?StructValueType
    {
        if (array_is_list($valueData) === true) {
            $structValueType = new StructValueType(
                StructUnderlyingDataType::ArrayList,
                null,
                $valueData,
            );
            return $structValueType;
        }
        $structValueType = self::_findMatchingDataType(
            $valueData,
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
    protected static function _findMatchingDataType(mixed $valueData, StructDataTypeCollection $structDataTypeCollection, array $structUnderlyingDataTypes, StructUnderlyingDataType $defaultStructUnderlyingDataType): StructValueType
    {
        $structValueType = null;
        foreach ($structDataTypeCollection->structDataTypes as $structDataType) {
            $structUnderlyingDataType = $structDataType->structUnderlyingDataType;
            if (in_array($structUnderlyingDataType, $structUnderlyingDataTypes) === false) {
                continue;
            }
            $structValueType = new StructValueType(
                $structUnderlyingDataType,
                $structDataType->className,
                $valueData,
            );
            break;
        }
        if ($structValueType !== null) {
            return $structValueType;
        }
        $value = null;
        if (is_array($valueData) === false) {
            $value = new Value($valueData);
        }
        $structValueType = new StructValueType(
            $defaultStructUnderlyingDataType,
            null,
            $valueData,
            $value,
        );
        return $structValueType;
    }

    protected static function _findStructType(mixed $valueData): ?StructValueType
    {
        $structUnderlyingDataType = null;
        $className = null;
        $value = null;

        if (is_a($valueData, StructInterface::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::Struct;
            $className = $valueData::class;
            $value = new Value($valueData);
        }
        if (is_a($valueData, DataTypeInterface::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::DataType;
            $className = $valueData::class;
            $value = new Value($valueData);
        }
        if (is_a($valueData, \DateTimeInterface::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::DateTime;
            $value = new Value($valueData);
        }
        if (is_a($valueData, \IntBackedEnum::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::EnumInt;
            $className = $valueData::class;
            $value = new Value($valueData);
        }
        if (is_a($valueData, \StringBackedEnum::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::EnumString;
            $className = $valueData::class;
            $value = new Value($valueData);
        }
        if (is_a($valueData, \UnitEnum::class)) {
            $structUnderlyingDataType = StructUnderlyingDataType::Enum;
            $className = $valueData::class;
            $value = new Value($valueData);
        }
        if ($structUnderlyingDataType === null) {
            return null;
        }
        $structValueType = new StructValueType(
            $structUnderlyingDataType,
            $className,
            $valueData,
            $value,
        );
        return $structValueType;
    }

    public static function _findDefinedType(mixed $valueData): ?StructValueType
    {
        if (is_array($valueData) === false) {
            return null;
        }
        if (count($valueData) !== 2) {
            return null;
        }
        if (array_key_exists('structType', $valueData) === false) {
            return null;
        }
        if (array_key_exists('value', $valueData) === false) {
            return null;
        }
        $structType = $valueData['structType'];
        $value = $valueData['value'];

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

        if (
            is_a($structType, \IntBackedEnum::class, true) === true &&
            is_int($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::EnumInt;
            $className = $structType;
        }
        if (
            is_a($structType, \StringBackedEnum::class, true) === true &&
            is_string($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::EnumString;
            $className = $structType;
        }
        if (
            is_a($structType, \UnitEnum::class, true) === true &&
            is_string($value) === true
        ) {
            $structUnderlyingDataType = StructUnderlyingDataType::Enum;
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
        );
        return $structValueType;
    }

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
                $value = $data->{$key};
                return new Value($value);
            } catch (\Throwable) {
            }
        }
        $getterName = 'get' . ucfirst($key);
        if (method_exists($data, $getterName) === true) {
            try {
                $value = $data->$getterName();
                return new Value($value);
            } catch (\Throwable) {
            }
        }
        return null;
    }
}
