<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use BackedEnum;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingArrayType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElementArray;
use Struct\Struct\StructReflectionUtility;
use function array_is_list;
use DateTimeInterface;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Struct\Enum\KeyConvert;
use UnitEnum;

/**
 * @internal
 */
class SerializeUtility
{
    /**
     * @return array<mixed>
     */
    public function serializeStruct(StructInterface $structure, ?KeyConvert $keyConvert): array
    {
        $serializedData = $this->_serializeStruct($structure, $keyConvert);
        return $serializedData;
    }

    /**
     * @return array<mixed>
     */
    public function _serializeStruct(StructInterface $struct, ?KeyConvert $keyConvert = null): array
    {
        $serializedData = [];
        $structSignature = StructReflectionUtility::readSignature($struct);
        foreach ($structSignature->structElements as $structElement) {
            $propertyName = $structElement->name;
            $value = $struct->$propertyName; // @phpstan-ignore-line
            $formattedValue = $this->formatValue($structElement->structDataTypeCollection, $value, $structElement->structElementArray);
            $serializedData[$propertyName] = $formattedValue;
        }

        return $serializedData;
    }

    protected function formatValue(StructDataTypeCollection $structDataTypeCollection, mixed $value, ?StructElementArray $structElementArray = null): mixed
    {
        if($value === null) {
            return null;
        }
        $structUnderlyingDataType = StructDataTypeHelper::findUnderlyingDataTypeFromValue($value);
        $formattedValue = match ($structUnderlyingDataType) {
            StructUnderlyingDataType::Boolean,
            StructUnderlyingDataType::Integer,
            StructUnderlyingDataType::Float,
            StructUnderlyingDataType::String => $value,
            StructUnderlyingDataType::Enum,
            StructUnderlyingDataType::EnumString,
            StructUnderlyingDataType::EnumInt => $this->formatEnum($structDataTypeCollection, $value),
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::ArrayList =>$this->formatArray($structDataTypeCollection, $value, $structElementArray),
            StructUnderlyingDataType::DateTime => $this->formatDateTime($structDataTypeCollection, $value),
            StructUnderlyingDataType::DataType => $this->formatDataType($structDataTypeCollection, $value),
            StructUnderlyingDataType::Struct => $this->formatStruct($structDataTypeCollection, $value),
        };
        return $formattedValue;
    }

    protected function formatArray(StructDataTypeCollection $structDataTypeCollection, array $value, StructElementArray $structElementArray): array
    {
        if($structElementArray->structUnderlyingArrayType === StructUnderlyingArrayType::ArrayPassThrough) {
            return $value;
        }
        $output = [];
        $isList = array_is_list($value);
        foreach ($value as $key => $item) {
            $formattedValue = $this->formatValue($structElementArray->structDataTypeCollection, $item);
            if($isList === true) {
                $output[] = $formattedValue;
            } else {
                $output[$key] = $formattedValue;
            }
        }
        return $output;
    }

    protected function formatStruct(StructDataTypeCollection $structDataTypeCollection, StructInterface $value): array
    {
        $formattedValue = $this->_serializeStruct($value);
        $formattedValue = $this->formatUnclear($structDataTypeCollection, $formattedValue, $value::class);
        return $formattedValue;
    }

    protected function formatDataType(StructDataTypeCollection $structDataTypeCollection, DataTypeInterface $value): string|array
    {
        $formattedValue = $value->serializeToString();
        $formattedValue = $this->formatUnclear($structDataTypeCollection, $formattedValue, $value::class);
        return $formattedValue;
    }

    protected function formatEnum(StructDataTypeCollection $structDataTypeCollection, UnitEnum $value): string|int|array
    {
        $formattedValue = $value->name;
        if ($value instanceof BackedEnum) {
            $formattedValue = $value->value;
        }
        $formattedValue = $this->formatUnclear($structDataTypeCollection, $formattedValue, UnitEnum::class);
        return $formattedValue;
    }

    protected function formatDateTime(StructDataTypeCollection $structDataTypeCollection, DateTimeInterface $value): string|array
    {
        $formattedValue = $value->format('c');
        $formattedValue = $this->formatUnclear($structDataTypeCollection, $formattedValue, DateTimeInterface::class);
        return $formattedValue;
    }

    protected function formatUnclear(StructDataTypeCollection $structDataTypeCollection, string|int|array $value, string $className): string|int|array
    {
        if(is_string($value) === true) {
            $formattedValue = $this->formatUnclearType($structDataTypeCollection, $value, $className, 'unclearString');
            return $formattedValue;
        }
        if(is_int($value) === true) {
            $formattedValue = $this->formatUnclearType($structDataTypeCollection, $value, $className, 'unclearInt');
            return $formattedValue;
        }
        $formattedValue = $this->formatUnclearType($structDataTypeCollection, $value, $className, 'unclearArray');
        return $formattedValue;
    }

    protected function formatUnclearType(StructDataTypeCollection $structDataTypeCollection, string|int|array $value, string $className, string $type): string|int|array
    {
        if($structDataTypeCollection->$type === false) {
            return $value;
        }
        return [
            'structType' => $className,
            'value' => $value,
        ];
    }
}
