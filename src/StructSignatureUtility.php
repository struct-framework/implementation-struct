<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Contracts\DataTypeInterfaceWritable;
use Struct\Contracts\StructInterface;
use Struct\Struct\Internal\Struct\ObjectSignature\Property;

class StructSignatureUtility
{
    /**
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

    public static function readValueSignature(StructInterface|string $struct, bool $withType = false): array
    {
        $propertyValueStrings = [];
        self::buildValueStruct($propertyValueStrings, $struct, '');
        return $propertyValueStrings;
    }

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
        $dataType = self::findDataType($propertyValue);
        $data = match ($dataType) {
            'null'              => 'null',
            'DateTimeInterface' => self::buildValueFromDateTime($propertyValue), // @phpstan-ignore-line
            'Enum'              => self::buildValueFromEnum($propertyValue), // @phpstan-ignore-line
            'DataTypeInterface' => self::buildValueFromDataType($propertyValue), // @phpstan-ignore-line
            'boolean'           => self::buildValueBoolean($propertyValue), // @phpstan-ignore-line
            'integer',
            'double',
            'string'            => (string) $propertyValue, // @phpstan-ignore-line
        };
        $propertyValueStrings[] = $prefix . '_' . $dataType . ':' . self::encode($data);
    }

    protected static function encode(string $value): string
    {
        $value = str_replace('%', '%25', $value);
        $value = str_replace(':', '%3A', $value);
        $value = str_replace(';', '%3B', $value);
        return $value;
    }

    protected static function buildValueStruct(array &$propertyValueStrings, StructInterface $struct, string $prefix): void
    {
        $propertyStrings = self::readPropertySignature($struct);
        foreach ($propertyStrings as $propertyString) {
            $propertyValue = $struct->{$propertyString};
            self::buildValues($propertyValueStrings, $propertyValue, $prefix . '_Struct');
        }
    }

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

    protected static function buildValueFromDataType(DataTypeInterfaceWritable $value): string
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

    protected static function findDataType(mixed $value): string
    {
        $type = gettype($value);
        if ($value === null) {
            return 'null';
        }
        if ($value instanceof \DateTimeInterface) {
            return 'DateTimeInterface';
        }
        if ($value instanceof \UnitEnum) {
            return 'Enum';
        }
        if ($value instanceof DataTypeInterfaceWritable) {
            return 'DataTypeInterface';
        }
        if ($type === 'boolean') {
            return 'boolean';
        }
        if ($type === 'integer') {
            return 'integer';
        }
        if ($type === 'double') {
            return 'double';
        }
        if ($type === 'string') {
            return 'string';
        }
        throw new UnexpectedException(1701724351);
    }

    public static function readPropertySignatureHash(StructInterface|string $struct, bool $withType = false): string
    {
        $propertyStrings = self::readPropertySignature($struct, $withType);
        $hash = sha1(implode(',', $propertyStrings));
        return $hash;
    }

    protected static function _readType(Property $property): string
    {
        $propertyType = $property->parameter->types[0]->type;
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
