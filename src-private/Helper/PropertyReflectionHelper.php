<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Helper;

use Exception\Unexpected\UnexpectedException;
use function is_a;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Attribute\StructType;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Struct\Private\Struct\PropertyReflection;
use Throwable;

class PropertyReflectionHelper
{
    /**
     * @param StructInterface|class-string $structure
     * @return array<PropertyReflection>
     */
    public static function readProperties(StructInterface|string $structure): array
    {
        $properties = [];
        try {
            $reflection = new ReflectionClass($structure);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(1652124640, $exception);
        }
        $reflectionProperties = $reflection->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            if ($reflectionProperty->isPublic() === false) {
                throw new InvalidValueException('The property <' . $propertyName . '> in <' . $structure::class . '> must be public', 1675967772);
            }
            $properties[] = self::buildPropertyReflection($reflectionProperty);
        }
        return $properties;
    }

    /**
     * @param StructInterface|class-string $structure
     */
    public static function hasConstructorProperties(StructInterface|string $structure): bool
    {
        try {
            $reflection = new ReflectionClass($structure);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(1652124640, $exception);
        }
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return false;
        }
        return true;
    }

    protected static function buildPropertyReflection(ReflectionProperty|ReflectionParameter $reflectionPropertyOrParameter): PropertyReflection
    {
        $propertyReflection = new PropertyReflection();
        $propertyReflection->name = $reflectionPropertyOrParameter->getName();
        $type = $reflectionPropertyOrParameter->getType();
        if ($type === null) {
            throw new InvalidValueException('The property <' . $propertyReflection->name . '> must have an type declaration', 1652179807);
        }
        if (is_a($type, ReflectionIntersectionType::class) === true) {
            throw new InvalidValueException('Intersection type is not supported at property <' . $propertyReflection->name . '>', 1652179804);
        }
        if (is_a($type, ReflectionUnionType::class) === true) {
            throw new InvalidValueException('Union type is not supported at property <' . $propertyReflection->name . '>', 1652179804);
        }
        if (is_a($type, ReflectionNamedType::class) === false) {
            throw new UnexpectedException(1652187714);
        }

        $propertyReflection->isAllowsNull = $type->allowsNull();
        if ($reflectionPropertyOrParameter instanceof ReflectionProperty) {
            $propertyReflection->isHasDefaultValue = $reflectionPropertyOrParameter->hasDefaultValue();
            $propertyReflection->defaultValue = $reflectionPropertyOrParameter->getDefaultValue();
        }

        $propertyReflection->type = $type->getName();
        $propertyReflection->isBuiltin = $type->isBuiltin();

        self::readStructCollectionAttributes($reflectionPropertyOrParameter, $propertyReflection);
        self::readArrayAttributes($reflectionPropertyOrParameter, $propertyReflection);

        return $propertyReflection;
    }

    protected static function readStructCollectionAttributes(ReflectionProperty|ReflectionParameter $reflectionPropertyOrParameter, PropertyReflection $propertyReflection): void
    {
        if (is_a($propertyReflection->type, StructCollectionInterface::class, true) === false) {
            return;
        }
        $structTypes = $reflectionPropertyOrParameter->getAttributes(StructType::class);
        if (count($structTypes) === 1) {
            $structType = $structTypes[0];
            $arguments = $structType->getArguments();
            if (count($arguments) === 0) {
                throw new UnexpectedException(1698952338);
            }
            $structType = $arguments[0];
            $propertyReflection->structTypeOfArrayOrCollection = $structType;
            return;
        }
        $structType = self::readTypeByCurrent($propertyReflection->type);
        if ($structType === StructInterface::class) {
            throw new InvalidValueException('The property <' . $reflectionPropertyOrParameter->getName() . '> must have an "StructType" or more specific return value at method current', 1698953636);
        }
        $propertyReflection->structTypeOfArrayOrCollection = $structType;
    }

    /**
     * @param class-string<StructCollectionInterface> $structCollectionType
     */
    protected static function readTypeByCurrent(string $structCollectionType): string
    {
        $reflection = new ReflectionClass($structCollectionType);
        try {
            $methodCurrent = $reflection->getMethod('current');
        } catch (Throwable $exception) {
            throw new UnexpectedException(1698953504, $exception);
        }
        $returnType = $methodCurrent->getReturnType();
        if ($returnType instanceof ReflectionNamedType === false) {
            throw new UnexpectedException(1698953565);
        }
        $structType = $returnType->getName();
        return $structType;
    }

    protected static function readArrayAttributes(ReflectionProperty|ReflectionParameter $reflectionPropertyOrParameter, PropertyReflection $propertyReflection): void
    {
        if ($propertyReflection->type !== 'array') {
            return;
        }
        $arrayListAttributes = $reflectionPropertyOrParameter->getAttributes(ArrayList::class);
        $arrayKeyListAttributes = $reflectionPropertyOrParameter->getAttributes(ArrayKeyList::class);
        if (count($arrayListAttributes) === 0 && count($arrayKeyListAttributes) === 0) {
            return;
        }
        if (
            (count($arrayListAttributes) !== 1 && count($arrayKeyListAttributes) !== 1) ||
            count($arrayListAttributes) > 1 ||
            count($arrayKeyListAttributes) >  1
        ) {
            throw new InvalidValueException('The property <' . $reflectionPropertyOrParameter->getName() . '> can not be ArrayList and ArrayKeyList', 1652195496);
        }
        $attributes = $arrayListAttributes;
        if (count($attributes) === 0) {
            $propertyReflection->isArrayKeyList = true;
            $attributes = $arrayKeyListAttributes;
        }
        $attribute = $attributes[0];
        $arguments = $attribute->getArguments();
        if (count($arguments) === 0) {
            return;
        }
        $propertyReflection->structTypeOfArrayOrCollection = $arguments[0];
    }
}
