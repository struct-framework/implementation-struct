<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Struct\StructSignature;

/**
 * @internal
 */
readonly class Parameter
{
    public function __construct(
        public string $type,
        public bool $isBuiltin,
        public string $name,
        public bool $isAllowsNull,
        public bool $hasDefaultValue,
        public mixed $defaultValue,
        public ?bool $isArrayKeyList,
        public ?string $arrayType,
    ) {
    }
}
