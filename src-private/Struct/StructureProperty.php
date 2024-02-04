<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct;

class StructureProperty
{
    public string $name = '';
    public string $type = '';
    public bool $allowsNull = false;
    public bool $isBuiltin = false;

    public bool $hasDefaultValue = false;
    public mixed $defaultValue;
}
