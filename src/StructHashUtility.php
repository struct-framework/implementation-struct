<?php

declare(strict_types=1);

namespace Struct\Struct;

use Struct\Contracts\StructInterface;
use Struct\Struct\Internal\Struct\StructSignature\DataType\StructDataType;
use Struct\Struct\Internal\Struct\StructSignature\StructElement;
use Struct\Struct\Internal\Struct\StructSignature\StructElementArray;

class StructHashUtility
{
    /**
     * @param class-string<StructInterface>|StructInterface $structNameOrStruct
     */
    public static function signatureHash(StructInterface|string $structNameOrStruct): string
    {
        $signature = StructReflectionUtility::readSignature($structNameOrStruct);
        $elementSignatures = self::buildElementsSignature($signature->structElements);
        $hash = self::buildHash($elementSignatures);
        return $hash;
    }

    protected static function buildHash(string $data): string
    {
        $hash = hash('sha1', $data);
        return $hash;
    }

    /**
     * @param array<StructElement> $structElements
     * @return string
     */
    protected static function buildElementsSignature(array $structElements): string
    {
        foreach ($structElements as $structElement) {
            $name = $structElement->name;
            if ($structElement->isAllowsNull === true) {
                $name .= 'a78bfb14-7da9-4d7d-891f-b48b55c282cd';
            }
            $types = '';
            $types .= self::buildStructElementArray($structElement->structElementArray);
            $types .= self::buildStructDataTypeCollection($structElement->structDataTypeCollection->structDataTypes);
            $elementSignature = $name . ':' . $types;
            $elementSignatures .= $elementSignature;
        }
        return $elementSignatures;
    }

    protected static function buildStructElementArray(?StructElementArray $structElementArray): string
    {
        if ($structElementArray === null) {
            return '';
        }
        $types = $structElementArray->structUnderlyingArrayType->value;
        if ($structElementArray->structDataTypeCollection === null) {
            return $types;
        }
        $types .= '<';
        $types .= self::buildStructDataTypeCollection($structElementArray->structDataTypeCollection->structDataTypes);
        $types .= '>';
        return $types;
    }

    /**
     * @param array<StructDataType> $structDataTypes
     * @return string
     */
    protected static function buildStructDataTypeCollection(array $structDataTypes): string
    {
        $types = '';
        foreach ($structDataTypes as $structDataType) {
            $className = $structDataType->className;
            $types .= $structDataType->structUnderlyingDataType->value;
            if ($className !== null) {
                $types .= '<' . $className . '>';
            }
        }
        return $types;
    }
}
