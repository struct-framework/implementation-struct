<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Enum;

/**
 * @internal
 */
enum SerializeDataType: string
{
    case NullType = 'null';
    case StructureType = 'Structure';
    case StructCollection =  'StructCollection';
    case ArrayType = 'array';
    case EnumType = 'enum';
    case DataType = 'DataType';
    case DateTime = 'DateTime';
    case BuildInType = 'default';
}
