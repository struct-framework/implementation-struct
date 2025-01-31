<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;


use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Utility\ValueUtility;
use Struct\Struct\StructReflectionUtility;

class StructFactory
{
    /**
     * @template T of StructInterface
     * @param  class-string<T> $structName
     * @return T
     */
    public static function create(string $structName, null|array|object $data = null): StructInterface
    {
        $structSignature = StructReflectionUtility::readSignature($structName);

        try {
            $values = self::_createValues($structSignature, $data);
        } catch (InvalidValueException $invalidValueException) {
            throw new InvalidValueException($invalidValueException, '<' . $structName . '>');
        }
        if ($structSignature->isReadOnly === true) {
            $valueDataArray = self::_buildValueDataArray($values);
            $struct = new $structName(...$valueDataArray);
        } else {
            $struct = new $structName();
            self::_assignValues($struct, $values);
        }
        return $struct;
    }

    /**
     * @param array<Value> $values
     * @return array<mixed>
     */
    protected static function _buildValueDataArray(array $values): array
    {
        $valueDataArray = [];
        foreach ($values as $value) {
            if($value === null) {
                $valueDataArray[] = null;
                continue;
            }
            $valueDataArray[] = $value->valueData;
        }
        return $valueDataArray;
    }


    protected static function _assignValues(StructInterface &$struct, mixed $values): void
    {
        foreach ($values as $propertyName => $value) {
            self::_assignValue($struct, $propertyName, $value);
        }
    }

    protected static function _assignValue(StructInterface &$struct, string $propertyName, ?Value $value): void
    {
        if ($value === null) {
            return;
        }
        try {
            $struct->$propertyName = $value->valueData;
        } catch (\Throwable $exception) {
            throw new InvalidValueException($exception);
        }
    }

    /**
     * @return array<null|Value>
     */
    protected static function _createValues(StructSignature $structSignature, null|array|object $data): array
    {
        $values = [];
        foreach ($structSignature->structElements as $structElement) {
            $propertyName = $structElement->name;
            try {
                $values[$propertyName] = self::buildValue($structElement, $data);
            } catch (InvalidValueException $invalidValueException) {
                throw new InvalidValueException($invalidValueException, ':' . $propertyName);
            }
        }
        return $values;
    }


    protected static function buildValue(StructElement $structElement, null|array|object $data): ?Value
    {
        $structDataType = $structElement->structDataTypes[0];
        $valueFromData = self::_findValueFromData($structElement, $data);
        if($valueFromData !== null) {
            return $valueFromData;
        }
        $value = match ($structDataType->structBaseDataType) {
            StructBaseDataType::Boolean,
            StructBaseDataType::Integer,
            StructBaseDataType::Float,
            StructBaseDataType::String,
            StructBaseDataType::Enum,
            StructBaseDataType::DateTime,
            StructBaseDataType::DataType => $structElement->defaultValue,
            StructBaseDataType::Array => self::_buildValueArray($structElement),
            StructBaseDataType::Struct => self::_buildValueStruct($structElement, $data),
        };
        return $value;
    }


    protected static function _findValueFromData(StructElement $structElement, null|array|object $data): ?Value
    {
        $name = $structElement->name;
        $valueData = null;
        if(is_array($data) === true) {
            if(array_key_exists($name, $data) === true) {
                $valueData = $data[$name];
            }
        }
        $value = null;
        if($valueData !== null) {
            $value = new Value($valueData);
        }
        $valueFromData = ValueUtility::processValue($structElement->structDataTypes, $value);
        return $valueFromData;
    }


    protected static function _buildValueArray(StructElement $structElement): Value
    {
        if ($structElement->isAllowsNull === true) {
            return new Value(null);
        }
        return new Value([]);
    }

    protected static function _buildValueStruct(StructElement $structElement, null|array|object $data): Value
    {
        $className = $structElement->structDataTypes[0]->className;
        $struct =  self::create($className, $data);
        return new Value($struct);
    }

}
