<?php

declare(strict_types=1);

namespace Struct\Struct\Private\Struct\ObjectStruct;

use Struct\Struct\Private\Struct\ObjectStruct\Parts\Visibility;

readonly class Property
{
    public function __construct(
        public Parameter $parameter,
        public ?bool $isReadOnly,
        public ?Visibility $visibility,
        public ?bool $isStatic,
    ) {
    }
}
