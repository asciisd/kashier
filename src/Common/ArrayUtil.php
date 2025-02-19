<?php

namespace Asciisd\Kashier\Common;

/**
 * Class ArrayUtil
 * Util Class for Arrays
 *
 * @package Asciisd\Kashier\Common
 */
class ArrayUtil
{
    /**
     *
     * @param array $arr
     * @return true if $arr is an associative array
     */
    public static function isAssocArray(array $arr): bool
    {
        foreach ($arr as $k => $v) {
            if (is_int($k)) {
                return false;
            }
        }
        return true;
    }
}
