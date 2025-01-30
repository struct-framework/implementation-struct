<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use BackedEnum;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use LogicException;
use ReflectionUtility;
use Stringable;
use Struct\Attribute\ArrayKeyList;
use Struct\Attribute\ArrayList;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\TransformException;
use Struct\Reflection\Internal\Struct\ObjectSignature;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parameter;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Internal\Enum\StructDataType;
use Struct\Struct\Internal\Helper\FormatHelper;
use UnitEnum;
use function array_key_exists;
use function is_array;
use function is_object;

/**
 * @internal
 */
class DeserializeUtility
{
    /**
     * @template T of StructInterface
     * @param array<mixed>|Object $data
     * @param class-string<T> $type
     * @return T
     */
    public function deserialize(array|object $data, string $structName, ?KeyConvert $keyConvert): StructInterface
    {
        if (is_a($structName, StructInterface::class, true) === false) {
            throw new LogicException('The type: ' . $structName . ' must implement <' . StructInterface::class . '>', 1737885869);
        }

        $type = new NamedType($structName, false);
        $structure = $this->_deserializeStruct($data, $type, $keyConvert);
        return $structure;
    }

    protected function _deserialize(mixed $data, NamedType $type, Parameter $parameter, ?KeyConvert $keyConvert): mixed
    {
        $dataType = $this->_findDataType($data, $type);
        $result = match ($dataType) {
            StructDataType::Struct  => $this->_deserializeStruct($data, $type, $keyConvert), // @phpstan-ignore-line
            StructDataType::NULL => $this->parseNull($parameter),
            StructDataType::Enum => $this->_deserializeEnum($data, $type),
            StructDataType::Array => $this->_deserializeArray($data, $parameter, $keyConvert),
            StructDataType::DataType => $this->_deserializeDataType($data, $parameter), // @phpstan-ignore-line
            StructDataType::String, StructDataType::DateTime, StructDataType::Double, StructDataType::Integer, StructDataType::Boolean => $this->_deserializeBuildIn($data, $type, $parameter),
        };
        return $result;
    }

    /**
     * @template T of StructInterface
     * @param class-string<T> $type
     * @return T
     */
    protected function _deserializeStruct(mixed $data, NamedType $type, ?KeyConvert $keyConvert): StructInterface
    {
        $dataArray = $this->_transformObjectToArray($data);
        $structSignature = ReflectionUtility::readObjectSignature($type->dataType);
        $properties = $structSignature->properties;

        $values = [];
        foreach ($properties as $property) {
            $parameter = $property->parameter;
            $propertyName = $parameter->name;
            $value = null;
            $arrayKey = CaseStyleUtility::buildArrayKeyFromPropertyName($propertyName, $keyConvert);
            if (array_key_exists($arrayKey, $dataArray) === true) {
                $value = $dataArray[$arrayKey];
            }
            $mostMatchingType = $this->_readMostMatchingType($parameter);
            $values[$propertyName] = $this->_deserialize($value, $mostMatchingType, $parameter, $keyConvert);
        }

        $struct = $this->_buildStruct($structSignature, $values);
        return $struct;
    }

    protected function _buildStruct(ObjectSignature $structSignature, array $values): StructInterface
    {
        $structName = $structSignature->objectName;
        if ($structSignature->isReadOnly === true) {
            $struct = new $structName(...$values);
            return $struct;
        }
        $structure = new $structName();
        foreach ($values as $propertyName => $value) {
            $structure->$propertyName = $value;  // @phpstan-ignore-line
        }
        return $structure;
    }

    protected function _findDataType(mixed $data, NamedType $type): StructDataType
    {
        if ($data === null) {
            return StructDataType::NULL;
        }
        $dataType = $type->dataType;
        if (is_a($dataType, UnitEnum::class, true) === true) {
            return StructDataType::Enum;
        }
        if (is_a($dataType, DataTypeInterface::class, true) === true) {
            return StructDataType::DataType;
        }
        if (is_a($dataType, StructInterface::class, true) === true) {
            return StructDataType::Struct;
        }
        if (is_a($dataType, DateTimeInterface::class, true) === true) {
            return StructDataType::DateTime;
        }
        if ($dataType === 'array') {
            return StructDataType::Array;
        }
        if ($dataType === 'bool') {
            return StructDataType::Boolean;
        }
        if ($dataType === 'string') {
            return StructDataType::String;
        }
        if ($dataType === 'int') {
            return StructDataType::Integer;
        }
        if ($dataType === 'float') {
            return StructDataType::Double;
        }
        throw new LogicException('The type: ' . $dataType . ' is not supported', 1737881559);
    }

