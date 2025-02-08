<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;


use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataTypeCollection;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructUnderlyingArrayType;

/**
 * @internal
 */
readonly class StructElementArray
{

    public function __construct(
        public StructUnderlyingArrayType $structUnderlyingArrayType,
        public ?StructDataTypeCollection $structDataTypeCollection,
    ) {
    }
}
