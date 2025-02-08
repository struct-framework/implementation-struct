<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature\DataType;

/**
 * @internal
 */
enum StructUnderlyingDataType: string
{
    case Boolean          = '19731516-ce69-4e63-b58f-6b870e86f713';
    case Integer          = '5f708564-715f-4e6b-aca3-52bee90f4cfc';
    case Float            = '517d7201-4fc9-47fd-8740-9e22fd9cd9a1';
    case String           = 'cb9750f4-f1e9-4da1-a3c3-91633743b0b8';
    case Enum             = 'b823236c-0554-4964-a7cb-502e07e56f3e';
    case EnumString       = 'dd1787ae-9145-44c9-b5af-784332337ebf';
    case EnumInt          = 'f2d3e3c4-e715-43b7-81ec-f0f1bf09a78d';
    case Array            = '11baab65-1824-4634-b7a0-32d3f22a804a';
    case ArrayList        = '3e8caf87-bcdf-4fd7-b45a-f319c6c0eefb';

    case DateTime         = 'b7568468-3680-47de-bbf3-67f545f7c364';
    case DataType         = '2f78af1a-07da-4db9-be3b-4f6799a6640c';
    case Struct           = '5b659c66-fc64-4d83-a99f-f4ad40cc0fbd';
}
