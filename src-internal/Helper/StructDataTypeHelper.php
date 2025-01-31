<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Helper;

use LogicException;
use Struct\Contracts\DataTypeInterface;
use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\NamedType;
use Struct\Struct\Internal\Struct\StructSignature\StructBaseDataType;

/**
 * @internal
 */
class StructDataTypeHelper
{
    public static function findDataType(string|NamedType $dataTyperOrNamedType): StructBaseDataType
    {
        $dataType = $dataTyperOrNamedType;
        if ($dataTyperOrNamedType instanceof NamedType) {
            $dataType = $dataTyperOrNamedType->dataType;
        }
        if (is_a($dataType, \UnitEnum::class, true) === true) {
            return StructBaseDataType::Enum;
        }
        if (is_a($dataType, DataTypeInterface::class, true) === true) {
            return StructBaseDataType::DataType;
        }
        if (is_a($dataType, StructInterface::class, true) === true) {
            return StructBaseDataType::Struct;
        }
        if (is_a($dataType, \DateTimeInterface::class, true) === true) {
            return StructBaseDataType::DateTime;
        }
        if ($dataType === 'array') {
            return StructBaseDataType::Array;
        }
        if ($dataType === 'bool') {
            return StructBaseDataType::Boolean;
        }
        if ($dataType === 'string') {
            return StructBaseDataType::String;
        }
        if ($dataType === 'int') {
            return StructBaseDataType::Integer;
        }
        if ($dataType === 'float') {
            return StructBaseDataType::Double;
        }
        throw new LogicException('The type: ' . $dataType . ' is not supported', 1738258655);
    }
}
