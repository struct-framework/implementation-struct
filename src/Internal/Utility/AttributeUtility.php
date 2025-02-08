<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;

/**
 * @internal
 */
class AttributeUtility
{
    /**
     * @return array<string>|null
     */
    public static function findFirstAttributeArgumentAsArray(Property $property, string $attributeName): ?array
    {
        $argument =  self::findFirstAttributeArgumentAsArrayOrString($property, $attributeName);
        if (is_array($argument) === true) {
            return $argument;
        }
        return null;
    }

    public static function findFirstAttributeArgumentAsString(Property $property, string $attributeName): ?string
    {
        $argument =  self::findFirstAttributeArgumentAsArrayOrString($property, $attributeName);
        if (is_string($argument) === true) {
            return $argument;
        }
        return null;
    }

    /**
     * @return string|array<string>|null
     */
    public static function findFirstAttributeArgumentAsArrayOrString(Property $property, string $attributeName): null|string|array
    {
        $argument =  self::_findFirstAttributeArgument($property, $attributeName);
        if (is_string($argument) === true) {
            return $argument;
        }
        if (is_array($argument) === false) {
            return null;
        }
        $items = [];
        foreach ($argument as $item) {
            if (is_string($item) === false) {
                continue;
            }
            $items[] = $item;
        }
        return $items;
    }

    public static function findFirstAttribute(Property $property, string $attributeName): ?Attribute
    {
        $attributes = self::_findAttributes($property, $attributeName);
        if (count($attributes) === 0) {
            return null;
        }
        return $attributes[0];
    }

    protected static function _findFirstAttributeArgument(Property $property, string $attributeName): mixed
    {
        $attribute = self::findFirstAttribute($property, $attributeName);
        if ($attribute === null) {
            return null;
        }
        $arguments = $attribute->arguments;
        if (count($arguments) === 0) {
            return null;
        }
        return $arguments[0];
    }

    /**
     * @return array<Attribute>
     */
    protected static function _findAttributes(Property $property, string $attributeName): array
    {
        $attributes = [];
        foreach ($property->parameter->attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                $attributes[] = $attribute;
            }
        }
        return $attributes;
    }
}