    protected function _deserializeEnum(mixed $data, NamedType $type): UnitEnum
    {
        if (is_string($data) === false && is_int($data) === false) {
            throw new LogicException('The value for <' . $data . '> must be string or int', 1652900283);
        }

        if (is_a($type->dataType, BackedEnum::class, true) === true) {
            $enum = $type->dataType::tryFrom($data);
            if ($enum === null) {
                throw new LogicException('The value <' . $data . '> is not allowed for Enum <' . $type . '>', 1652900286);
            }
            return $enum;
        }
        $cases = $type::cases();
        /** @var UnitEnum $case */
        foreach ($cases as $case) {
            if ($case->name === $data) {
                return $case;
            }
        }
        throw new LogicException('The value <' . $data . '> is not allowed for Enum <' . $type . '>', 1652899974);
    }

    /**
     * @param mixed $data
     * @return array<mixed>
     */
    protected function _transformObjectToArray(mixed $data): array
    {
        if (is_array($data) === true) {
            return $data;
        }
        if (
            is_object($data) === true &&
            is_a($data, DateTimeInterface::class) === false
        ) {
            $dataArray = [];
            $dataArrayTransform = (array) $data;
            foreach ($dataArrayTransform as $key => $value) {
                if (is_a($value, DateTimeInterface::class)) {
                    $value = FormatHelper::formatDateTime($value);
                }
                if ($value instanceof UnitEnum) {
                    $value = FormatHelper::formatEnum($value);
                }
                $dataArray[$key] = $value;
            }
            return $dataArray;
        }
        throw new UnexpectedException(1676979096);
    }

    protected function _deserializeDataType(string|Stringable $serializedData, Parameter $parameter): DataTypeInterface
    {
        $serializedData = (string) $serializedData;
        $type = $this->_readMostMatchingType($parameter);
        $dataType = DataTypeFactory::create($type->dataType, $serializedData);
        return $dataType;
    }

    protected function _readMostMatchingType(Parameter $parameter): NamedType
    {
        if (count($parameter->types) === 0) {
            throw new LogicException('The parameter <' . $parameter->name . '> must have at least one type', 1737881057);
        }
        $firstType = $parameter->types[0];
        if ($firstType instanceof NamedType === false) {
            throw new LogicException('The parameter <' . $parameter->name . '> must have intersection type', 1737881126);
        }
        return $firstType;
    }

    /**
     * @return array<mixed>
     */
    protected function _deserializeArray(mixed $dataArray, Parameter $parameter, ?KeyConvert $keyConvert): array
    {
        if (is_array($dataArray) === false) {
            throw new UnexpectedException(1675967242);
        }
        // Return mixed arrays as is.
        if ($parameter->type === 'mixed') {
            return $dataArray;
        }

        $arrayType = $this->_findArrayType($parameter);
        $type = $arrayType[0];
        $isArrayKeyList = $arrayType[1];
        $parsedOutput = $this->_buildArray($dataArray, $parameter, $type, $isArrayKeyList, $keyConvert);
        return $parsedOutput;
    }

    protected function _findArrayType(Parameter $parameter): array
    {
        $isArrayKeyList = false;
        $arguments = null;

        foreach ($parameter->attributes as $attribute) {
            if ($attribute->name === ArrayKeyList::class) {
                $isArrayKeyList = true;
                $arguments = $attribute->arguments;
            }
            if ($attribute->name === ArrayList::class) {
                $isArrayKeyList = true;
                $arguments = $attribute->arguments;
            }
        }
        if ($arguments === null || count($arguments) === 0) {
            throw new LogicException('The array arguments must have an type', 1737883497);
        }
        $valueType = $arguments[0];
        $type = new NamedType($valueType, true);
        return [
            $type,
            $isArrayKeyList,
        ];
    }

    /**
     * @param array<mixed> $dataArray
     * @return array<mixed>
     */
    protected function _buildArray(array $dataArray, Parameter $parameter, NamedType $type, bool $isArrayKeyList, ?KeyConvert $keyConvert): array
    {
        $parsedOutput = [];
        foreach ($dataArray as $key => $value) {
            $valueToSet = $value;
            if ($type->dataType !== 'mixed') {
                $valueToSet = $this->_deserialize($value, $type, $parameter, $keyConvert);
            }
            if ($isArrayKeyList === true) {
                $parsedOutput[$key] = $valueToSet;
            } else {
                $parsedOutput[] = $valueToSet;
            }
        }
        return $parsedOutput;
    }

    protected function _deserializeBuildIn(mixed $value, NamedType $type, Parameter $parameter): mixed
    {
        try {
            return FormatHelper::formatBuildIn($value, $type);
        } catch (TransformException $transformException) {
            throw new LogicException('Can not transform property <' . $parameter->name . '>', 1652190689, $transformException);
        }
    }

    protected function parseNull(Parameter $parameter): mixed
    {
        if ($parameter->isAllowsNull === true) {
            return null;
        }
        if ($parameter->hasDefaultValue === true) {
            return $parameter->defaultValue;
        }
        throw new LogicException('No value for <' . $parameter->name . '> found', 1675967217);
    }
}
