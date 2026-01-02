<?php

declare(strict_types=1);

namespace Struct\Struct;

use function array_is_list;
use BackedEnum;
use DateTime;
use Exception\Unexpected\UnexpectedException;
use function gettype;
use ReflectionClass;
use ReflectionException;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Struct\Enum\HashAlgorithm;
use Struct\Struct\Private\Enum\DataType;
use UnitEnum;
use DataType\Contracts\DataTypeInterface as NewDataTypeInterface;

class StructHashUtility
{
    public static function buildHash(StructInterface|StructCollectionInterface $struct, HashAlgorithm $algorithm = HashAlgorithm::SHA2): string
    {
        if ($struct instanceof StructCollectionInterface) {
            return self::buildHashFromStructCollection($struct, $algorithm);
        }
        return self::buildHashFromStruct($struct, $algorithm);
    }

    protected static function buildHashFromStruct(StructInterface $struct, HashAlgorithm $algorithm): string
    {
        $data = hash($algorithm->value, $struct::class, true);
        $propertyNames = self::readPropertyNames($struct);

        foreach ($propertyNames as $propertyName) {
            $value = $struct->$propertyName; // @phpstan-ignore-line propertyName is from reflection
            $data .= hash($algorithm->value, $propertyName, true);
            $data .= self::buildHashFromValue($value, $algorithm);
        }

        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    protected static function buildHashFromStructCollection(StructCollectionInterface $structCollection, HashAlgorithm $algorithm): string
    {
        $data = hash($algorithm->value, $structCollection::class, true);
        foreach ($structCollection as $struct) {
            $data .= self::buildHashFromStruct($struct, $algorithm);
        }
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    protected static function buildHashFromValue(mixed $value, HashAlgorithm $algorithm): string
    {
        $dataType = self::findDataType($value);
        $data = match ($dataType) {
            DataType::NULL             => '4237d4b9-00b6-4ebd-b482-e77551cd1620',
            DataType::Struct           => self::buildHashFromStruct($value, $algorithm), // @phpstan-ignore-line
            DataType::StructCollection => self::buildHashFromStructCollection($value, $algorithm), // @phpstan-ignore-line
            DataType::DateTime         => self::buildHashFromDateTime($value, $algorithm), // @phpstan-ignore-line
            DataType::Enum             => self::buildHashFromEnum($value, $algorithm), // @phpstan-ignore-line
            DataType::DataType         => self::buildHashFromDataType($value, $algorithm), // @phpstan-ignore-line
            DataType::Array            => self::buildHashFromArray($value, $algorithm), // @phpstan-ignore-line
            DataType::Boolean,
            DataType::Integer,
            DataType::Double,
            DataType::String           => self::buildHashFromDefault($value, $algorithm), // @phpstan-ignore-line
        };
        $hash = hash($algorithm->value, $dataType->value . $data, true);
        return $hash;
    }

    protected static function buildHashFromDefault(bool|int|float|string $value, HashAlgorithm $algorithm): string
    {
        $data = (string) $value;
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    protected static function buildHashFromDataType(DataTypeInterface|NewDataTypeInterface $value, HashAlgorithm $algorithm): string
    {
        $data = hash($algorithm->value, $value::class, true);
        $data .= $value->serializeToString();
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    protected static function buildHashFromDateTime(DateTime $value, HashAlgorithm $algorithm): string
    {
        $data = $value->format('c');
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    protected static function buildHashFromEnum(UnitEnum $value, HashAlgorithm $algorithm): string
    {
        $data = hash($algorithm->value, $value::class, true);
        $data .= $value->name;
        if ($value instanceof BackedEnum) {
            $data = (string) $value->value;
        }
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    /**
     * @param array<mixed> $values
     */
    protected static function buildHashFromArray(array $values, HashAlgorithm $algorithm): string
    {
        $data = '';
        $list = array_is_list($values);

        foreach ($values as $key => $value) {
            $valueHash = self::buildHashFromValue($value, $algorithm);
            if ($list === false) {
                $keyHash   = hash($algorithm->value, (string) $key, true);
                $valueHash = hash($algorithm->value, $keyHash . $valueHash, true);
            }
            $data .= $valueHash;
        }
        $hash = hash($algorithm->value, $data, true);
        return $hash;
    }

    /**
     * @return string[]
     */
    protected static function readPropertyNames(StructInterface $struct): array
    {
        $propertyNames = [];
        try {
            $reflection = new ReflectionClass($struct);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(1651559371, $exception);
        }
        $reflectionProperties = $reflection->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            if ($reflectionProperty->isPublic() === false) {
                throw new InvalidStructException('The property <' . $propertyName . '> must be public', 1651559697);
            }
            $propertyNames[] = $propertyName;
        }
        return $propertyNames;
    }

    protected static function findDataType(mixed $value): DataType
    {
        $type = gettype($value);
        if ($value === null) {
            return DataType::NULL;
        }
        if ($value instanceof StructInterface) {
            return DataType::Struct;
        }
        if ($value instanceof StructCollectionInterface) {
            return DataType::StructCollection;
        }
        if ($value instanceof DateTime) {
            return DataType::DateTime;
        }
        if ($value instanceof UnitEnum) {
            return DataType::Enum;
        }
        if ($value instanceof DataTypeInterface) {
            return DataType::DataType;
        }
        if ($value instanceof NewDataTypeInterface) {
            return DataType::DataType;
        }
        if ($type === 'array') {
            return DataType::Array;
        }
        if ($type === 'boolean') {
            return DataType::Boolean;
        }
        if ($type === 'integer') {
            return DataType::Integer;
        }
        if ($type === 'double') {
            return DataType::Double;
        }
        if ($type === 'string') {
            return DataType::String;
        }
        throw new UnexpectedException(1701724351);
    }
}
