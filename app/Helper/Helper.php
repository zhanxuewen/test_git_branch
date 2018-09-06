<?php

namespace App\Helper;

class Helper
{
    public static function vsprintf($query, $bindings)
    {
        $bindings = str_replace('&apos;', '\'', str_replace('&quot;', '"', self::decodeBindings($bindings)));
        return vsprintf(str_replace("?", "'%s'", $query), self::carbonToString($bindings));
    }
    
    /**
     * @param mixed
     * @return mixed
     */
    public static function carbonToString($data)
    {
        foreach ($data as &$item) {
            if (is_array($item)) $item = self::carbonToString($item);
            if (is_object($item)) $item = self::toString($item);
        }
        return $data;
    }
    
    protected static function toString($object)
    {
        if (isset($object->date) && isset($object->timezone_type)) $object = substr($object->date, 0, 19);
        return $object;
    }
    
    public static function convertQuot($items)
    {
        foreach ($items as $key => $item) {
            if (is_array($item)) $items[$key] = self::convertQuot($item);
            if (is_string($item)) $items[$key] = str_replace('&quot;', '"', $item);
        }
        return $items;
    }
    
    public static function decodeBindings($bindings)
    {
        return json_decode(str_replace('\_', '_', $bindings));
    }
}