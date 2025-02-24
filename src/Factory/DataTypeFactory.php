<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\DataTypeInterface;
use Struct\Exception\DeserializeException;
use Struct\Exception\InvalidStructException;

class DataTypeFactory
{
    /**
     * @template T of DataTypeInterface
     * @param class-string<T> $typeClassName
     * @return T
     */
    public static function create(string $typeClassName, string $serializedData): DataTypeInterface
    {
        if(is_a($typeClassName, DataTypeInterface::class, true) === false) {
            throw new InvalidStructException(1740333906, 'Can not build: ' . $typeClassName . ' must implement <'.DataTypeInterface::class.'>');
        }
        try {
            $dataType = new $typeClassName($serializedData);
        } catch (DeserializeException $exception) {
            throw new InvalidStructException(1740333769, 'Can not build: ' . $typeClassName, $exception);
        }
        return $dataType;
    }
}
