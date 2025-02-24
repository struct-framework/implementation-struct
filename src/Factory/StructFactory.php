<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Exception\Unexpected\UnexpectedException;
use Struct\Contracts\StructInterface;
use Struct\Exception\DeserializeException;
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
     * @param array<mixed>|object|null $data
     * @param  class-string<T> $structName
     * @return T
     */
    public static function create(string $structName, null|array|object $data = null): StructInterface
    {
        $structSignature = StructReflectionUtility::readSignature($structName);
        try {
            $values = self::_createValues($structSignature, $data);
        } catch (InvalidValueException $invalidValueException) {
            throw new DeserializeException(1740340189, '<' . $structName . '>');
        }
        if ($structSignature->isReadOnly === true) {
            $valueDataArray = self::_buildValueDataArray($values);
            $struct = new $structName(...$valueDataArray);
            return $struct;
        }
        $struct = new $structName();
        self::_assignValues($struct, $values);
        return $struct; // @phpstan-ignore return.type
    }

    /**
     * @param array<Value|null> $values
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

    /**
     * @param array<null|Value> $values
     */
    protected static function _assignValues(StructInterface &$struct, mixed $values): void
    {
        foreach ($values as $propertyName => $value) {
            if($value === null) {
                continue;
            }
            self::_assignValue($struct, $propertyName, $value);
        }
    }

    protected static function _assignValue(StructInterface &$struct, string $propertyName, ?Value $value): void
    {
        if ($value === null) {
            return;
        }
        try {
            $struct->$propertyName = $value->valueData; // @phpstan-ignore property.dynamicName
        } catch (\Throwable $exception) {
            throw new UnexpectedException(1740338748);
        }
    }

    /**
     * @param array<mixed>|object|null $data
     * @return array<null|Value>
     */
    protected static function _createValues(StructSignature $structSignature, null|array|object $data): array
    {
        $values = [];
        foreach ($structSignature->structElements as $structElement) {
            $propertyName = $structElement->name;
            $values[$propertyName] = self::_createValue($structElement, $data);
        }
        return $values;
    }

    /**
     * @param array<mixed>|object|null $data
     */
    protected static function _createValue(StructElement $structElement, null|array|object $data): ?Value
    {
        $value = self::_processValue($structElement, $data);
        if ($value !== null) {
            return $value;
        }
        foreach ($structElement->structDataTypeCollection->structDataTypes as $structDataType) {
            if ($structDataType->structUnderlyingDataType !== StructUnderlyingDataType::Struct) {
                continue;
            }
            if ($structDataType->isAbstract === true) {
                continue;
            }
            /** @var class-string<StructInterface> $className */
            $className = $structDataType->className;
            $struct = self::create($className);
            return new Value($struct);
        }
        return null;
    }

    /**
     * @param array<mixed>|object|null $data
     */
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
            if($structElementArray === null) {
                throw new UnexpectedException(1740338246);
            }
            $array = self::_processArray($processedValue, $structElementArray);
            return new Value($array);
        }
        if (
            $processedValue->structUnderlyingDataType === StructUnderlyingDataType::Struct
        ) {
            /** @var class-string<StructInterface> $className */
            $className = $processedValue->className;
            /** @var array<mixed> $rawDataValue */
            $rawDataValue = $processedValue->rawDataValue;
            $struct = self::create($className, $rawDataValue);
            return new Value($struct);
        }
        $value = FormatHelper::buildValue($processedValue);
        return $value;
    }

    /**
     * @return array<mixed>|null
     */
    protected static function _processArray(StructValueType $structValueType, StructElementArray $structElementArray): ?array
    {
        if ($structElementArray->structUnderlyingArrayType === StructUnderlyingArrayType::ArrayPassThrough) {
            if (is_array($structValueType->rawDataValue) === true) {
                return $structValueType->rawDataValue;
            }
            return null;
        }
        $processedArray = [];
        if(is_array($structValueType->rawDataValue) === false) {
            throw new UnexpectedException(1740337979);
        }
        foreach ($structValueType->rawDataValue as $key => $value) {
            if($structElementArray->structDataTypeCollection === null) {
                throw new UnexpectedException(1740337981);
            }
            $processedValue =  DeserializationUtility::processValue($value, $structElementArray->structDataTypeCollection);
            $postProcessedValue = self::_postProcessValue($processedValue, null);
            if($postProcessedValue === null) {
                throw new UnexpectedException(1740337983);
            }
            if ($structValueType->structUnderlyingDataType === StructUnderlyingDataType::ArrayList) {
                $processedArray[] = $postProcessedValue->valueData;
            } else {
                $processedArray[$key] = $postProcessedValue->valueData;
            }
        }
        return $processedArray;
    }
}
