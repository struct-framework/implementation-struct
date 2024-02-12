<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct;

class PropertyReflection
{
    public string $name = '';
    public string $type = '';
    public bool $isAllowsNull = false;
    public bool $isBuiltin = false;
    public bool $isHasDefaultValue = false;
    public mixed $defaultValue;
    public ?string $structTypeOfArrayOrCollection = null;
    public bool $isArrayKeyList = false;
}
