<?php

declare(strict_types=1);

namespace Zoghal\IrnicApi;

class Arrays
{
    /**
     * Flattens a nested array into a single-level array.
     *
     * @param array $array The array to be flattened.
     * @return array The flattened array.
     */
    public static function flatten($array)
    {
        $out_array = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $rec_array = self::flatten($value);

                if (is_numeric($key)) {
                    $out_array[$key] = $rec_array;
                } else {
                    foreach ($rec_array as $rec_key => $rec_value) {
                        if (is_numeric($rec_key)) {
                            $out_array[$key][] = $rec_value;
                        } else {
                            $out_array["{$key}.{$rec_key}"] = $rec_value;
                        }
                    }
                }
            } else {
                $out_array[$key] = $value;
            }
        }

        return $out_array;
    }

    /**
     * Check if a key exists in the given array.
     *
     * @param string $key The key to search for.
     * @param array $array The array to search in.
     * @return bool True if the key exists, false otherwise.
     */
    public static function has_key(string $key, array $array)
    {
        $keys = array_keys($array);
        foreach ($keys as $item) {
            if (strncmp($item, $key, strlen($key)) === 0) {
                return true;
            }
        }
        return false;
    }


    /**
     * Retrieves the value associated with the given key from the array.
     *
     * If the key ends with '.*', it will return an associative array containing all the values
     * whose keys start with the given key. If the key does not exist in the array, the default
     * value will be returned.
     *
     * @param string $key The key to search for in the array.
     * @param array $array The array to search in.
     * @param mixed $default The default value to return if the key does not exist in the array. Default is null.
     * @return mixed The value associated with the key, or the default value if the key does not exist.
     */
    public static function get(string $key, array $array, $default = null)
    {
        if (!self::str_ends_with($key, '.*')) {
            if (array_key_exists($key, $array)) {
                return $array[$key];
            }
            return $default;
        }

        $out = [];
        $key = rtrim($key, '*');

        foreach ($array as $k => $v) {
            if (!self::str_starts_with($k, $key)) {
                continue;
            }
            $k = str_replace($key, '', $k);
            $out[$k] = $v;
        }
        if (empty($out)) {
            return $default;
        }
        return $out;
    }


    /**
     * Checks if a string ends with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for.
     * @return bool Returns true if the string ends with the substring, false otherwise.
     */
    private static function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }


    /**
     * Checks if a string starts with a given substring.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for.
     * @return bool Returns true if the string starts with the substring, false otherwise.
     */
    private static function str_starts_with($haystack, $needle)
    {

        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
