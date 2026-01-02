<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use DateTimeInterface;
use function is_a;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Struct\Private\Placeholder\Undefined;
use Struct\Struct\Private\Struct\StructureProperty;
use Struct\Struct\StructPropertyUtility;
use UnitEnum;

class StructFactory
{
    /**
     * @template T of StructInterface
     * @param  class-string<T> $structType
     * @return T
     */
    public static function create(string $structType): StructInterface
    {
        if (is_a($structType, StructInterface::class, true) === false) {
            throw new InvalidStructException('The structureType <' . $structType . '> must implement the interface <' . StructInterface::class . '>', 1675967937);
        }
        $structure = new $structType();
        $properties = StructPropertyUtility::readProperties($structure);
        foreach ($properties as $property) {
            $name = $property->name;
            $value = self::buildValue($property);
            if ($value instanceof Undefined === false) {
                $structure->$name = $value; // @phpstan-ignore-line
            }
        }
        return $structure;
    }

    /**
     * @param StructureProperty $property
     * @return mixed
     */
    protected static function buildValue(StructureProperty $property): mixed
    {
        if ($property->hasDefaultValue) {
            return $property->defaultValue;
        }
        if ($property->allowsNull) {
            return null;
        }
        $type = $property->type;
        if ($type === 'array') {
            return [];
        }
        if (is_a($type, StructCollectionInterface::class, true) === true) {
            return new $type();
        }
        if (is_a($type, StructInterface::class, true) === true) {
            return self::create($type);
        }
        if (
            $type === 'string' ||
            $type === 'int' ||
            $type === 'float' ||
            $type === 'bool' ||
            is_a($type, DateTimeInterface::class, true) === true ||
            is_a($type, DataTypeInterface::class, true) === true ||
            is_a($type, \DataType\Contracts\DataTypeInterface::class, true) === true ||
            is_a($type, UnitEnum::class, true) === true
        ) {
            $undefined = new Undefined();
            return $undefined;
        }
        throw new InvalidStructException('The type <' . $type . '> is not supported', 1675967989);
    }
}
