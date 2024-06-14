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
     * @param string $key The key to search for in the array.
     * @param array $array The array to search in.
     * @param mixed $default The default value to return if the key is not found. Default is false.
     * @return mixed The value associated with the key, or the default value if the key is not found.
     */
    public static function get(string $key, array $array, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }
}