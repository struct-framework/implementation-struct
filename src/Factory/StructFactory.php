<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use DateTimeInterface;
use Struct\Attribute\DefaultValue;
use Struct\Struct\Internal\Placeholder\Undefined;
use Struct\Struct\Internal\Struct\ObjectSignature\Parameter;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Struct\ReflectionUtility;
use function is_a;
use Struct\Contracts\DataTypeInterfaceWritable;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use UnitEnum;

class StructFactory
{
    /**
     * @template T of StructInterface
     * @param  class-string<T> $structType
     * @return T
     */
    public static function create(string $structType): StructInterface
    {
        $structSignature = ReflectionUtility::readObjectSignature($structType);
        $properties = $structSignature->properties;

        $structure = new $structType();
        foreach ($properties as $property) {
            $name = $property->parameter->name;
            $value = self::buildValue($property->parameter);
            if ($value instanceof Undefined === false) {
                $structure->$name = $value; // @phpstan-ignore-line
            }
        }
        return $structure;
    }

    protected static function buildValue(Parameter $parameter): mixed
    {
        if ($parameter->hasDefaultValue) {
            return $parameter->defaultValue;
        }
        if ($parameter->isAllowsNull) {
            return null;
        }
        /** @var NamedType $type */
        $type = $parameter->types[0];
        $typeString = $type->type;

        if ($typeString === 'array') {
            return [];
        }
        if (is_a($typeString, StructInterface::class, true) === true) {
            return self::create($type->type);
        }

        $defaultValue = self::readDefaultValue($parameter, $typeString);
        if($defaultValue !== null) {
            return $defaultValue;
        }

        if (
            is_a($typeString, DateTimeInterface::class, true) === true ||
            is_a($typeString, DataTypeInterfaceWritable::class, true) === true
        ) {
            return self::create($type->type);
        }
        if (
            $typeString === 'string' ||
            $typeString === 'int' ||
            $typeString === 'float' ||
            $typeString === 'bool' ||
            is_a($typeString, DateTimeInterface::class, true) === true ||
            is_a($typeString, DataTypeInterfaceWritable::class, true) === true ||
            is_a($typeString, UnitEnum::class, true) === true
        ) {
            $undefined = new Undefined();
            return $undefined;
        }
        throw new InvalidStructException('The type <' . $type->type . '> is not supported', 1675967989);
    }

    protected static function readDefaultValue(Parameter $parameter, string $typeString): mixed
    {
        $defaultValue = self::findDefaultValueString($parameter,DefaultValue::class);
        if($defaultValue === null) {
            return null;
        }
        if(is_a($typeString, DataTypeInterfaceWritable::class, true) === true) {
            $value = new $typeString($defaultValue);
            return $value;
        }
        if(is_a($typeString, DateTimeInterface::class, true) === true) {
            $value = new \DateTime($defaultValue);
            return $value;
        }
        return null;
    }

    protected static function findDefaultValueString(Parameter $parameter, string $attributName): ?string
    {
        foreach ($parameter->attributes as $attribute) {
            if($attribute->name === DefaultValue::class) {
                if(count($attribute->arguments) !== 1) {
                    return null;
                }
                $defaultValue = $attribute->arguments[0];
                if(is_string($defaultValue) === false) {
                    return null;
                }
                return $defaultValue;
            }
        }
        return null;
    }
}
