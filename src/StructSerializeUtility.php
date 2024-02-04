<?php

declare(strict_types=1);

namespace Struct\Struct;

use Exception\Unexpected\UnexpectedException;
use Struct\Contracts\StructCollection;
use Struct\Contracts\StructCollectionInterface;
use Struct\Contracts\StructInterface;
use Struct\Struct\Enum\KeyConvert;
use Struct\Struct\Private\Utility\DeserializeUtility;
use Struct\Struct\Private\Utility\SerializeUtility;

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
     * @template T of StructInterface
     * @param object|array<mixed> $data
     * @param class-string<T> $type
     * @return T
     */
    public static function deserialize(object|array $data, string $type, ?KeyConvert $keyConvert = null): StructInterface
    {
        $unSerializeUtility = new DeserializeUtility();
        return $unSerializeUtility->deserialize($data, $type, $keyConvert);
    }

    /**
     * @template T of StructCollectionInterface
     * @param object|array<mixed> $data
     * @param class-string<StructInterface> $itemType
     * @param KeyConvert|null $keyConvert
     * @param class-string<T> $collectionType
     * @return T
     */
    public static function deserializeStructCollection(object|array $data, string $itemType, ?KeyConvert $keyConvert = null, string $collectionType = StructCollection::class): StructCollectionInterface
    {
        $unSerializeUtility = new DeserializeUtility();
        return $unSerializeUtility->_deserializeCollection($data, $itemType, $keyConvert, $collectionType);
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
     * @template T of StructInterface
     * @param string $dataJson
     * @param class-string<T> $type
     * @param KeyConvert|null $keyConvert
     * @return T
     */
    public static function deserializeFromJson(string $dataJson, string $type, ?KeyConvert $keyConvert = null): StructInterface
    {
        try {
            /** @var mixed[] $dataArray */
            $dataArray = \json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \LogicException('Can not parse the given JSON string', 1675972764, $exception);
        }
        return self::deserialize($dataArray, $type, $keyConvert);
    }

    /**
     * @template T of StructCollectionInterface
     * @param string $dataJson
     * @param class-string<StructInterface> $itemType
     * @param KeyConvert|null $keyConvert
     * @param class-string<T> $collectionType
     * @return T
     */
    public static function deserializeCollectionFromJson(string $dataJson, string $itemType, ?KeyConvert $keyConvert = null, string $collectionType = StructCollection::class): StructCollectionInterface
    {
        try {
            /** @var mixed[] $dataArray */
            $dataArray = \json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \LogicException('Can not parse the given JSON string', 1675972764, $exception);
        }
        return self::deserializeStructCollection($dataArray, $itemType, $keyConvert, $collectionType);
    }
}
