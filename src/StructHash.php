<?php

declare(strict_types=1);

namespace Struct\Struct;

use Struct\Contracts\StructInterface;
use Struct\Struct\Private\Utility\HashUtility;

class StructHash
{
    public static function buildHash(StructInterface $structure): string
    {
        $hashUtility = new HashUtility();
        $hashString = $hashUtility->buildHashString($structure);
        return \hash('sha512', $hashString, true);
    }
}