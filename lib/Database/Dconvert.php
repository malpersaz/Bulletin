<?php
/**
 * AKM Business Content Management System
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * User: AKM
 * Class: Dconvert
 * File: Dconvert.php
 * Description: Data conversion utilities (Array, Object, JSON, Serialization)
 */

class Dconvert
{
    /**
     * Convert Array to Object
     * 
     * @param array|object $array
     * @return object
     */
    public static function toObject($array)
    {
        if (is_object($array)) {
            return $array;
        }
        if (is_array($array)) {
            return self::convertArrayToObject($array);
        }
        throw new DconvertException(
            "Value could not be converted to Object:\n" . var_export($array, true)
        );
    }

    /**
     * Convert Object or Array to Array
     * 
     * @param array|object $array
     * @return array
     */
    public static function toArray($array)
    {
        if (is_array($array)) {
            return $array;
        }
        if (is_object($array)) {
            return self::convertObjectToArray($array);
        }
        throw new DconvertException(
            "Value could not be converted to Array:\n" . var_export($array, true)
        );
    }

    /**
     * Convert Array to JSON
     * 
     * @param array $array
     * @return string
     */
    public static function toJson($array)
    {
        return json_encode($array);
    }

    /**
     * Serialize Array
     * 
     * @param mixed $array
     * @return string
     */
    public static function toSerialize($array)
    {
        return serialize($array);
    }

    /**
     * Unserialize Data
     * 
     * @param string $data
     * @return mixed
     */
    public static function toUnserialize($data)
    {
        return unserialize($data);
    }

    /**
     * Convert Array to Object recursively
     * 
     * @param array $arr
     * @return object
     */
    public static function convertArrayToObject($arr)
    {
        foreach ($arr as &$v) {
            if (is_array($v)) {
                $v = self::convertArrayToObject($v);
            }
        }
        return (object)$arr;
    }

    /**
     * Convert Object to Array recursively
     * 
     * @param object|array $object
     * @return array
     */
    public static function convertObjectToArray($object)
    {
        if (is_array($object) || is_object($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array[$key] = self::convertObjectToArray($value);
            }
            return $array;
        }
        return $object;
    }
}
