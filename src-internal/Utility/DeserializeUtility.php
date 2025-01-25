<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use function array_key_exists;
use BackedEnum;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use function is_array;
use function is_object;
use LogicException;
use Stringable;
use Struct\Contracts\DataTypeInterfaceWritable;
use Struct\Contracts\StructInterface;
use Struct\Exception\TransformException;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Factory\DataTypeFactory;
use Struct\Struct\Internal\Enum\SerializeDataType;
use Struct\Struct\Internal\Helper\FormatHelper;
use Struct\Struct\Internal\Struct\StructSignature\Parameter;
use Struct\Struct\StructHashUtility;
use UnitEnum;

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
    public function deserialize(array|object $data, string $type, ?KeyConvert $keyConvert): StructInterface
    {
        $structure = $this->_deserializeStructure($data, $type, $keyConvert);
        return $structure;
    }

    protected function _deserialize(mixed $data, string $type, Parameter $parameter, ?KeyConvert $keyConvert): mixed
    {
        $dataType = $this->_findDataType($data, $type);
        $result = match ($dataType) {
            SerializeDataType::StructureType  => $this->_deserializeStructure($data, $type, $keyConvert), // @phpstan-ignore-line
            SerializeDataType::NullType => $this->parseNull($parameter),
            SerializeDataType::EnumType => $this->_deserializeEnum($data, $type),
            SerializeDataType::ArrayType => $this->_deserializeArray($data, $parameter, $keyConvert),
            SerializeDataType::DataType => $this->_deserializeDataType($data, $parameter), // @phpstan-ignore-line
            SerializeDataType::BuildInType => $this->_deserializeBuildIn($data, $type, $parameter),
        };
        return $result;
    }

    /**
     * @template T of StructInterface
     * @param class-string<T> $type
     * @return T
     */
    protected function _deserializeStructure(mixed $data, string $type, ?KeyConvert $keyConvert): StructInterface
    {
        $dataArray = $this->_transformObjectToArray($data);
        $structSignature = StructHashUtility::generateStructSignature($type);
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
            $values[$propertyName] = $this->_deserialize($value, $parameter->type, $parameter, $keyConvert);
        }
        $structure = new $type();
        foreach ($values as $propertyName => $value) {
            $structure->$propertyName = $value;  // @phpstan-ignore-line
        }
        return $structure;
    }

    protected function _findDataType(mixed $data, string $type): SerializeDataType
    {
        if ($data === null) {
            return SerializeDataType::NullType;
        }
        if (is_a($type, UnitEnum::class, true) === true) {
            return SerializeDataType::EnumType;
        }
        if (is_a($type, DataTypeInterfaceWritable::class, true) === true) {
            return SerializeDataType::DataType;
        }
        if (is_a($type, StructInterface::class, true) === true) {
            return SerializeDataType::StructureType;
        }
        if (is_a($type, StructCollectionInterface::class, true) === true) {
            return SerializeDataType::StructCollection;
        }
        if ($type === 'array') {
            return SerializeDataType::ArrayType;
        }
        return SerializeDataType::BuildInType;
    }

    protected function _deserializeEnum(mixed $data, string $type): UnitEnum
    {
        if (is_string($data) === false && is_int($data) === false) {
            throw new LogicException('The value for <' . $data . '> must be string or int', 1652900283);
        }

        if (is_a($type, BackedEnum::class, true) === true) {
            $enum = $type::tryFrom($data);
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

    protected function _deserializeDataType(string|Stringable $serializedData, Parameter $parameter): DataTypeInterfaceWritable
    {
        $serializedData = (string) $serializedData;
        /** @var class-string<DataTypeInterfaceWritable> $type */
        $type = $parameter->type;
        $dataType = DataTypeFactory::create($type, $serializedData);
        return $dataType;
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
        /** @var string $type */
        $type = $parameter->arrayType;
        $isArrayKeyList = $parameter->isArrayKeyList;
        $parsedOutput = $this->_buildArray($dataArray, $parameter, $type, $isArrayKeyList, $keyConvert);
        return $parsedOutput;
    }

    /**
     * @param array<mixed> $dataArray
     * @return array<mixed>
     */
    protected function _buildArray(array $dataArray, Parameter $parameter, string $type, bool $isArrayKeyList, ?KeyConvert $keyConvert): array
    {
        $parsedOutput = [];
        foreach ($dataArray as $key => $value) {
            $valueToSet = $value;
            if ($type !== 'mixed') {
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

    protected function _deserializeBuildIn(mixed $value, string $type, Parameter $parameter): mixed
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
