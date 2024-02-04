<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Private\Utility\SerializeUtility;
use Struct\Struct\Private\Utility\UnSerializeUtility;

class StructSerializeUtility
{
    /**
     * @return mixed[]
     */
    public static function serialize(StructInterface|StructCollectionInterface $structure, ?KeyConvert $keyConvert = null): array
    {
        $serializeUtility = new SerializeUtility();
        return $serializeUtility->serialize($structure, $keyConvert);
    }

    /**
     * @template T of StructInterface|StructCollectionInterface
     * @param object|array<mixed> $data
     * @param class-string<T> $type
     * @return T
     */
    public static function deserialize(object|array $data, string $type, ?KeyConvert $keyConvert = null): StructInterface|StructCollectionInterface
    {
        $unSerializeUtility = new UnSerializeUtility();
        return $unSerializeUtility->unSerialize($data, $type, $keyConvert);
    }

    public static function serializeToJson(StructInterface|StructCollectionInterface $structure, ?KeyConvert $keyConvert = null): string
    {
        $dataArray = self::serialize($structure, $keyConvert);
        $dataJson = \json_encode($dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($dataJson === false) {
            throw new UnexpectedException(1675972511);
        }
        return $dataJson;
    }

    /**
     * @template T of StructInterface|StructCollectionInterface
     * @param string $dataJson
     * @param class-string<T> $type
     * @return T
     */
    public static function deserializeFromJson(string $dataJson, string $type, ?KeyConvert $keyConvert = null): StructInterface|StructCollectionInterface
    {
        try {
            /** @var mixed[] $dataArray */
            $dataArray = \json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \LogicException('Can not parse the given JSON string', 1675972764, $exception);
        }
        return self::deserialize($dataArray, $type, $keyConvert);
    }
}
