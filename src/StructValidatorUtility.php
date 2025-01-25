<?php

declare(strict_types=1);

namespace Struct\Struct;


use Struct\Contracts\StructInterface;
use Struct\Exception\InvalidStructException;
use Struct\Struct\Internal\Struct\ObjectSignature\Parts\Visibility;

class StructValidatorUtility
{
    public function isValidStruct(StructInterface $struct): void
    {
        $signature = ReflectionUtility::readObjectSignature($struct);
        if(count($signature->methods) === 1) {
            if($signature->methods[0]->name !== '__construct') {
                throw new InvalidStructException('A struct must not have methods', 1725991614);
            }
        }
        if(count($signature->methods) > 1) {
            throw new InvalidStructException('A struct must not have methods', 1725991562);
        }
        foreach ($signature->properties as $property) {
            if($property->visibility !== Visibility::public) {
                throw new InvalidStructException('The property: ' . $property->parameter->name . ' must be public.', 1725991733);
            }
        }
    }
}
