<?php

declare(strict_types=1);

namespace Struct\Struct;

use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Attribute\ArrayPassThrough;
use Struct\Attribute\DefaultValue;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Reflection\MemoryCache;
use Struct\Reflection\ReflectionUtility;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\StructArrayType;
use Struct\Struct\Internal\Struct\StructSignature\StructArrayTypeOption;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Validator\PropertyValidator;

class StructReflectionUtility
{
    /**
     * @param class-string<StructInterface>|object $structNameOrStruct
     */
    public static function readSignature(object|string $structNameOrStruct): StructSignature
    {
        $structName = $structNameOrStruct;
        if (is_object($structName) === true) {
            $structName = $structName::class;
        }
        $cacheIdentifier = MemoryCache::buildCacheIdentifier($structName, '1936c4c4-f4fa-404c-b5e6-dfaffb60e69a');
        if (MemoryCache::has($cacheIdentifier)) {
            return MemoryCache::read($cacheIdentifier);
        }
        $signature = self::_readSignature($structName);
        MemoryCache::write($cacheIdentifier, $signature);
        return $signature;
    }

    /**
     * @param class-string<StructInterface> $structName
     */
    protected static function _readSignature(string $structName): StructSignature
    {
        $objectSignature = ReflectionUtility::readSignature($structName);
        $structName = $objectSignature->objectName;
        $isReadOnly = $objectSignature->isReadOnly;
        $elements = self::_buildElements($structName, $objectSignature->properties);
        $structSignature = new StructSignature(
            $structName,
            $isReadOnly,
            $elements,
        );
        return $structSignature;
    }

    /**
     * @param array<Property> $properties
     * @return array<StructElement>
     */
    protected static function _buildElements(string $structName, array $properties): array
    {
        $elements = [];
        foreach ($properties as $property) {
            PropertyValidator::validate($structName, $property);
            $elements[] = self::_buildElement($property);
        }
        return $elements;
    }

    protected static function _buildElement(Property $property): StructElement
    {
        $structDataTypes = self::_buildStructDataTypesFromNamedType($property->parameter->types);
        list($hasDefaultValue, $defaultValue) = self::_buildDefaultValue($property);
        $structArrayType =  self::_buildStructArrayType($property);

        $element = new StructElement(
            $property->parameter->name,
            $property->parameter->isAllowsNull,
            $hasDefaultValue,
            $defaultValue,
            $structDataTypes,
            $structArrayType,
        );
        return $element;
    }

    protected static function _buildStructArrayType(Property $property): ?StructArrayType
    {
        $isArrayPassThrough = self::_findAttribute($property, ArrayPassThrough::class) === [];
        if ($isArrayPassThrough === true) {
            $structArrayType = new StructArrayType(
                StructArrayTypeOption::ArrayPassThrough,
                [],
            );
            return $structArrayType;
        }
        $structArrayTypeOption = StructArrayTypeOption::ArrayList;
        $arrayListArguments = self::_findAttribute($property, ArrayList::class);
        if ($arrayListArguments === null) {
            $arrayListArguments = self::_findAttribute($property, ArrayKeyList::class);
            if ($arrayListArguments === null) {
                return null;
            }
            $structArrayTypeOption = StructArrayTypeOption::ArrayKeyList;
        }
        if (count($arrayListArguments) === 0) {
            return null;
        }

        $arguments = $arrayListArguments[0];
        if (is_string($arguments) === true) {
            $arguments = [$arguments];
        }
        if (is_array($arguments) === false) {
            return null;
        }
        $structDataType = self::_buildStructDataTypesFromDataType($arguments);
        $structArrayType = new StructArrayType(
            $structArrayTypeOption,
            $structDataType,
        );
        return $structArrayType;
    }

    protected static function _buildDefaultValue(Property $property): array
    {
        $hasDefaultValue = $property->parameter->hasDefaultValue;
        $defaultValue = $property->parameter->defaultValue;
        if ($hasDefaultValue === true) {
            return [
                true,
                $defaultValue,
            ];
        }
        $defaultValue = self::_findDefaultValue($property);
        if ($defaultValue !== null) {
            $hasDefaultValue = true;
        }
        return [
            $hasDefaultValue,
            $defaultValue,
        ];
    }

    protected static function _findDefaultValue(Property $property): ?string
    {
        $defaultValueAttributeArguments = self::_findAttribute($property, DefaultValue::class);
        if (
            $defaultValueAttributeArguments === null ||
            count($defaultValueAttributeArguments) === 0 ||
            is_string($defaultValueAttributeArguments[0]) === false
        ) {
            return null;
        }
        return $defaultValueAttributeArguments[0];
    }

    /**
     * @return array<mixed>|null
     */
    protected static function _findAttribute(Property $property, string $attributeName): ?array
    {
        foreach ($property->parameter->attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                return $attribute->arguments;
            }
        }
        return null;
    }

    /**
     * @param array<NamedType> $namedTypes
     * @return array<StructDataType>
     */
    protected static function _buildStructDataTypesFromNamedType(array $namedTypes): array
    {
        $structDataTypes = [];
        foreach ($namedTypes as $type) {
            $dataType = $type->dataType;
            $structDataTypes[] = self::_buildStructDataType($dataType);
        }
        return $structDataTypes;
    }

    /**
     * @param array<string> $dataTypes
     * @return array<StructDataType>
     */
    protected static function _buildStructDataTypesFromDataType(array $dataTypes): array
    {
        $structDataTypes = [];
        foreach ($dataTypes as $dataType) {
            if (is_string($dataType) === false) {
                continue;
            }
            $structDataTypes[] = self::_buildStructDataType($dataType);
        }
        return $structDataTypes;
    }

    protected static function _buildStructDataType(string $dataType): StructDataType
    {
        $structBaseDataTypes = StructDataTypeHelper::findDataType($dataType);
        $className = match ($structBaseDataTypes) {
            StructBaseDataType::NULL,
            StructBaseDataType::Array,
            StructBaseDataType::Integer,
            StructBaseDataType::Boolean,
            StructBaseDataType::Double,
            StructBaseDataType::DateTime,
            StructBaseDataType::String => null,
            StructBaseDataType::DataType,
            StructBaseDataType::Enum,
            StructBaseDataType::Struct => $dataType,
        };
        $structDataType = new StructDataType(
            $structBaseDataTypes,
            $className,
        );
        return $structDataType;
    }
}
