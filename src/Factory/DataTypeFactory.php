<?php

declare(strict_types=1);

namespace Struct\Struct\Factory;

use Struct\Contracts\DataTypeInterfaceWritable;

class DataTypeFactory
{
    /**
     * @template T of DataTypeInterfaceWritable
     * @param  class-string<T> $type
     * @return T
     */
    public static function create(string $type, string $serializedData): DataTypeInterfaceWritable
    {
        $model = new $type();
        $model->deserializeFromString($serializedData);
        return $model;
    }
}
