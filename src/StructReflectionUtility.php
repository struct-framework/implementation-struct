<?php

declare(strict_types=1);

namespace Struct\Struct;

use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Attribute\ArrayPassThrough;
use Struct\Attribute\DefaultValue;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Reflection\MemoryCache;
use Struct\Reflection\ReflectionUtility;
use Struct\Struct\Internal\Helper\FormatHelper;
use Struct\Struct\Internal\Helper\StructDataTypeHelper;
use Struct\Struct\Internal\Struct\StructSignature;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingArrayType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingDataType;
use Struct\Struct\Internal\Struct\StructSignature\DataType\UnclearDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Struct\StructSignature\StructElementArray;
use Struct\Struct\Internal\Utility\AttributeUtility;
use Struct\Struct\Internal\Utility\StructValidatorUtility;
use Struct\Struct\Internal\Utility\UnserializeUtility;
use Struct\Struct\Internal\Validator\PropertyValidator;

class StructReflectionUtility
{
    /**
     * @param class-string<StructInterface>|StructInterface $structNameOrStruct
     */
    public static function readSignature(StructInterface|string $structNameOrStruct): StructSignature
    {
        $structName = $structNameOrStruct;
        if (is_object($structName) === true) {
            $structName = $structName::class;
        }
        $cacheIdentifier = MemoryCache::buildCacheIdentifier($structName, '1936c4c4-f4fa-404c-b5e6-dfaffb60e69a');
        if (MemoryCache::has($cacheIdentifier)) {
            /** @var StructSignature $signature */
            $signature =  MemoryCache::read($cacheIdentifier);
            return $signature;
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
        $structDataTypeCollection = self::_buildStructDataTypeCollectionFromNamedTypes($property->parameter->types);
        $structArrayType =  self::_buildStructArrayType($property, $structDataTypeCollection->structDataTypes);
        $defaultValue = self::_buildDefaultValue($property, $structDataTypeCollection);

        $element = new StructElement(
            $property->parameter->name,
            $property->parameter->isAllowsNull,
            $defaultValue,
            $structDataTypeCollection,
            $structArrayType,
        );
        return $element;
    }

    protected static function _buildDefaultValue(Property $property, StructDataTypeCollection $structDataTypeCollection): ?Value
    {
        if ($property->parameter->defaultValue !== null) {
            return $property->parameter->defaultValue;
        }
        $defaultValue = AttributeUtility::findFirstAttributeArgument($property, DefaultValue::class);
        if ($defaultValue === null) {
            return null;
        }
        $structValueType = UnserializeUtility::processValue($defaultValue, $structDataTypeCollection);
        $value = FormatHelper::buildValue($structValueType);
        return $value;
    }

    /**
     * @param array<StructDataType>$structDataTypes
     */
    protected static function _buildStructArrayType(Property $property, array $structDataTypes): ?StructElementArray
    {
        if (self::_hasArrayDataType($structDataTypes) === false) {
            return null;
        }
        $arrayPassThroughAttribute = AttributeUtility::findFirstAttribute($property, ArrayPassThrough::class);
        if ($arrayPassThroughAttribute !== null) {
            $structArrayType = new StructElementArray(
                StructUnderlyingArrayType::ArrayPassThrough,
                null,
            );
            return $structArrayType;
        }
        $arrayListAttribute = AttributeUtility::findFirstAttributeArgumentAsArrayOrString($property, ArrayList::class);
        $arrayKeyListAttribute = AttributeUtility::findFirstAttributeArgumentAsArrayOrString($property, ArrayKeyList::class);
        $structArrayTypeOption = null;
        $arguments = [];
        if ($arrayListAttribute !== null) {
            $structArrayTypeOption = StructUnderlyingArrayType::ArrayList;
            $arguments = $arrayListAttribute;
        }
        if ($arrayKeyListAttribute !== null) {
            $structArrayTypeOption = StructUnderlyingArrayType::ArrayKeyList;
            $arguments = $arrayKeyListAttribute;
        }
        if ($structArrayTypeOption === null) {
            throw new InvalidStructException(1739035381, 'The array is undefined');
        }
        $dataTypes = $arguments;
        if (is_string($arguments) === true) {
            $dataTypes = [$arguments];
        }
        $structDataTypeCollection = self::_buildStructDataTypeCollection($dataTypes);
        $structArrayType = new StructElementArray(
            $structArrayTypeOption,
            $structDataTypeCollection,
        );
        return $structArrayType;
    }

    /**
     * @param array<StructDataType>$structDataTypes
     */
    protected static function _hasArrayDataType(array $structDataTypes): bool
    {
        foreach ($structDataTypes as $structDataType) {
            if ($structDataType->structUnderlyingDataType === StructUnderlyingDataType::Array) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<NamedType> $namedTypes
     */
    protected static function _buildStructDataTypeCollectionFromNamedTypes(array $namedTypes): StructDataTypeCollection
    {
        $dataTypes = [];
        foreach ($namedTypes as $type) {
            $dataTypes[] = $type->dataType;
        }
        $structDataTypeCollection = self::_buildStructDataTypeCollection($dataTypes);
        return $structDataTypeCollection;
    }

    /**
     * @param array<string> $dataTypes
     */
    protected static function _buildStructDataTypeCollection(array $dataTypes): StructDataTypeCollection
    {
        $structDataTypes = self::_buildStructDataTypes($dataTypes);
        $unclearIntCount = 0;
        $unclearStringCount = 0;
        $unclearArrayCount = 0;
        foreach ($structDataTypes as $structDataType) {
            $unclearType = $structDataType->clearDataType;
            match ($unclearType) {
                null => 0,
                UnclearDataType::Integer => $unclearIntCount++,
                UnclearDataType::String=> $unclearStringCount++,
                UnclearDataType::Array => $unclearArrayCount++,
            };
        }
        $unclearInt = $unclearIntCount > 1;
        $unclearString = $unclearStringCount > 1;
        $unclearArray = $unclearArrayCount > 1;
        $structDataTypeCollection = new StructDataTypeCollection(
            $unclearInt,
            $unclearString,
            $unclearArray,
            $structDataTypes,
        );
        return $structDataTypeCollection;
    }

    /**
     * @param array<string> $dataTypes
     * @return array<StructDataType>
     */
    protected static function _buildStructDataTypes(array $dataTypes): array
    {
        $structDataTypes = [];
        foreach ($dataTypes as $dataType) {
            $structDataTypes[] = self::_buildStructDataType($dataType);
        }
        return $structDataTypes;
    }

    protected static function _buildStructDataType(string $dataType): StructDataType
    {
        $underlyingDataType = StructDataTypeHelper::findUnderlyingDataType($dataType);
        $unclearDataType = StructDataTypeHelper::findUnclearType($underlyingDataType);
        $className = null;
        if (self::_addClassName($underlyingDataType) === true) {
            $className = $dataType;
        }
        $structDataType = new StructDataType(
            $underlyingDataType,
            $unclearDataType,
            $className,
        );
        return $structDataType;
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
