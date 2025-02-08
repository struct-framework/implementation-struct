<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

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
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Exception\SerializeException;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Internal\Helper\FormatHelper;
use UnitEnum;

/**
 * @internal
 */
class SerializeUtility
{
    /**
     * @return array<mixed>
     */
    public function serialize(StructInterface $structure, ?KeyConvert $keyConvert): array
    {
        $serializedData = $this->_serialize($structure, $keyConvert);
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
            try {
                $formattedValue = $this->formatValue($value, $keyConvert);
            } catch (SerializeException $serializeException) {
                throw new SerializeException(1724534315, $structure::class, null, $serializeException);
            }

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
                throw new InvalidStructException(1738341233, 'The property <' . $propertyName . '> must be public');
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
        if ($value instanceof UnitEnum) {
            return FormatHelper::formatEnum($value);
        }
        if (is_object($value)) {
            return $this->formatObjectValue($value, $keyConvert);
        }
        throw new SerializeException(1724534215, null, 'The type of value is not supported');
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
            return FormatHelper::formatDateTime($value);
        }
        if (is_a($value, StructInterface::class)) {
            return $this->_serialize($value, $keyConvert);
        }
        if (is_a($value, DataTypeInterface::class)) {
            try {
                return $value->serializeToString();
            } catch (\Throwable $exception) {
                throw new SerializeException(1724533985, $value::class, 'Can not serialize DataType', $exception);
            }
        }
        throw new SerializeException(1724533843, $value::class, 'The type of value is not supported');
    }
}
