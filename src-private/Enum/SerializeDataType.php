<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Enum;

enum SerializeDataType: string
{
    case NullType = 'null';
    case StructureType = 'Structure';
    case StructCollection =  'StructCollection';
    case ArrayType = 'array';
    case EnumType = 'enum';
    case DataType = 'DataType';
    case BuildInType = 'default';
}
