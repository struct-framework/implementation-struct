<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct;

use Struct\Attribute\ArrayList;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;

/**
 * @internal
 */
readonly class StructSignature
{
    public function __construct(
        public string $structName,
        public bool $isReadOnly,
        /**
         * @var array<StructElement>
         */
        #[ArrayList(StructElement::class)]
        public array $structElements,
    ) {
    }
}
