<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Reflection\Internal\Struct\ObjectSignature;

/**
 * @internal
 */
class StructValidatorUtility
{
    public static function validate(ObjectSignature $objectSignature): void
    {
        if (is_a($objectSignature->objectName, StructInterface::class, true) === false) {
            throw new InvalidStructException(1738330147, 'A Struct class must implement <' . StructInterface::class . '>');
        }
        self::checkForMethods($objectSignature);
    }

    protected static function checkForMethods(ObjectSignature $objectSignature): void
    {
        $methodCount = count($objectSignature->methods);
        if ($methodCount === 0) {
            return;
        }
        if (
            $methodCount === 1 &&
            $objectSignature->methods[0]->name === '__construct'
        ) {
            return;
        }
        throw new InvalidStructException(1738330401, 'A struct must not have methods');
    }
}
