<?php

use App\Models\Label\Label;

class InitLabelAppendRouteGroupSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $type_id = DB::table('label_type')->where('code', 'system')->first()->id;
        $parent = Label::firstOrCreate(['name' => '路由分组', 'code' => 'route_group', 'is_active' => 1, 'label_type_id' => $type_id, 'level' => 1, 'power' => 10]);
        $parent_id = $parent->id;
        $create = ['is_active' => 1, 'label_type_id' => $type_id, 'parent_id' => $parent_id, 'level' => 2];
        Label::firstOrCreate(array_merge($create, ['name' => '用户权限', 'code' => 'user', 'power' => 10]));
        Label::firstOrCreate(array_merge($create, ['name' => '通用', 'code' => 'common', 'power' => 9]));
        Label::firstOrCreate(array_merge($create, ['name' => '架构', 'code' => 'framework', 'power' => 8]));
        Label::firstOrCreate(array_merge($create, ['name' => '数据', 'code' => 'data', 'power' => 7]));
        Label::firstOrCreate(array_merge($create, ['name' => '数据库', 'code' => 'database', 'power' => 6]));
        Label::firstOrCreate(array_merge($create, ['name' => 'SQL', 'code' => 'sql', 'power' => 5]));
        Label::firstOrCreate(array_merge($create, ['name' => '监控', 'code' => 'monitor', 'power' => 4]));
        Label::firstOrCreate(array_merge($create, ['name' => '导出', 'code' => 'export', 'power' => 3]));
    }
}
