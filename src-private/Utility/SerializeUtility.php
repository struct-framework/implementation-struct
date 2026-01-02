<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Utility;

use function array_is_list;
use DateTimeInterface;
use Exception\Unexpected\UnexpectedException;
use function gettype;
use function is_a;
use function is_array;
use function is_object;
use ReflectionClass;
use ReflectionException;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Private\Helper\TransformHelper;
use UnitEnum;
use DataType\Contracts\DataTypeInterface as NewDataTypeInterface;

class SerializeUtility
{
    /**
     * @return array<mixed>
     */
    public function serialize(StructInterface|StructCollectionInterface $structure, ?KeyConvert $keyConvert): array
    {
        if ($structure instanceof StructInterface) {
            $serializedData = $this->_serialize($structure, $keyConvert);
        }
        if ($structure instanceof StructCollectionInterface) {
            /** @var array<mixed> $serializedData */
            $serializedData = $this->formatComplexValue($structure, $keyConvert);
        }
        return $serializedData;
    }

    /**
     * @return array<mixed>
     */
    public function _serialize(StructInterface $structure, ?KeyConvert $keyConvert): array
    {
        $serializedData = [];

        $propertyNames = $this->readPropertyNames($structure);
        foreach ($propertyNames as $propertyName) {
            $value = $structure->$propertyName; // @phpstan-ignore-line
            $formattedValue = $this->formatValue($value, $keyConvert);
            if ($formattedValue === null) {
                continue;
            }
            $arrayKey = CaseStyleUtility::buildArrayKeyFromPropertyName($propertyName, $keyConvert);
            $serializedData[$arrayKey] = $formattedValue;
        }

        return $serializedData;
    }

    /**
     * @return string[]
     */
    protected function readPropertyNames(StructInterface $structure): array
    {
        $propertyNames = [];
        try {
            $reflection = new ReflectionClass($structure);
            // @phpstan-ignore-next-line
        } catch (ReflectionException $exception) {
            throw new UnexpectedException(651559371, $exception);
        }
        $reflectionProperties = $reflection->getProperties();
        foreach ($reflectionProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            if ($reflectionProperty->isPublic() === false) {
                throw new InvalidStructException('The property <' . $propertyName . '> must be public', 1651559697);
            }
            $propertyNames[] = $propertyName;
        }
        return $propertyNames;
    }

    protected function formatValue(mixed $value, ?KeyConvert $keyConvert): mixed
    {
        $type = gettype($value);
        if ($value === null) {
            return null;
        }

        if (
            $type === 'boolean' ||
            $type === 'integer' ||
            $type === 'double' ||
            $type === 'string'
        ) {
            return $value;
        }

        return $this->formatComplexValue($value, $keyConvert);
    }

    protected function formatComplexValue(mixed $value, ?KeyConvert $keyConvert): mixed
    {
        if (is_array($value)) {
            return $this->formatArrayValue($value, $keyConvert);
        }
        if ($value instanceof StructCollectionInterface) {
            return $this->formatArrayValue($value->getValues(), $keyConvert);
        }
        if ($value instanceof UnitEnum) {
            return TransformHelper::formatEnum($value);
        }

        if (is_object($value)) {
            return $this->formatObjectValue($value, $keyConvert);
        }

        throw new InvalidStructException('The type of value is not supported', 1651515873);
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    protected function formatArrayValue(array $value, ?KeyConvert $keyConvert): array
    {
        $isList = array_is_list($value);
        $values = [];
        foreach ($value as $key => $item) {
            if ($isList) {
                $values[] = $this->formatValue($item, $keyConvert);
            } else {
                $values[$key] = $this->formatValue($item, $keyConvert);
            }
        }
        return $values;
    }

    /**
     * @param object $value
     * @return array<mixed>|string
     */
    protected function formatObjectValue(object $value, ?KeyConvert $keyConvert): array|string
    {
        if (is_a($value, DateTimeInterface::class)) {
            return TransformHelper::formatDateTime($value);
        }
        if (is_a($value, StructInterface::class)) {
            return $this->_serialize($value, $keyConvert);
        }
        if (is_a($value, DataTypeInterface::class)) {
            return $value->serializeToString();
        }
        if (is_a($value, NewDataTypeInterface::class)) {
            return $value->serializeToString();
        }
        throw new InvalidStructException('The type of value is not supported', 1651521990);
    }
}
