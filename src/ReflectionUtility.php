<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Exception\InvalidValueException;
use Struct\Struct\Private\Struct\ObjectStruct;
use Struct\Struct\Private\Struct\ObjectStruct\Property;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\IntersectionType;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\NamedType;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\Visibility;
use Struct\Struct\Private\Struct\ObjectStruct\Method;
use Struct\Struct\Private\Struct\ObjectStruct\Parameter;
use Struct\Struct\Private\Struct\ObjectStruct\Parts\Attribute;

class ReflectionUtility
{

    /**
     * @param object|class-string<object> $object
     */
    public static function readObjectStruct(object|string $object): ObjectStruct
    {
        $objectName = $object;
        if(is_object($object) === true) {
            $objectName = $object::class;
        }
        try {
            $reflection = new ReflectionClass($objectName);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(1724442032, $exception);
        }
        $constructorArguments = self::readConstructorArguments($reflection);
        $properties = self::readProperties($reflection);
        $methods = self::readMethods($reflection);

        $objectStruct = new ObjectStruct(
            $constructorArguments,
            $properties,
            $methods,
        );
        return $objectStruct;
    }


    /**
     * @return array<Parameter>
     */
    protected static function readConstructorArguments(ReflectionClass $reflection): array
    {
        $properties = [];
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $properties;
        }
        $reflectionParameters = $constructor->getParameters();
        $parameters = self::readParameters($reflectionParameters);
        return $parameters;
    }


    /**
     * @param array<ReflectionParameter> $reflectionParameters
     * @return array<Parameter>
     */
    protected static function readParameters(array $reflectionParameters): array
    {
        $parameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameter = self::buildParameter($reflectionParameter);
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    /**
     * @return array<Method>
     */
    protected static function readMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        $methodReflections = $reflection->getMethods();
        foreach ($methodReflections as $methodReflection) {
            $methods[] = self::readMethod($methodReflection);
        }
        return $methods;
    }

    /**
     * @return array<Method>
     */
    protected static function readMethod(ReflectionMethod $methodReflection): Method
    {
        $name = $methodReflection->getName();
        $returnTypeReflection = $methodReflection->getReturnType();
        $returnAllowsNull = false;
        $returnType = null;
        if($returnTypeReflection !== null) {
            if ($returnTypeReflection instanceof \ReflectionNamedType === false) {
                throw new UnexpectedException(1724520780);
            }
            $returnType = new NamedType(
                $returnTypeReflection->getName(),
                $returnTypeReflection->isBuiltin(),
            );
            $returnAllowsNull = $returnTypeReflection->allowsNull();
        }

        $parameterReflections = $methodReflection->getParameters();
        $visibility = self::buildVisibility($methodReflection);
        $isStatic = $methodReflection->isStatic();
        $parameters = self::readParameters($parameterReflections);
        $attributes = self::buildAttributes($methodReflection);


        $method = new Method(
            $name,
            $returnType,
            $returnAllowsNull,
            $visibility,
            $isStatic,
            $parameters,
            $attributes,
        );
        return $method;
    }

    protected static function buildParameter(ReflectionParameter|ReflectionProperty $reflectionPropertyOrParameter): Parameter
    {

        $name = $reflectionPropertyOrParameter->getName();
        $type = $reflectionPropertyOrParameter->getType();
        if ($type === null) {
            throw new InvalidValueException('The property <' . $name . '> must have an type declaration', 1724442038);
        }
        $types = self::buildPropertyTypes($type);
        $isAllowsNull = $type->allowsNull();
        $defaultValue = null;

        if ($reflectionPropertyOrParameter instanceof ReflectionProperty === true) {
            $hasDefaultValue = $reflectionPropertyOrParameter->hasDefaultValue();
            if ($hasDefaultValue === true) {
                $defaultValue = $reflectionPropertyOrParameter->getDefaultValue();
            }
        }
        if ($reflectionPropertyOrParameter instanceof ReflectionParameter === true) {
            $hasDefaultValue = $reflectionPropertyOrParameter->isDefaultValueAvailable();
            if ($hasDefaultValue === true) {
                $defaultValue = $reflectionPropertyOrParameter->getDefaultValue();
            }
        }

        $attributes = self::buildAttributes($reflectionPropertyOrParameter);

        $arrayType = null;
        $isArrayKeyList = false;
        if(count($types) === 1 && $types[0]->type === 'array') {
            $arrayType = self::readArrayType($reflectionPropertyOrParameter);
            $isArrayKeyList = self::readIsArrayKeyList($reflectionPropertyOrParameter);
        }
        $parameter = new Parameter(
            $name,
            $types,
            $isAllowsNull,
            $hasDefaultValue,
            $defaultValue,
            $attributes,
            $arrayType,
            $isArrayKeyList,
        );
        return $parameter;
    }


    /**
     * @return array<Attribute>
     */
    protected static function buildAttributes(ReflectionProperty|ReflectionParameter|ReflectionMethod $reflectionPropertyOrParameter): array
    {
        $attributes = [];
        $attributeReflections = $reflectionPropertyOrParameter->getAttributes();
        foreach ($attributeReflections as $attributeReflection) {
            $name = $attributeReflection->getName();
            $target = $attributeReflection->getTarget();
            $isRepeated = $attributeReflection->isRepeated();
            $arguments = $attributeReflection->getArguments();
            $attribute = new Attribute(
                $name,
                $target,
                $isRepeated,
                $arguments,
            );
            $attributes[] = $attribute;
        }
        return $attributes;
    }

    /**
     * @return array<Property>
     */
    protected static function readProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        $propertyReflections = $reflection->getProperties();
        foreach ($propertyReflections as $propertyReflection) {
            $property = self::buildProperty($propertyReflection);
            $properties[] = $property;
        }
        return $properties;
    }


    protected static function buildProperty(ReflectionProperty $propertyReflection): Property
    {

        $parameter = self::buildParameter($propertyReflection);
        $isReadOnly = $propertyReflection->isReadOnly();
        $visibility = self::buildVisibility($propertyReflection);
        $isStatic = $propertyReflection->isStatic();
        $property = new Property(
            $parameter,
            $isReadOnly,
            $visibility,
            $isStatic,
        );
        return $property;
    }

    protected static function buildVisibility(ReflectionProperty|ReflectionMethod $reflectionPropertyOrParameter): Visibility
    {
        $visibility = null;
        if($reflectionPropertyOrParameter->isPrivate() === true) {
            $visibility = Visibility::private;
        }
        if($reflectionPropertyOrParameter->isPublic() === true) {
            $visibility = Visibility::public;
        }
        if($reflectionPropertyOrParameter->isProtected() === true) {
            $visibility = Visibility::protected;
        }
        if($visibility === null) {
            throw new UnexpectedException(1724522961);
        }
        return $visibility;
    }


    protected static function buildPropertyTypes(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType $type): array
    {
        $propertyTypes = [];
        if ($type instanceof ReflectionNamedType === true) {
            $newPropertyTypes = self::buildFromReflectionNamed($type);
            $propertyTypes = array_merge($propertyTypes,  $newPropertyTypes);
        }
        if ($type instanceof ReflectionUnionType === true) {
            $newPropertyTypes = self::buildFromUnionType($type);
            $propertyTypes = array_merge($propertyTypes,  $newPropertyTypes);
        }
        if ($type instanceof ReflectionIntersectionType === true) {
            $newPropertyTypes = self::buildFromIntersectionType($type);
            $propertyTypes = array_merge($propertyTypes,  $newPropertyTypes);
        }
        return $propertyTypes;
    }

    protected static function buildFromIntersectionType(ReflectionIntersectionType $type): array
    {
        $intersectionTypes = [];
        foreach ($type->getTypes() as $intersectionType) {
            if ($intersectionType instanceof ReflectionNamedType === false) {
                throw new UnexpectedException(1724439483);
            }
            $intersectionTypes[] = self::buildFromReflectionNamed($intersectionType);
        }
        $propertyType = new IntersectionType(
            $intersectionTypes,
        );
        return [$propertyType];
    }

    protected static function buildFromUnionType(ReflectionUnionType $type): array
    {
        $propertyTypes = [];
        foreach ($type->getTypes() as $unionType) {
            if ($unionType instanceof ReflectionNamedType === true) {
                $newPropertyTypes = self::buildFromReflectionNamed($unionType);
                $propertyTypes = array_merge($propertyTypes,  $newPropertyTypes);
            }
            if ($unionType instanceof ReflectionIntersectionType === true) {
                $newPropertyTypes = self::buildFromIntersectionType($unionType);
                $propertyTypes = array_merge($propertyTypes,  $newPropertyTypes);
            }
        }
        return $propertyTypes;
    }


    protected static function buildFromReflectionNamed(ReflectionNamedType $type): array
    {
        $propertyType = new NamedType(
            $type->getName(),
            $type->isBuiltin(),
        );
        return [$propertyType];
    }


    protected static function readIsArrayKeyList(ReflectionProperty|ReflectionParameter $reflectionPropertyOrParameter): bool
    {
        $arrayKeyListAttributes = $reflectionPropertyOrParameter->getAttributes(ArrayKeyList::class);
        if(count($arrayKeyListAttributes) === 0) {
            return false;
        }
        return true;
    }

    protected static function readArrayType(ReflectionProperty|ReflectionParameter $reflectionPropertyOrParameter): ?string
    {
        $arrayListAttributes = $reflectionPropertyOrParameter->getAttributes(ArrayList::class);
        $arrayKeyListAttributes = $reflectionPropertyOrParameter->getAttributes(ArrayKeyList::class);
        if (count($arrayListAttributes) === 0 && count($arrayKeyListAttributes) === 0) {
            return null;
        }
        if (count($arrayListAttributes) > 1 || count($arrayKeyListAttributes) >  1) {
            throw new InvalidValueException('The property <' . $reflectionPropertyOrParameter->getName() . '> can not have multiple ArrayList and ArrayKeyList', 1724442044);
        }
        if (count($arrayListAttributes) === 1 && count($arrayKeyListAttributes) ===  1) {
            throw new InvalidValueException('The property <' . $reflectionPropertyOrParameter->getName() . '> can not be ArrayList and ArrayKeyList', 1724442047);
        }
        $attributes = $arrayListAttributes;
        if (count($arrayKeyListAttributes) === 1) {
            $attributes = $arrayKeyListAttributes;
        }
        $attribute = $attributes[0];
        $arguments = $attribute->getArguments();
        if (
            count($arguments) !== 1 ||
            is_string($arguments[0]) === false
        ) {
            throw new InvalidValueException('The property <' . $reflectionPropertyOrParameter->getName() . '> must have in ArrayList and ArrayKeyList with one argument type', 1724442049);
        }
        return $arguments[0];
    }


}
