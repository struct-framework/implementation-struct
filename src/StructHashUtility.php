<?php

declare(strict_types=1);

namespace Struct\Struct;

use function array_is_list;
use BackedEnum;
use DateTime;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use function gettype;
use ReflectionClass;
use ReflectionException;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Struct\Enum\HashAlgorithm;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;
use UnitEnum;

class StructHashUtility
{
    public static function buildHash(StructInterface $struct, HashAlgorithm $algorithm = HashAlgorithm::SHA2): string
    {
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

    protected static function buildHashFromValue(mixed $value, HashAlgorithm $algorithm): string
    {
        $dataType = self::findDataType($value);
        $data = match ($dataType) {
            StructBaseDataType::NULL             => '4237d4b9-00b6-4ebd-b482-e77551cd1620',
            StructBaseDataType::Struct           => self::buildHashFromStruct($value, $algorithm), // @phpstan-ignore-line
            StructBaseDataType::DateTime         => self::buildHashFromDateTime($value, $algorithm), // @phpstan-ignore-line
            StructBaseDataType::Enum             => self::buildHashFromEnum($value, $algorithm), // @phpstan-ignore-line
            StructBaseDataType::DataType         => self::buildHashFromDataType($value, $algorithm), // @phpstan-ignore-line
            StructBaseDataType::Array            => self::buildHashFromArray($value, $algorithm), // @phpstan-ignore-line
            StructBaseDataType::Boolean,
            StructBaseDataType::Integer,
            StructBaseDataType::Double,
            StructBaseDataType::String           => self::buildHashFromDefault($value, $algorithm), // @phpstan-ignore-line
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

    protected static function buildHashFromDataType(DataTypeInterface $value, HashAlgorithm $algorithm): string
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

    protected static function findDataType(mixed $value): StructBaseDataType
    {
        $type = gettype($value);
        if ($value === null) {
            return StructBaseDataType::NULL;
        }
        if ($value instanceof StructInterface) {
            return StructBaseDataType::Struct;
        }
        if ($value instanceof DateTimeInterface) {
            return StructBaseDataType::DateTime;
        }
        if ($value instanceof UnitEnum) {
            return StructBaseDataType::Enum;
        }
        if ($value instanceof DataTypeInterface) {
            return StructBaseDataType::DataType;
        }
        if ($type === 'array') {
            return StructBaseDataType::Array;
        }
        if ($type === 'boolean') {
            return StructBaseDataType::Boolean;
        }
        if ($type === 'integer') {
            return StructBaseDataType::Integer;
        }
        if ($type === 'double') {
            return StructBaseDataType::Double;
        }
        if ($type === 'string') {
            return StructBaseDataType::String;
        }
        throw new UnexpectedException(1701724351);
    }
}
