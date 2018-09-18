<?php

namespace App\Console\Commands;

use App\Models\Rpc\DB\Model;
use App\Models\Rpc\DB\Module;
use App\Models\Rpc\DB\Relation;
use Illuminate\Console\Command;
use Storage;

class BuildDbWiki extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:db:wiki';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build DB to Wiki';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $modules = Module::whereNotIn('code', ['analysis', 'marketing'])->get();
        $bar     = $this->output->createProgressBar(count($modules));
        foreach ($modules as $module) {
            $filename = 'wiki/'.$module->code.'.md';
            $content  = $this->buildModule($module);
            Storage::put($filename, $content);
            $bar->advance();
        }
        $bar->finish();
        $this->buildIndex($modules);
    }
    
    protected function buildModule($module)
    {
        $label   = empty($module->label) ? $module->code : $module->label;
        $content = "[[ ./../ | 数据库结构 ]] 之 ".ucfirst($label)." 模块\r\n";
        $content .= "===================\r\n\r\n---\r\n模型\r\n------\r\n";
        $content .= "List\r\n----\r\n";
        $models  = Model::where('module_id', $module->id)->get();
        foreach ($models as $model) {
            $content .= "- [[ ./#".strtolower($model->code)." | ".$model->code." ]]\r\n";
        }
        $content .= "\r\n---\r\n";
        foreach ($models as $model) {
            $content .= $this->buildModel($model);
        }
        $content .= "\r\n[[ ./#list | 返回顶部 ]]\r\n";
        $content .= "\r\n[[ ./../ | 返回上一层 ]]";
        return $content;
    }
    
    protected function buildModel($model)
    {
        $content = "\r\n";
        $content .= $model->code."\r\n";
        $content .= "-----\r\n";
        $content .= "\r\n(NOTE) **描述** : \r\n";
        $content .= "\r\n**表名**  ：`".$model->table."`\r\n";
        $content .= "\r\n| 字段名 | 字段类型 | 释义 |\r\n";
        $content .= "| --------| ----------| -------|\r\n";
        $content .= $this->buildFields($model->table);
        $content .= "\r\n**关联关系**\r\n";
        $content .= $this->buildRelation($model);
        $content .= "---\r\n";
        return $content;
    }
    
    protected function buildFields($table)
    {
        $fields  = \DB::connection('dev')->select("DESC `".$table."`");
        $content = '';
        $Labels  = $this->getFieldLabels();
        $labels  = $this->getWordLabels();
        foreach ($fields as $field) {
            $_field = $field->Field;
            if (array_key_exists($_field, $Labels)) {
                $label = $Labels[$_field];
            } else {
                $str = [];
                foreach (explode('_', $_field) as $word) {
                    $str[] = array_key_exists($word, $labels) ? $labels[$word] : $word;
                }
                $label = implode(' ', $str);
            }
            $content .= "| ".$_field." | ".$field->Type." | ".$label." |\r\n";
        }
        return $content;
    }
    
    protected function buildRelation($model)
    {
        $relations = Relation::where('model_id', $model->id)->get();
        $content   = "\r\n";
        foreach ($relations as $relation) {
            $content .= "- ".$relation->relation."() !!";
            $content .= $relation->relate_type == 'belongsTo' ? '多个对应一个 ' : '有多个 ';
            $content .= $relation->related_model."!! ";
            $content .= "`主键(".$relation->local_key.") 外键(".$relation->foreign_key.")`\r\n";
            $content .= "\r\n";
        }
        return $content;
    }
    
    protected function buildIndex($modules)
    {
        $filename = 'wiki/index.md';
        $content  = "数据库结构 -- 模块\r\n============\r\n\r\n---\r\n\r\n";
        foreach ($modules as $module) {
            $content .= "- [[ ./ ".$module->code." | ".ucfirst($module->code)." ]]\r\n";
        }
        $content .= "\r\n---\r\n\r\n[[ ./../ | 返回上一层 ]]";
        Storage::put($filename, $content);
    }
    
    protected function getWordLabels()
    {
        return [
            'id' => 'ID',
            'is' => '是否',
            'key' => '键',
            'type' => '类型',
            'code' => 'Code',
            'name' => 'Name',
            'value' => '值',
            'label' => 'Label',
            'result' => '结果',
            'project' => '项目',
            'content' => '内容',
            'account' => '用户',
            'student' => '学生',
            'teacher' => '教师',
            'vanclass' => '班级',
            'processed' => '已处理',
        ];
    }
    
    protected function getFieldLabels()
    {
        return [
            'id' => '主键',
            'deleted_at' => '删除时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'created_date' => '创建日期',
        ];
    }
}
