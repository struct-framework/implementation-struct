<?php

declare(strict_types=1);

namespace Struct\Struct\Enum;

enum HashAlgorithm: string
{
    case MD5 = 'md5';
    case SHA1 = 'sha1';
    case SHA2 = 'sha256';
    case SHA3 = 'sha3-512';
}
