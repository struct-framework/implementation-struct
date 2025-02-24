<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Validator;

use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Visibility;
use Struct\Reflection\Internal\Struct\ObjectSignature\Property;
use Struct\Exception\InvalidStructException;

/**
 * @internal
 */
class PropertyValidator
{
    public static function validate(string $structName, Property $property): void
    {
        if ($property->visibility !== Visibility::public) {
            throw new InvalidStructException(1738257241, $property->parameter->name . ' Property must be public');
        }
    }
}
