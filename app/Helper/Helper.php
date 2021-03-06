<?php

namespace App\Helper;

use App\Foundation\PdoBuilder;

class Helper
{
    use PdoBuilder;

    protected static $cache = null;

    public static function getCache($pje_conn)
    {
        if (is_null(self::$cache)) {
            self::$cache = (new self())->getRedis('analyze')->get($pje_conn . '_table_rows');
        }
        return self::$cache;
    }

    public static function vsprintf($query, $bindings)
    {
        if (is_null($bindings)) {
            return str_replace('&apos;', '\'', str_replace('&quot;', '"', $query));
        }
        $bindings = str_replace('&apos;', '\'', str_replace('&quot;', '"', self::decodeBindings($bindings)));
        return vsprintf(str_replace("?", "'%s'", $query), self::carbonToString($bindings));
    }

    public static function needHide($explain, $conn)
    {
        if (empty($explain)) return true;
        $cache = self::getCache($conn);
        $tables = json_decode($cache, true);
        $explain = json_decode(str_replace('&quot;', '"', $explain), true);
        foreach ($explain as $item) {
            $table = $item['table'];
            if (!isset($tables[$table]) || self::exceptTables($item['table']) == true) continue;
            if ($item['type'] == 'ALL') return false;
            if (empty($item['key'])) return false;
            $_row = $item['rows'];
            $rows = $tables[$table] == 0 ? 1 : $tables[$table];
            if ($_row / $rows > 0.05) return false;
        }
        return true;
    }

    protected static function exceptTables($table)
    {
        $excepts = ['user_type', 'system_role', 'log_type', 'system_config', 'payment_commodity'];
        return in_array($table, $excepts) ? true : false;
    }

    public static function showExplain($explain, $pje_conn)
    {
        if (empty($explain)) return '';
        $cache = self::getCache($pje_conn);
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
            $rows = $tables[$table] == 0 ? 1 : $tables[$table];
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

    public static function countNotRead($notices, $user_id)
    {
        $count = 0;
        foreach ($notices as $notice) {
            if ($notice->receiver_id == $user_id && $notice->has_read == 0) $count++;
        }
        return $count;
    }
}