<?php

declare(strict_types=1);

namespace Struct\Struct\Internal\Utility;

use function ctype_upper;
use function strtolower;
use Struct\Struct\Enum\KeyConvert;

/**
 * @internal
 */
class CaseStyleUtility
{
    public static function lowerCamelToSnake(string $string): string
    {
        $stringOutput = '';
        for ($index = 0; $index < strlen($string); $index++) {
            $character = $string[$index];
            if (ctype_upper($character) === true) {
                $stringOutput .= '-' . strtolower($character);
            } else {
                $stringOutput .= $character;
            }
        }
        return $stringOutput;
    }

    public static function snakeToLowerCamel(string $string): string
    {
        $stringLowerCase = strtolower($string);
        $stringUnderscoresReplacedWithSpace = str_replace('_', ' ', $stringLowerCase);
        $stringCapitalized = ucwords($stringUnderscoresReplacedWithSpace);
        $stringCapitalizedNoSpaces = str_replace(' ', '', $stringCapitalized);
        $stringLowerCamelCase = lcfirst($stringCapitalizedNoSpaces);
        return $stringLowerCamelCase;
    }

    public static function buildArrayKeyFromPropertyName(string $propertyName, ?KeyConvert $keyConvert): string
    {
        if ($keyConvert === null) {
            return $propertyName;
        }
        $propertyNameInArray = match ($keyConvert) {
            KeyConvert::snakeCase => self::lowerCamelToSnake($propertyName)
        };
        return $propertyNameInArray;
    }
}
