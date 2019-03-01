<?php

namespace App\Console\Commands;

use App\Models\Rpc\DB\Model;
use App\Models\Rpc\DB\Module;
use App\Models\Rpc\DB\Relation;
use Illuminate\Console\Command;
use Storage;

class BuildMigrationMD extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:migration:md {project=rpc}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build Markdown File From Migration';

    protected $dirs;
    protected $table;
    protected $migrations;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->dirs = [
            'rpc' => env('RPC_DIR')
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->migrations = \DB::table('database_migrations')->distinct()->pluck('migration_name')->toArray();
        $project_dir = rtrim($this->dirs[$this->argument('project')], '/');
        $migration_dir = $project_dir . '/database/migrations';
        foreach (scandir($migration_dir) as $_dir) {
            if (in_array($_dir, ['.', '..', '.DS_Store', 'marketing'])) continue;
            $dir = $migration_dir . '/' . $_dir;
            if (!is_dir($dir)) $this->buildMigration($dir, '');
            foreach (scandir($dir) as $migration) {
                if (in_array($migration, ['.', '..', '.DS_Store'])) continue;
                $this->buildMigration($dir . '/' . $migration, $_dir);
            }
//            if ($dir == '/Users/luminee/PhpstormProjects/rpc_server/database/migrations/user') dd($dir);
        }


        dd(1);
        $modules = Module::whereNotIn('code', ['analysis', 'marketing'])->get();
        $bar = $this->output->createProgressBar(count($modules));
        foreach ($modules as $module) {
            $filename = 'wiki/' . $module->code . '.md';
            $content = $this->buildModule($module);
            Storage::put($filename, $content);
            $bar->advance();
        }
        $bar->finish();
        $this->buildIndex($modules);
    }

    protected function buildMigration($migration, $module)
    {
        $file = basename($migration);
        if (in_array($file, $this->migrations)) {
//            $this->error($file . ' Skip');
            return;
        }
        $content = file_get_contents($migration, FILE_USE_INCLUDE_PATH);
        preg_match('/public function up\(\)\\n([\s\S]*)\/\*\*/i', $content, $matches);
        preg_match('/Schema::([\s\S]*)\}\);\\n/i', $matches[1], $matches);
        $content = $matches[1];
        if (strstr($content, 'Schema::')) {
            $matches = explode("Schema::", $content);
            $insert = [];
            foreach ($matches as $match) {
                $match = str_replace("});\n", '', $match);
                $this->getTableInfo($match);
                $insert[] = $this->buildModel($file, $this->table, $module);
            }
            \DB::table('database_migrations')->insert($insert);
            $this->info($file . ' Done.');
//            dd($file);
        } else {
            $this->getTableInfo($content);
            $create = $this->buildModel($file, $this->table, $module);
//            dd($create);
            \DB::table('database_migrations')->insert($create);
            $this->info($file . ' Done.');
//            dd($file);
        }
    }

    protected function getTableInfo($content)
    {
        $this->table = [];
        foreach (explode("\n", $content) as $line) {
            $this->getTable(trim($line));
        }
    }

    protected function buildModel($file, $table, $module)
    {
        $now = date('Y-m-d H:i:s');
        $columns = isset($table['columns']) ? $table['columns'] : [];
        $id_type = collect($columns)->filter(function ($value) {
            return $value['name'] == 'id';
        })->first()['type'];
        $index = [];
        isset($table['index']) ? $index['index'] = $table['index'] : null;
        isset($table['unique']) ? $index['unique'] = $table['unique'] : null;
        isset($table['dropIndex']) ? $index['dropIndex'] = $table['dropIndex'] : null;
        isset($table['dropUnique']) ? $index['dropUnique'] = $table['dropUnique'] : null;
        return [
            'module' => $module,
            'migration_name' => $file,
            'table_name' => $table['name'],
            'migrate_type' => $table['type'] == 'table' ? 'update' : $table['type'],
            'engine' => isset($table['engine']) ? $table['engine'] : '-',
            'id_type' => is_null($id_type) ? '-' : $id_type,
            'columns' => json_encode($columns),
            'index' => json_encode(empty($index) ? null : $index),
            'timestamps' => isset($table['timestamps']) ? 1 : 0,
            'has_deleted' => isset($table['softDeleted']) ? 1 : 0,
            'created_at' => $now, 'updated_at' => $now
        ];
    }

    protected function getTable($line)
    {
        if (empty($line)) return false;
        if (strstr($line, 'function')) return $this->getTableAndType($line);
        if (strstr($line, '$table->engine')) return $this->getEngine($line);
        if (strstr($line, '$table->index')) return $this->getIndex($line);
        if (strstr($line, '$table->unique')) return $this->getUnique($line);
        if (strstr($line, '$table->dropIndex')) return $this->dropIndex($line);
        if (strstr($line, '$table->dropUnique')) return $this->dropUnique($line);
        if (strstr($line, '$table->timestamps()')) return $this->getTimestamps();
        if (strstr($line, '$table->rememberToken()')) return $this->getRememberToken();
        if (strstr($line, '$table->softDeletes()')) return $this->getSoftDeleted();
        return $this->getColumns($line);
    }

    protected function getTableAndType($line)
    {
        list($type, $table) = explode('(', explode(',', $line)[0]);
        $this->table['type'] = $type;
        $table = trim($table, '\'\"');
        $this->table['name'] = $table;
        return true;
    }

    protected function getEngine($line)
    {
        $this->table['engine'] = trim(explode('=', $line)[1], ' \';');
        return true;
    }

    protected function getColumns($line)
    {
        $line = str_replace('"', '\'', $line);
        $items = explode('->', $line);
        $name = $type = '';
        $nullable = $change = $unique = $index = $unsigned = 0;
        $default = $after = $comment = $extra = '-';
        foreach ($items as $item) {
            if ($item == '$table') continue;
            if (strstr($item, 'default')) {
                $default = trim(explode('default', $item)[1], ' ()\';');
                continue;
            }
            if (strstr($item, 'after')) {
                $after = trim(explode('after', $item)[1], ' ()\';');
                continue;
            }
            if (strstr($item, 'nullable()')) {
                $nullable = 1;
                continue;
            }
            if (strstr($item, 'unique()')) {
                $unique = 1;
                continue;
            }
            if (strstr($item, 'index()')) {
                $index = 1;
                continue;
            }
            if (strstr($item, 'unsigned()')) {
                $unsigned = 1;
                continue;
            }
            if (strstr($item, 'change()')) {
                $change = 1;
                continue;
            }
            if (strstr($item, '//')) list($item, $comment) = explode('//', $item);
            if (count(explode('(\'', $item)) == 1) dd($item);
            list($type, $name) = explode('(\'', $item);
            if (strstr($name, ',')) {
                $item = explode(',', $name);
                $name = array_shift($item);
                $extra = trim(implode(',', $item), ' \')');
            }
            $name = trim($name, ' \');');
        }
        if ($unique == 1) $this->table['unique'][] = $name;
        if ($index == 1) $this->table['index'][] = $name;
        if ($type == 'increments' && $name == 'id') $comment = '主键自增ID';
        $data = ['name' => $name, 'type' => $type, 'default' => $default, 'nullable' => $nullable, 'change' => $change,
            'after' => $after, 'extra' => $extra, 'unsigned' => $unsigned, 'comment' => $comment];
        $this->table['columns'][] = $data;
        return true;
    }

    protected function getIndex($line)
    {
        $keys = trim(str_replace('\'', '', explode('->index(', $line)[1]), ' [])\';');
        $this->table['index'][] = $keys;
        return true;
    }

    protected function getUnique($line)
    {
        $keys = trim(str_replace('\'', '', explode('->unique(', $line)[1]), ' [])\';');
        $this->table['unique'][] = $keys;
        return true;
    }

    protected function dropUnique($line)
    {
        $this->table['dropUnique'][] = trim(explode('->dropUnique(', $line)[1], ' \');');
        return true;
    }

    protected function dropIndex($line)
    {
        $this->table['dropIndex'][] = trim(explode('->dropIndex(', $line)[1], ' \');');
        return true;
    }

    protected function getTimestamps()
    {
        $this->table['timestamps'] = 1;
        return true;
    }

    protected function getRememberToken()
    {
        $this->table['columns'][] = ['name' => 'rememberToken', 'type' => 'string', 'default' => '-', 'nullable' => 1, 'change' => 0,
            'after' => '-', 'extra' => '-', 'comment' => '-'];
        return true;
    }

    protected function getSoftDeleted()
    {
        $this->table['soft_deleted'] = 1;
        return true;
    }

    protected function buildModule($module)
    {
        $label = empty($module->label) ? $module->code : $module->label;
        $content = "[[ ./../ | 数据库结构 ]] 之 " . ucfirst($label) . " 模块\r\n";
        $content .= "===================\r\n\r\n---\r\n模型\r\n------\r\n";
        $content .= "List\r\n----\r\n";
        $models = Model::where('module_id', $module->id)->get();
        foreach ($models as $model) {
            $content .= "- [[ ./#" . strtolower($model->code) . " | " . $model->code . " ]]\r\n";
        }
        $content .= "\r\n---\r\n";
        foreach ($models as $model) {
            $content .= $this->buildModel($model);
        }
        $content .= "\r\n[[ ./#list | 返回顶部 ]]\r\n";
        $content .= "\r\n[[ ./../ | 返回上一层 ]]";
        return $content;
    }

    protected function build__Model($model)
    {
        $content = "\r\n";
        $content .= $model->code . "\r\n";
        $content .= "-----\r\n";
        $content .= "\r\n(NOTE) **描述** : \r\n";
        $content .= "\r\n**表名**  ：`" . $model->table . "`\r\n";
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
        $fields = \DB::connection('dev')->select("DESC `" . $table . "`");
        $content = '';
        $Labels = $this->getFieldLabels();
        $labels = $this->getWordLabels();
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
            $content .= "| " . $_field . " | " . $field->Type . " | " . $label . " |\r\n";
        }
        return $content;
    }

    protected function buildRelation($model)
    {
        $relations = Relation::where('model_id', $model->id)->get();
        $content = "\r\n";
        foreach ($relations as $relation) {
            $content .= "- " . $relation->relation . "() !!";
            $content .= $relation->relate_type == 'belongsTo' ? '多个对应一个 ' : '有多个 ';
            $content .= $relation->related_model . "!! ";
            $content .= "`主键(" . $relation->local_key . ") 外键(" . $relation->foreign_key . ")`\r\n";
            $content .= "\r\n";
        }
        return $content;
    }

    protected function buildIndex($modules)
    {
        $filename = 'wiki/index.md';
        $content = "数据库结构 -- 模块\r\n============\r\n\r\n---\r\n\r\n";
        foreach ($modules as $module) {
            $content .= "- [[ ./ " . $module->code . " | " . ucfirst($module->code) . " ]]\r\n";
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
