<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

use Struct\Reflection\Internal\Struct\ObjectSignature\Value;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;

/**
 * @internal
 */
readonly class StructElement
{
    public function __construct(
        public string $name,
        public bool $isAllowsNull,
        public ?Value $defaultValue,
        public StructDataTypeCollection $structDataTypeCollection,
        public ?StructElementArray $structElementArray,
    ) {
    }
}
