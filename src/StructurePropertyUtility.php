<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use ReflectionProperty;
use Struct\Attribute\DefaultValue;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidValueException;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Private\Struct\StructureProperty;

class StructurePropertyUtility
{
    /**
     * @param class-string<StructInterface>|StructInterface $structure
     * @return StructureProperty[]
     * @throws UnexpectedException
     */
    public static function readProperties(string|StructInterface $structure): array
    {
        $structureProperties = [];
        if (\is_a($structure, StructInterface::class, true) === false) {
            throw new InvalidValueException('The structure must implement <' . StructInterface::class . '>', 1675967847);
        }
        $properties = self::getProperties($structure);
        foreach ($properties as $property) {
            if ($property->isPublic() === false) {
                throw new InvalidValueException('The property <' . $property->getName() . '> must be public', 1675967850);
            }
            $structureProperties[$property->name] = self::buildPropertyReflection($property);
        }
        return $structureProperties;
    }

    /**
     * @param class-string<StructInterface>|StructInterface $structure
     * @return ReflectionProperty[]
     * @throws UnexpectedException
     */
    protected static function getProperties(string|StructInterface $structure): array
    {
        try {
            $reflection = new \ReflectionClass($structure);
            return $reflection->getProperties();
        } catch (\Throwable $exception) {
            throw new UnexpectedException(1652194304, $exception);
        }
    }

    /**
     * @param ReflectionProperty $property
     * @return StructureProperty
     * @throws UnexpectedException
     */
    protected static function buildPropertyReflection(ReflectionProperty $property): StructureProperty
    {
        $structureProperty = new StructureProperty();
        $structureProperty->name = $property->getName();

        $type = $property->getType();
        if ($type === null) {
            throw new InvalidValueException('The property <' . $property->name . '> must have an type declaration', 1652179807);
        }
        if (is_a($type, \ReflectionIntersectionType::class) === true) {
            throw new InvalidValueException('Intersection type is not supported at property <' . $property->name . '>', 1652179804);
        }
        if (is_a($type, \ReflectionUnionType::class) === true) {
            throw new InvalidValueException('Union type is not supported at property <' . $property->name . '>', 1652179804);
        }
        if (is_a($type, \ReflectionNamedType::class) === false) {
            throw new UnexpectedException(1652187714);
        }

        $structureProperty->type = $type->getName();
        $structureProperty->allowsNull = $type->allowsNull();
        $structureProperty->isBuiltin = $type->isBuiltin();

        $structureProperty->hasDefaultValue = $property->hasDefaultValue();
        $structureProperty->defaultValue = $property->getDefaultValue();
        self::readDefaultValueFromAttributes($property, $structureProperty);
        return $structureProperty;
    }

    protected static function readDefaultValueFromAttributes(ReflectionProperty $property, StructureProperty &$structureProperty): void
    {
        $attributes = $property->getAttributes();
        foreach ($attributes as $attribute) {
            $name = $attribute->getName();
            if ($name !== DefaultValue::class) {
                continue;
            }
            $attributeArguments = $attribute->getArguments();
            if (count(($attributeArguments)) !== 1) {
                continue;
            }
            $type = $property->getType();
            $typeName = $type->getName(); // @phpstan-ignore-line

            if (\is_a($typeName, DataTypeInterface::class, true) === true) {
                $defaultValueString = $attributeArguments[0];
                $defaultValue = DataTypeFactory::create($typeName, $defaultValueString); // @phpstan-ignore-line
                $structureProperty->hasDefaultValue = true;
                $structureProperty->defaultValue = $defaultValue;
            }

            if (\is_a($typeName, \DateTimeInterface::class, true) === true) {
                try {
                    $defaultValue = new \DateTime($attributeArguments[0]);
                } catch (\Exception $exception) {
                    throw new UnexpectedException(1675967987, $exception);
                }
                $structureProperty->hasDefaultValue = true;
                $structureProperty->defaultValue = $defaultValue;
            }
        }
    }
}
