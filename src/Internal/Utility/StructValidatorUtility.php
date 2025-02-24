<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use Struct\Contracts\StructInterface;
use Struct\Reflection\Internal\Struct\ObjectSignature;
use Struct\Reflection\Internal\Struct\ObjectSignature\Parts\Visibility;
use Struct\Reflection\ReflectionUtility;
use Struct\Exception\InvalidStructException;

/**
 * @internal
 */
class StructValidatorUtility
{
    public static function preValidate(ObjectSignature $objectSignature): void
    {
        if (is_a($objectSignature->objectName, StructInterface::class, true) === false) {
            throw new InvalidStructException(1740332782, 'A Struct class must implement <' . StructInterface::class . '>');
        }
        self::isAbstract($objectSignature);
        self::checkForMethods($objectSignature);
        self::checkProperties($objectSignature);
    }


    protected static function checkProperties(ObjectSignature $objectSignature): void
    {
        foreach ($objectSignature->properties as $property) {
            if($property->visibility !== Visibility::public) {
                throw new InvalidStructException(1740333667, 'The struct properties must not be public');
            }
        }
    }

    protected static function isAbstract(ObjectSignature $objectSignature): void
    {
        if (ReflectionUtility::isAbstract($objectSignature->objectName) === true) {
            throw new InvalidStructException(1740333437, 'Can not build signature for abstract struct');
        }
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
        throw new InvalidStructException(1740332777, 'Method not allowed in struct');
    }
}
