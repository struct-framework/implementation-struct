<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

/**
 * @internal
 */
enum StructBaseDataType: string
{
    case NULL             = '8b44ad0e-1b6f-4562-b975-87c9963a8841';
    case Boolean          = '19731516-ce69-4e63-b58f-6b870e86f713';
    case Integer          = '5f708564-715f-4e6b-aca3-52bee90f4cfc';
    case Double           = '517d7201-4fc9-47fd-8740-9e22fd9cd9a1';
    case String           = 'cb9750f4-f1e9-4da1-a3c3-91633743b0b8';
    case Enum             = 'b823236c-0554-4964-a7cb-502e07e56f3e';
    case DateTime         = 'b7568468-3680-47de-bbf3-67f545f7c364';
    case Array            = '11baab65-1824-4634-b7a0-32d3f22a804a';
    case DataType         = '2f78af1a-07da-4db9-be3b-4f6799a6640c';
    case Struct           = '5b659c66-fc64-4d83-a99f-f4ad40cc0fbd';
}
