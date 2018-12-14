<?php

class SystemSystemConfigAppendGroupSetSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $value = ['user' => '用户权限', 'common' => '通用', 'framework' => '架构', 'data' => '数据', 'database' => '数据库', 'sql' => 'SQL', 'monitor' => '监控', 'export' => '导出'];
        $data = ['type' => 'system_param', 'label' => '权限组', 'key' => 'group_sets', 'value' => json_encode($value)];
        $now = date('Y-m-d H:i:s');
        if (empty($item = DB::table('system_config')->where('key', 'group_sets')->first())) {
            DB::table('system_config')->insert(array_merge($data, ['created_at' => $now, 'updated_at' => $now]));
        } else {
            DB::table('system_config')->where('id', $item->id)->update(array_merge($data, ['updated_at' => $now]));
        }
    }
}
