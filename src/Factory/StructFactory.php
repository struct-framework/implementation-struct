<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Internal\Helper\FormatHelper;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingArrayType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructValueType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Struct\StructSignature\StructElementArray;
use Struct\Struct\Internal\Utility\DeserializationUtility;
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
            if ($value === null) {
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
                $values[$propertyName] = self::_createValue($structElement, $data);
            } catch (InvalidValueException $invalidValueException) {
                throw new InvalidValueException($invalidValueException, ':' . $propertyName);
            }
        }
        return $values;
    }

    protected static function _createValue(StructElement $structElement, null|array|object $data): ?Value
    {
        $value = self::_processValue($structElement, $data);
        if ($value !== null) {
            return $value;
        }
        foreach ($structElement->structDataTypeCollection->structDataTypes as $structDataType) {
            if ($structDataType->structUnderlyingDataType === StructUnderlyingDataType::Struct) {
                $struct = self::create($structDataType->className);
                return new Value($struct);
            }
        }
        return null;
    }

    protected static function _processValue(StructElement $structElement, null|array|object $data): ?Value
    {
        $dataValue = DeserializationUtility::findValue($data, $structElement->name);
        if ($dataValue === null) {
            if ($structElement->defaultValue !== null) {
                return  $structElement->defaultValue;
            }
            if ($structElement->isAllowsNull === true) {
                return new Value(null);
            }
            return null;
        }
        $processedValue = DeserializationUtility::processValue($dataValue->valueData, $structElement->structDataTypeCollection);
        $value = self::_postProcessValue($processedValue, $structElement->structElementArray);
        return $value;
    }

    protected static function _postProcessValue(?StructValueType $processedValue, ?StructElementArray $structElementArray): ?Value
    {
        if ($processedValue === null) {
            return null;
        }
        if (
            $processedValue->structUnderlyingDataType === StructUnderlyingDataType::Array ||
            $processedValue->structUnderlyingDataType === StructUnderlyingDataType::ArrayList
        ) {
            $array = self::_processArray($processedValue, $structElementArray);
            return new Value($array);
        }
        if ($processedValue->structUnderlyingDataType === StructUnderlyingDataType::Struct) {
            $struct = self::create($processedValue->className, $processedValue->rawDataValue);
            return new Value($struct);
        }
        $value = FormatHelper::buildValue($processedValue);
        return $value;
    }

    protected static function _processArray(StructValueType $structValueType, StructElementArray $structElementArray): ?array
    {
        if ($structElementArray->structUnderlyingArrayType === StructUnderlyingArrayType::ArrayPassThrough) {
            if (is_array($structValueType->rawDataValue) === true) {
                return $structValueType->rawDataValue;
            }
            return null;
        }
        $processedArray = [];
        foreach ($structValueType->rawDataValue as $key => $value) {
            $processedValue =  DeserializationUtility::processValue($value, $structElementArray->structDataTypeCollection);
            $postProcessedValue = self::_postProcessValue($processedValue, null);
            if ($structValueType->structUnderlyingDataType === StructUnderlyingDataType::ArrayList) {
                $processedArray[] = $postProcessedValue->valueData;
            } else {
                $processedArray[$key] = $postProcessedValue->valueData;
            }
        }
        return $processedArray;
    }
}
