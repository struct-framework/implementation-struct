<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use ReflectionUtility;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;

class StructSignatureUtility
{
    /**
     * @param StructInterface|class-string<object> $struct
     * @return array<string>
     */
    public static function readPropertySignature(StructInterface|string $struct, bool $withType = false): array
    {
        $signature = ReflectionUtility::readObjectSignature($struct);
        $propertyStrings = [];
        foreach ($signature->properties as $property) {
            $propertyString = $property->parameter->name;
            if ($withType === true) {
                $propertyString .= ':' . self::_readType($property);
            }
            $propertyStrings[] = $propertyString;
        }
        sort($propertyStrings);
        return $propertyStrings;
    }

    /**
     * @param StructInterface|class-string<object> $struct
     */
    public static function readPropertySignatureHash(StructInterface|string $struct, bool $withType = false): string
    {
        $propertyStrings = self::readPropertySignature($struct, $withType);
        $hash = sha1(implode(',', $propertyStrings));
        return $hash;
    }

    /**
     * @param array<string> $propertyValueStrings
     */
    protected static function buildValues(array &$propertyValueStrings, mixed $propertyValue, string $prefix): void
    {
        if ($propertyValue instanceof StructInterface === true) {
            self::buildValueStruct($propertyValueStrings, $propertyValue, $prefix);
            return;
        }
        if (is_array($propertyValue) === true) {
            self::buildValueArray($propertyValueStrings, $propertyValue, $prefix . '_array');
            return;
        }
        $structDataType = self::findDataType($propertyValue);
        $data = match ($structDataType) {
            StructBaseDataType::NULL     => 'null',
            StructBaseDataType::DateTime => self::buildValueFromDateTime($propertyValue), // @phpstan-ignore-line
            StructBaseDataType::Enum     => self::buildValueFromEnum($propertyValue), // @phpstan-ignore-line
            StructBaseDataType::DataType => self::buildValueFromDataType($propertyValue), // @phpstan-ignore-line
            StructBaseDataType::Boolean  => self::buildValueBoolean($propertyValue), // @phpstan-ignore-line

            StructBaseDataType::Integer,
            StructBaseDataType::Float,
            StructBaseDataType::String   => (string) $propertyValue, // @phpstan-ignore-line

            StructBaseDataType::Array,
            StructBaseDataType::Struct => throw new UnexpectedException(1737888077),
        };
        $propertyValueStrings[] = $prefix . '_' . $structDataType->value . ':' . self::encode($data);
    }

    protected static function encode(string $value): string
    {
        $value = str_replace('%', '%25', $value);
        $value = str_replace(':', '%3A', $value);
        $value = str_replace(';', '%3B', $value);
        return $value;
    }

    /**
     * @param array<string> $propertyValueStrings
     */
    protected static function buildValueStruct(array &$propertyValueStrings, StructInterface $struct, string $prefix): void
    {
        $propertyStrings = self::readPropertySignature($struct);
        foreach ($propertyStrings as $propertyString) {
            $propertyValue = $struct->{$propertyString};
            self::buildValues($propertyValueStrings, $propertyValue, $prefix . '_Struct');
        }
    }

    /**
     * @param array<string> $propertyValueStrings
     */
    protected static function buildValueArray(array &$propertyValueStrings, array $propertyValue, string $prefix): void
    {
        foreach ($propertyValue as $key => $subPropertyValue) {
            self::buildValues($propertyValueStrings, $subPropertyValue, $prefix . '_' . $key);
        }
    }

    protected static function buildValueBoolean(bool $value): string
    {
        if ($value === true) {
            return 'true';
        }
        return 'false';
    }

    protected static function buildValueFromDataType(DataTypeInterface $value): string
    {
        $data = $value->serializeToString();
        return $data;
    }

    protected static function buildValueFromDateTime(\DateTimeInterface $value): string
    {
        $data = $value->format('c');
        return $data;
    }

    protected static function buildValueFromEnum(\UnitEnum $value): string
    {
        $data = $value->name;
        if ($value instanceof \BackedEnum) {
            $data = (string) $value->value;
        }
        return $data;
    }

    protected static function findDataType(mixed $value): StructBaseDataType
    {
        $type = gettype($value);
        if ($value === null) {
            return StructBaseDataType::NULL;
        }
        if ($value instanceof \DateTimeInterface) {
            return StructBaseDataType::DateTime;
        }
        if ($value instanceof \UnitEnum) {
            return StructBaseDataType::Enum;
        }
        if ($value instanceof DataTypeInterface) {
            return StructBaseDataType::DataType;
        }
        if ($type === 'boolean') {
            return StructBaseDataType::Boolean;
        }
        if ($type === 'integer') {
            return StructBaseDataType::Integer;
        }
        if ($type === 'double') {
            return StructBaseDataType::Float;
        }
        if ($type === 'string') {
            return StructBaseDataType::String;
        }
        throw new UnexpectedException(1701724351);
    }

    protected static function _readType(Property $property): string
    {
        $propertyType = $property->parameter->types[0]->dataType;
        if ($propertyType !== 'array') {
            return $propertyType;
        }
        $propertyType .= '<';
        $attributes = $property->parameter->attributes;
        foreach ($attributes as $attribute) {
            if ($attribute->name === ArrayList::class) {
                $propertyType .= $attribute->arguments[0];
            }
            if ($attribute->name === ArrayKeyList::class) {
                $propertyType .= 'string,' . $attribute->arguments[0];
            }
        }
        $propertyType .= '>';
        return $propertyType;
    }
}
