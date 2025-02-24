<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Exception\Unexpected\UnexpectedException;
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
            StructUnderlyingDataType::EnumInt => static::formatEnum($structDataTypeCollection, $value), // @phpstan-ignore argument.type
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::ArrayList => static::formatArray($structDataTypeCollection, $value, $structElementArray), // @phpstan-ignore-line
            StructUnderlyingDataType::DateTime => static::formatDateTime($structDataTypeCollection, $value), // @phpstan-ignore argument.type
            StructUnderlyingDataType::DataType => static::formatDataType($structDataTypeCollection, $value), // @phpstan-ignore argument.type
            StructUnderlyingDataType::Struct => static::formatStruct($structDataTypeCollection, $value), // @phpstan-ignore argument.type
        };
        return $formattedValue;

    }

    /**
     * @return array{structType:class-string, value:mixed}|array<mixed>
     */
    protected static function formatStruct(StructDataTypeCollection $structDataTypeCollection, StructInterface $value): array
    {
        $serializeStruct = self::serializeStruct($value);
        $formattedValue = self::formatUnclear($structDataTypeCollection, $serializeStruct, $value::class);
        if(is_int($formattedValue) === true) {
            throw new UnexpectedException(1739728561);
        }
        if(is_string($formattedValue) === true) {
            throw new UnexpectedException(1739728564);
        }
        return $formattedValue;
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    protected static function formatArray(StructDataTypeCollection $structDataTypeCollection, array $value, StructElementArray $structElementArray): array
    {
        if ($structElementArray->structUnderlyingArrayType === StructUnderlyingArrayType::ArrayPassThrough) {
            return $value;
        }
        $output = [];
        $isList = array_is_list($value);
        foreach ($value as $key => $item) {
            if($structElementArray->structDataTypeCollection === null) {
                throw new UnexpectedException(1739727396);
            }
            $formattedValue = self::formatValue($structElementArray->structDataTypeCollection, $item);
            if ($isList === true) {
                $output[] = $formattedValue;
            } else {
                $output[$key] = $formattedValue;
            }
        }
        return $output;
    }

    /**
     * @return string|array{structType:class-string, value:mixed}
     */
    protected static function formatDataType(StructDataTypeCollection $structDataTypeCollection, DataTypeInterface $value): string|array
    {
        $formattedValue = FormatHelper::formatDataType($value);
        /** @var string|array{structType:class-string, value:mixed} $formattedValue */
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, $value::class);
        if(is_int($formattedValue) === true) {
            throw new UnexpectedException(1739727515);
        }
        return $formattedValue;
    }

    /**
     * @return int|string|array{structType:class-string, value:mixed}
     */
    protected static function formatEnum(StructDataTypeCollection $structDataTypeCollection, UnitEnum $value): int|string|array
    {
        $formattedValue = FormatHelper::formatEnum($value);
        /** @var int|string|array{structType:class-string, value:mixed} $formattedValue */
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, UnitEnum::class);
        return $formattedValue;
    }

    /**
     * @return string|array{structType:class-string, value:mixed}
     */
    protected static function formatDateTime(StructDataTypeCollection $structDataTypeCollection, DateTimeInterface $value): string|array
    {
        $formattedValue = FormatHelper::formatDateTime($value);
        /** @var string|array{structType:class-string, value:mixed} $formattedValue */
        $formattedValue = self::formatUnclear($structDataTypeCollection, $formattedValue, DateTimeInterface::class);
        if(is_int($formattedValue) === true) {
            throw new UnexpectedException(1739727578);
        }
        return $formattedValue;
    }


    /**
     * @param string|int|array<mixed> $value
     * @return string|int|array{structType:class-string, value:mixed}|array<mixed>
     */
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


    /**
     * @param string|int|array<mixed> $value
     * @return string|int|array{structType:class-string, value:mixed}|array<mixed>
     */
    protected static function formatUnclearType(StructDataTypeCollection $structDataTypeCollection, string|int|array $value, string $className, string $type): string|int|array
    {
        if ($structDataTypeCollection->$type === false) { // @phpstan-ignore property.dynamicName
            return $value;
        }
        return [
            'structType' => $className,
            'value' => $value,
        ];
    }
}
