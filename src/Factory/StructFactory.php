<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\StructReflectionUtility;
use UnitEnum;

class StructFactory
{
    protected const string NO_VALUE = '7874539c-8ea3-47a0-8c6a-7bddb0e03e72';

    /**
     * @template T of StructInterface
     * @param  class-string<T> $structName
     * @return T
     */
    public static function create(string $structName): StructInterface
    {
        $structSignature = StructReflectionUtility::readSignature($structName);

        try {
            $values = self::_createValues($structSignature);
        } catch (InvalidValueException $invalidValueException) {
            throw new InvalidValueException($invalidValueException, '<' . $structName . '>');
        }
        if($structSignature->isReadOnly === true) {
            $struct = new $structName(...$values);
        } else {
            $struct = new $structName();
            self::_assignValues($struct, $values);
        }
        return $struct;
    }

    protected static function _assignValues(StructInterface &$struct, mixed $values): void
    {
        foreach ($values as $propertyName => $value) {
            self::_assignValue($struct, $propertyName, $value);
        }
    }

    protected static function _assignValue(StructInterface &$struct, string $propertyName, mixed $value): void
    {
        if ($value === self::NO_VALUE) {
            return;
        }
        try {
            $struct->$propertyName = $value;
        } catch (\Throwable $exception) {
            throw new InvalidValueException($exception);
        }
    }



    protected static function _createValues(StructSignature $structSignature): array
    {
        $values = [];
        foreach ($structSignature->structElements as $structElement) {
            $propertyName = $structElement->name;
            try {
                $values[$propertyName] = self::buildValue($structElement);
            } catch (InvalidValueException $invalidValueException) {
                throw new InvalidValueException($invalidValueException, ':' . $propertyName);
            }
        }
        return $values;
    }


    protected static function buildValue(StructElement $structElement): mixed
    {
        $structDataType = $structElement->structDataTypes[0];
        $value = match ($structDataType->structBaseDataType) {
            StructBaseDataType::NULL,
            StructBaseDataType::Boolean,
            StructBaseDataType::Integer,
            StructBaseDataType::Double,
            StructBaseDataType::String,
            StructBaseDataType::Enum => self::_buildValueDefault($structElement),
            StructBaseDataType::DateTime => self::_buildValueDateTime($structElement),
            StructBaseDataType::Array => self::_buildValueArray($structElement),
            StructBaseDataType::DataType => self::_buildValueDataType($structElement),
            StructBaseDataType::Struct => self::_buildValueStruct($structElement),
        };
        return $value;
    }


    protected static function _buildValueArray(StructElement $structElement): null|array
    {
        if($structElement->isAllowsNull === true) {
            return null;
        }
        return [];
    }

    protected static function _buildValueDataType(StructElement $structElement): null|string|DataTypeInterface
    {
        $className = $structElement->structDataTypes[0]->className;
        if($structElement->hasDefaultValue === true) {
            if(is_string($structElement->defaultValue) === true) {
                return DataTypeFactory::create($className, (string) $structElement->defaultValue);
            }
            return $structElement->defaultValue;
        }
        if($structElement->isAllowsNull === true) {
            return null;
        }
        return self::NO_VALUE;
    }

    protected static function _buildValueStruct(StructElement $structElement): StructInterface
    {
        $className = $structElement->structDataTypes[0]->className;
        return self::create($className);
    }

    protected static function _buildValueDefault(StructElement $structElement): null|bool|int|float|string|UnitEnum
    {
        if($structElement->hasDefaultValue === true) {
            return $structElement->defaultValue;
        }
        if($structElement->isAllowsNull === true) {
            return null;
        }
        return self::NO_VALUE;
    }

    protected static function _buildValueDateTime(StructElement $structElement): null|\DateTimeInterface
    {
        if($structElement->hasDefaultValue === true) {
            return new \DateTimeImmutable($structElement->defaultValue);
        }
        if($structElement->isAllowsNull === true) {
            return null;
        }
        return new \DateTimeImmutable('1900-01-01 00:00:00', new \DateTimeZone('UTC'));
    }





}
