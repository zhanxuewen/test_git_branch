<?php

namespace App\Helper;

use App\Foundation\PdoBuilder;

class Helper
{
    use PdoBuilder;

    protected static $cache = null;

    public static function getCache()
    {
        if (is_null(self::$cache)) {
            self::$cache = (new self())->getRedis('analyze')->get('dev_table_rows');
        }
        return self::$cache;
    }

    public static function generateCaptcha($length)
    {
        $code_sets = "1234567890ABCDEFGHJKLMNPQRSTUVWXYZ1234567890";
        $captcha = "";
        $max = strlen($code_sets) - 1;
        for ($i = 0; $i < $length; $i++) {
            $captcha .= $code_sets[rand(0, $max)];
        }
        return $captcha;
    }

    public static function modifyDatabaseConfig($conn)
    {
        $env = include base_path() . '/.env.array';
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

    public static function needHide($explain)
    {
        if (empty($explain)) return true;
        $cache = self::getCache();
        $tables = json_decode($cache, true);
        $explain = json_decode(str_replace('&quot;', '"', $explain), true);
        foreach ($explain as $item) {
            $table = $item['table'];
            if (!isset($tables[$table]) || self::exceptTables($item['table']) == true) continue;
            if ($item['type'] == 'ALL') return false;
            if (empty($item['key'])) return false;
            $_row = $item['rows'];
            $rows = $tables[$table];
            if ($_row / $rows > 0.05) return false;
        }
        return true;
    }

    protected static function exceptTables($table)
    {
        $excepts = ['user_type', 'system_role', 'log_type', 'system_config','payment_commodity'];
        return in_array($table, $excepts) ? true : false;
    }

    public static function showExplain($explain)
    {
        if (empty($explain)) return '';
        $cache = self::getCache();
        $tables = json_decode($cache, true);
        $explain = json_decode(str_replace('&quot;', '"', $explain), true);
        $out = '';
        foreach ($explain as $item) {
            $label = '';
            $table = $item['table'];
            if (!isset($tables[$table])) continue;
            if ($item['type'] == 'ALL') $label .= ' ' . self::spanLabelBgColor('全表扫描');
            if (empty($item['key'])) $label .= ' ' . self::spanLabelBgColor('未使用索引');
            $_row = $item['rows'];
            $rows = $tables[$table];
            if ($_row / $rows > 0.05) {
                $info = '获取行 ' . $_row . ' / ' . $rows . ' (' . round($_row / $rows * 100, 2) . '%)';
                $label .= ' ' . self::spanLabelBgColor($info);
            }
            if (!empty($label)) $out .= self::spanLabelBgColor($item['table']) . $label;
        }
        return $out;
    }

    protected static function spanLabelBgColor($item, $color = 'bg-red')
    {
        return '<span class="label ' . $color . '">' . $item . '</span>';
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