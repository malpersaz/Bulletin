<?php
/**
 * Koberz Bilişim İçerik Yönetim Sistemi
 * Web: www.koberz.com
 * E-Mail: info@koberz.com
 * Kullanıcı: Kobim
 * Zaman: 16.04.2011- 21:20
 * Sınıfı : Dconvert
 */

class Dconvert
{
    /*
     * Convert Array to Object
     * Arrayı Objeye Çevirir
     * @param array $array
     * @return @object
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
            "$array is not Convert to Object\n" .
            "$array Objeye Çevrilemedi!\n" .
            var_dump($array)
        );
        return false;
    }

    /*
    * Convert Array to Object
    * Arrayı Objeye Çevirir
    * @param array|object $array
    * @return object
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
            "$array is not Convert to Array\n" .
            "$array Diziye Çevrilemedi!\n" .
            var_dump($array)
        );
        return false;
    }

    /*
    * Convert Array to Json
    * Arrayı Json'a Çevirir
    * @param array $array
    * @return json
    */
    public static function toJson($array)
    {
        return json_encode($array);
    }

    /*
    * Convert Array to Serialize
    * Arrayı serialize eder
    * @param array $array
    * @return serialize
    */
    public static function toSerialize($array)
    {
        return serialize($array);
    }

    /*
    * Convert to Unserialize
    * unserialize eder
    * @param array $array
    * @return serialize
    */
    public static function toUnserialize($array)
    {
        return unserialize($array);
    }

    /*
    * Convert Array to Object
    * Arrayı Objeye Çevirir
    * @param array $array
    * @return object
    */
    public static function convertArrayToObject($arr)
    {
        foreach ($arr as &$v) {
            if (is_array($v)) {
                $v = self::convertArrayToObject($v);
            }
        }
        return ( object )$arr;
    }

    /*
    * Convert Object to array
    * Objeyi Diziye Çevirir
    * @param object $object
    * @return array
    */
    public static function convertObjectToArray($object)
    {
        if (is_array($object) || is_object($object)) {
            $array = [];
            foreach ($object as $key => $value) {
                $array [$key] = self::convertObjectToArray($value);
            }
            return $array;
        }
        return $object;
    }
}
