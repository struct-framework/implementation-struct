<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\DataTypeInterfaceWritable;

class DataTypeFactory
{
    /**
     * @template T of DataTypeInterface
     * @param  class-string<T> $type
     * @return T
     */
    public static function create(string $type, string $serializedData): DataTypeInterface
    {
        $model = new $type($serializedData);
        return $model;
    }
}
