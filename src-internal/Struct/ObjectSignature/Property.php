<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\ObjectSignature;

use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Visibility;

/**
 * @internal
 */
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
