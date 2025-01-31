<?php

declare(strict_types=1);

namespace Struct\Struct;

use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Reflection\Internal\Struct\ObjectSignature;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parameter;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\IntersectionType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Visibility;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Reflection\ReflectionUtility;

class StructValidatorUtility
{
    public static function isValidStruct(StructInterface|string $struct): void
    {
        if (
            is_string($struct) &&
            is_a($struct, StructInterface::class, true) === false
        ) {
            throw new InvalidStructException('Class must implements <' . StructInterface::class . '>', 1737828376);
        }
        $signature = ReflectionUtility::readSignature($struct);
        self::checkForMethods($signature);
        self::checkProperties($signature);
    }

    public static function isValidDataType(DataTypeInterface|string $dataType): void
    {
        if (
            is_string($dataType) &&
            is_a($dataType, DataTypeInterface::class, true) === false
        ) {
            throw new InvalidStructException('Class must implements <' . DataTypeInterface::class . '>', 1737834524);
        }
        $signature = ReflectionUtility::readSignature($dataType);
        if ($signature->isReadOnly === false) {
            throw new InvalidStructException('The data type <' . $signature->objectName . '> must be readonly', 1737834619);
        }
        if ($signature->isFinal === false) {
            throw new InvalidStructException('The data type <' . $signature->objectName . '> must be final', 1737834686);
        }
        if (count($signature->constructorArguments) === 0) {
            throw new InvalidStructException('The data type <' . $signature->objectName . '> must have at least one constructor argument', 1737834794);
        }

        $firstConstructorArgument = $signature->constructorArguments[0];
        if (self::checkForTypeType($firstConstructorArgument, 'string') === false) {
            throw new InvalidStructException('The first constructor argument must  must support the data type: string', 1737877583);
        }
    }

    protected static function checkForTypeType(Parameter $parameter, string $typeName): bool
    {
        foreach ($parameter->types as $type) {
            if($type instanceof IntersectionType === true) {
                continue;
            }
            if ($type->dataType === $typeName) {
                return true;
            }
        }
        return false;
    }

    protected static function checkForMethods(ObjectSignature $signature): void
    {
        $methodCount = count($signature->methods);
        if ($methodCount === 0) {
            return;
        }
        if (
            $methodCount === 1 &&
            $signature->methods[0]->name === '__construct'
        ) {
            return;
        }
        throw new InvalidStructException('A struct must not have methods', 1737828924);
    }

    protected static function checkProperties(ObjectSignature $signature): void
    {
        foreach ($signature->properties as $property) {
            self::checkPropertyVisibility($property);
            self::checkPropertyReadOnly($property);
            self::checkPropertyTypes($property);
        }
    }

    protected static function checkPropertyVisibility(Property $property): void
    {
        if ($property->visibility !== Visibility::public) {
            throw new InvalidStructException('The property: ' . $property->parameter->name . ' must be public.', 1737828927);
        }
    }
    protected static function checkPropertyReadOnly(Property $property): void
    {
        if ($property->isReadOnly === true && $property->parameter->isPromoted === false) {
            throw new InvalidStructException('The readonly property: ' . $property->parameter->name . ' must be promoted in constructor.', 1737829582);
        }
    }

    protected static function checkPropertyTypes(Property $property): void
    {
        $hasArray = false;
        $hasArrayAttribute = false;

        foreach ($property->parameter->types as $type) {
            if ($type instanceof IntersectionType === true) {
                throw new InvalidStructException('The property: ' . $property->parameter->name . ' must not have intersection types', 1737830162);
            }
            if ($type->isBuiltin === true && $type->dataType === 'array') {
                $hasArray = true;
            }
            foreach ($property->parameter->attributes as $attribute) {
                if (
                    $attribute->name === ArrayList::class ||
                    $attribute->name === ArrayKeyList::class
                ) {
                    $hasArrayAttribute = true;
                }
            }
            if ($type->isBuiltin === false) {
                self::checkNonBuildInPropertyTypes($property, $type);
            }
        }
        if ($hasArray === true && $hasArrayAttribute === false) {
            throw new InvalidStructException('The property: ' . $property->parameter->name . ' must not have either the attribute <' . ArrayList::class . '> or <' . ArrayKeyList::class . '>', 1737830644);
        }
    }

    protected static function checkNonBuildInPropertyTypes(Property $property, NamedType $type): void
    {
        if ($type->dataType === \DateTimeInterface::class) {
            return;
        }
        if (is_a($type->dataType, StructInterface::class, true) === true) {
            self::isValidStruct($type->dataType);
            return;
        }
        if (is_a($type->dataType, \UnitEnum::class, true) === true) {
            return;
        }
        if (is_a($type->dataType, DataTypeInterface::class, true) === true) {
            self::isValidDataType($type->dataType);
            return;
        }
        throw new InvalidStructException('The property: ' . $property->parameter->name . ' must has an unsupported type: ' . $type->dataType, 1737830887);
    }
}
