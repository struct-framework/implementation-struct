<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\DataTypeInterface;
use Struct\Exception\DeserializeException;
use Struct\Exception\InvalidValueException;

class DataTypeFactory
{
    /**
     * @template T of DataTypeInterface
     * @param  class-string<T> $typeClassName
     * @return T
     */
    public static function create(string $typeClassName, string $serializedData): DataTypeInterface
    {
        try {
            $dataType = new $typeClassName($serializedData);
        } catch (DeserializeException $exception) {
            throw new InvalidValueException($exception);
        }
        return $dataType;
    }
}
