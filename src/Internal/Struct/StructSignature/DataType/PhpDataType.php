<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

/**
 * @internal
 */
enum PhpDataType
{
    case Boolean;
    case Integer;
    case Float;
    case String;
    case Array;
    case ArrayList;
}
