<?php

namespace App\Console\Pool;

use DB;

class SchoolInfo
{
    public static function getContract()
    {
        $list = DB::table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'contract_class')->get();
        return self::toArray($list, 'school_id', 'value');
    }

    public static function getRegions()
    {
        $list = DB::table('school_attribute')->selectRaw('school_id, value')->where('key', 'region')->get();
        return self::toArray($list, 'school_id', 'value');
    }

    public static function getAfterSales()
    {
        $list = DB::table('school_attribute')->selectRaw('school_attribute.school_id, nickname')
            ->join('user_account', 'user_account.id', '=', 'school_attribute.value')
            ->where('key', 'after_sales')->get();
        return self::toArray($list, 'school_id', 'nickname');
    }

    /**
     * @param $query
     * @param $key
     * @param array | string $alias
     * @return array
     */
    private static function toArray($query, $key, $alias)
    {
        $tmp = [];
        foreach ($query as $data) {
            $tmp[$data->$key] = is_array($alias) ? self::buildValues($data, $alias) : $data->$alias;
        }
        return $tmp;
    }

    /**
     * @param $data
     * @param array $keys
     * @return array
     */
    private static function buildValues($data, $keys)
    {
        $tmp = [];
        foreach ($keys as $key) {
            $tmp[] = $data->$key;
        }
        return $tmp;
    }
}