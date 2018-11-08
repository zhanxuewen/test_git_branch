<?php

namespace App\Helper;

class Helper
{
    public static function modifyDatabaseConfig($conn)
    {
        $env     = include base_path().'/.env.array';
        $default = config('database.default');
        foreach ($env[$conn] as $key => $vale) {
            config(["database.connections.$default.$key" => $vale]);
        }
    }
    
    public static function vsprintf($query, $bindings)
    {
        if (is_null($bindings)) {
            return str_replace('&apos;', '\'', str_replace('&quot;', '"', $query));
        }
        $bindings = str_replace('&apos;', '\'', str_replace('&quot;', '"', self::decodeBindings($bindings)));
        return vsprintf(str_replace("?", "'%s'", $query), self::carbonToString($bindings));
    }
    
    public static function showExplain($explain)
    {
        $cache   = \Cache::get('dev_table_rows');
        $tables  = json_decode($cache, true);
        $explain = json_decode(str_replace('&quot;', '"', $explain), true);
        $out     = '';
        foreach ($explain as $item) {
            $label = '';
            $table = $item['table'];
            if ($item['type'] == 'ALL') $label .= ' <span class="label bg-red">全表扫描</span>';
            if (empty($item['key'])) $label .= ' <span class="label bg-red">未使用索引</span>';
            $_row = $item['rows'];
            $rows = $tables[$table];
            if ($_row / $rows > 0.05)
                $label .= ' <span class="label bg-red">获取行 '.$_row.' / '.$rows.' ('.round($_row / $rows * 100, 2).'%)</span>';
            if (!empty($label)) $out .= '<span class="label bg-red">'.$item['table'].'</span>'.$label;
        }
        return $out;
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