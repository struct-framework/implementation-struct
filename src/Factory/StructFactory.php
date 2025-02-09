<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Internal\Helper\FormatHelper;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Utility\UnserializeUtility;
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
        $dataValue = UnserializeUtility::findValue($data, $structElement->name);
        if ($dataValue === null) {
            if ($structElement->defaultValue !== null) {
                return  $structElement->defaultValue;
            }
            if ($structElement->isAllowsNull === true) {
                return new Value(null);
            }
            return null;
        }
        $processedValue = UnserializeUtility::processValue($dataValue->valueData, $structElement->structDataTypeCollection);
        if ($processedValue === null) {
            return null;
        }
        if ($processedValue->value) {
            return $processedValue->value;
        }
        if ($processedValue->structUnderlyingDataType === StructUnderlyingDataType::Struct) {
            $struct = self::create($processedValue->className, $processedValue->rawDataValue);
            return new Value($struct);
        }
        if ($processedValue->structUnderlyingDataType === StructUnderlyingDataType::Array) {
            return new Value([]);
        }
        if ($processedValue->structUnderlyingDataType === StructUnderlyingDataType::ArrayList) {
            return new Value([]);
        }
        $valueData = FormatHelper::buildStructDataType($processedValue);
        return new Value($valueData);
    }
}
