<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use function array_is_list;
use DateTimeInterface;

use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Struct\Internal\Helper\FormatHelper;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingArrayType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElementArray;
use Struct\Struct\StructReflectionUtility;
use UnitEnum;

/**
 * @internal
 */
class SerializeUtility
{
    /**
     * @return array<mixed>
     */
    public static function serializeStruct(StructInterface $struct): array
    {
        $serializedData = [];
        $structSignature = StructReflectionUtility::readSignature($struct);
        foreach ($structSignature->structElements as $structElement) {
            $propertyName = $structElement->name;
            $value = $struct->$propertyName; // @phpstan-ignore-line
            $formattedValue = self::formatValue($structElement->structDataTypeCollection, $value, $structElement->structElementArray);
            $serializedData[$propertyName] = $formattedValue;
        }

        return $serializedData;
    }

    protected static function formatValue(StructDataTypeCollection $structDataTypeCollection, mixed $value, ?StructElementArray $structElementArray = null): mixed
    {
        if ($value === null) {
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
            StructUnderlyingDataType::EnumInt => static::formatEnum($structDataTypeCollection, $value),
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::ArrayList => static::formatArray($structDataTypeCollection, $value, $structElementArray),
            StructUnderlyingDataType::DateTime => static::formatDateTime($structDataTypeCollection, $value),
            StructUnderlyingDataType::DataType => static::formatDataType($structDataTypeCollection, $value),
            StructUnderlyingDataType::Struct => static::formatStruct($structDataTypeCollection, $value),
        };
        return $formattedValue;
    }

    protected static function formatStruct(StructDataTypeCollection $structDataTypeCollection, StructInterface $value): array
    {
        $formattedValue = self::serializeStruct($value);
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, $value::class);
        return $formattedValue;
    }

    protected static function formatArray(StructDataTypeCollection $structDataTypeCollection, array $value, StructElementArray $structElementArray): array
    {
        if ($structElementArray->structUnderlyingArrayType === StructUnderlyingArrayType::ArrayPassThrough) {
            return $value;
        }
        $output = [];
        $isList = array_is_list($value);
        foreach ($value as $key => $item) {
            $formattedValue = self::formatValue($structElementArray->structDataTypeCollection, $item);
            if ($isList === true) {
                $output[] = $formattedValue;
            } else {
                $output[$key] = $formattedValue;
            }
        }
        return $output;
    }

    protected static function formatDataType(StructDataTypeCollection $structDataTypeCollection, DataTypeInterface $value): string|array
    {
        $formattedValue = FormatHelper::formatDataType($value);
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, $value::class);
        return $formattedValue;
    }

    protected static function formatEnum(StructDataTypeCollection $structDataTypeCollection, UnitEnum $value): string|int|array
    {
        $formattedValue = FormatHelper::formatEnum($value);
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, UnitEnum::class);
        return $formattedValue;
    }

    protected static function formatDateTime(StructDataTypeCollection $structDataTypeCollection, DateTimeInterface $value): string|array
    {
        $formattedValue = FormatHelper::formatDateTime($value);
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, DateTimeInterface::class);
        return $formattedValue;
    }

    protected static function formatUnclear(StructDataTypeCollection $structDataTypeCollection, string|int|array $value, string $className): string|int|array
    {
        if (is_string($value) === true) {
            $formattedValue = self::formatUnclearType($structDataTypeCollection, $value, $className, 'unclearString');
            return $formattedValue;
        }
        if (is_int($value) === true) {
            $formattedValue = self::formatUnclearType($structDataTypeCollection, $value, $className, 'unclearInt');
            return $formattedValue;
        }
        $formattedValue = self::formatUnclearType($structDataTypeCollection, $value, $className, 'unclearArray');
        return $formattedValue;
    }

    protected static function formatUnclearType(StructDataTypeCollection $structDataTypeCollection, string|int|array $value, string $className, string $type): string|int|array
    {
        if ($structDataTypeCollection->$type === false) {
            return $value;
        }
        return [
            'structType' => $className,
            'value' => $value,
        ];
    }
}
