<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Attribute\ArrayPassThrough;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Attribute;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Reflection\MemoryCache;
use Struct\Reflection\ReflectionUtility;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructArrayType;
use Struct\Struct\Internal\Struct\StructSignature\StructArrayTypeOption;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Utility\StructValidatorUtility;
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
        StructValidatorUtility::validate($objectSignature);
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
        $structDataTypes = self::_buildStructDataTypes($property->parameter->types);
        $structArrayType =  self::_buildStructArrayType($property, $structDataTypes);
        $element = new StructElement(
            $property->parameter->name,
            $property->parameter->isAllowsNull,
            null,
            $structDataTypes,
            $structArrayType,
        );
        return $element;
    }


    /**
     * @param array<StructDataType>$structDataTypes
     */
    protected static function _buildStructArrayType(Property $property, array $structDataTypes): ?StructArrayType
    {
        if(self::_hasArrayDataType($structDataTypes) === false) {
            return null;
        }
        $arrayPassThroughAttribute = self::_findFirstAttribute($property, ArrayPassThrough::class);
        if($arrayPassThroughAttribute !== null) {
            $structArrayType = new StructArrayType(
                StructArrayTypeOption::ArrayPassThrough,
                null,
            );
            return $structArrayType;
        }
        $arrayListAttribute = self::_findFirstAttribute($property, ArrayList::class);
        $arrayKeyListAttribute = self::_findFirstAttribute($property, ArrayKeyList::class);
        $structArrayTypeOption = null;
        $arguments = [];
        if($arrayListAttribute !== null ) {
            $structArrayTypeOption = StructArrayTypeOption::ArrayList;
            $arguments = $arrayListAttribute->arguments;
        }
        if($arrayKeyListAttribute !== null ) {
            $structArrayTypeOption = StructArrayTypeOption::ArrayKeyList;
            $arguments = $arrayKeyListAttribute->arguments;
        }
        if($structArrayTypeOption === null) {
            throw new InvalidStructException(1739035381, 'The array is undefined');
        }
        if(count($arguments) === 0) {
            throw new UnexpectedException();
        }
        $firstArgument = $arguments[0];
        $dataTypes = $firstArgument;
        if(is_string($firstArgument) === true) {
            $dataTypes = [$firstArgument];
        }
        $structDataTypes = self::_buildStructDataTypeByTypeString($dataTypes);
        $structArrayType = new StructArrayType(
            $structArrayTypeOption,
            $structDataTypes,
        );
        return $structArrayType;
    }

    /**
     * @param array<StructDataType>$structDataTypes
     */
    protected static function _hasArrayDataType(array $structDataTypes): bool
    {
        foreach ($structDataTypes as $structDataType) {
            if($structDataType->structUnderlyingDataType === StructUnderlyingDataType::Array) {
                return true;
            }
        }
        return false;
    }

    protected static function _findFirstAttribute(Property $property, string $attributeName): ?Attribute
    {
        $attributes = self::_findAttributes($property, $attributeName);
        if(count($attributes) === 0) {
            return null;
        }
        return $attributes[0];
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

    /**
     * @param array<NamedType> $namedTypes
     * @return array<StructDataType>
     */
    protected static function _buildStructDataTypes(array $namedTypes): array
    {
        $dataTypes = [];
        foreach ($namedTypes as $type) {
            $dataTypes[] = $type->dataType;
        }
        $structDataTypes = self::_buildStructDataTypeByTypeString($dataTypes);
        return $structDataTypes;
    }

    /**
     * @param array<string> $dataTypes
     * @return array<StructDataType>
     */
    protected static function _buildStructDataTypeByTypeString(array $dataTypes): array
    {
        $phpDataTypeCounts = [];
        foreach ($dataTypes as $dataType) {
            $underlyingDataType = StructDataTypeHelper::findUnderlyingDataType($dataType);
            $phpDataType = StructDataTypeHelper::findPhpDataType($underlyingDataType);
            $key = $phpDataType->name;
            if (array_key_exists($key, $phpDataTypeCounts) === false) {
                $phpDataTypeCounts[$key] = 0;
            }
            $phpDataTypeCounts[$key]++;
        }

        $structDataTypes = [];
        foreach ($dataTypes as $dataType) {
            $underlyingDataType = StructDataTypeHelper::findUnderlyingDataType($dataType);
            $phpDataType = StructDataTypeHelper::findPhpDataType($underlyingDataType);
            $key = $phpDataType->name;

            $className = null;
            $isClearlyDefined = null;
            if(self::_addClassName($underlyingDataType) === true) {
                $className = $dataType;
                $isClearlyDefined = true;
                if($phpDataTypeCounts[$key] > 1) {
                    $isClearlyDefined = false;
                }
            }
            $structDataType = new StructDataType(
                $underlyingDataType,
                $className,
                $isClearlyDefined
            );
            $structDataTypes[] = $structDataType;
        }
        return $structDataTypes;
    }

    protected static function _addClassName(StructUnderlyingDataType $underlyingDataType): bool
    {
        $addClassNme = match ($underlyingDataType) {
            StructUnderlyingDataType::Array,
            StructUnderlyingDataType::Integer,
            StructUnderlyingDataType::Boolean,
            StructUnderlyingDataType::Float,
            StructUnderlyingDataType::DateTime,
            StructUnderlyingDataType::EnumString,
            StructUnderlyingDataType::EnumInt,
            StructUnderlyingDataType::ArrayList,
            StructUnderlyingDataType::String => false,
            StructUnderlyingDataType::DataType,
            StructUnderlyingDataType::Enum,
            StructUnderlyingDataType::Struct => true,

        };
        return $addClassNme;
    }
}
