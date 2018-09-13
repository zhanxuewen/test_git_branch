<?php

namespace App\Http\Controllers;

class DatabaseController extends Controller
{
    /**
     * List Migration Diff
     */
    public function migration_diff()
    {
        foreach (['dev', 'test', 'online'] as $env) {
            $$env = $this->resultToArray($this->getPdo($env)->query($this->buildSql('list_migrations', null)));
        }
        return view('database.migration_diff', compact('dev', 'test', 'online'));
    }
    
    public function table_diff()
    {
        foreach (['dev' => 'b_vanthink_core', 'test' => 'b_vanthink_online', 'online' => 'b_vanthink_online'] as $env => $db) {
            $$env = $this->resultToArray($this->getPdo($env)->query($this->buildSql('list_tables', $db)));
        }
        return view('database.table_diff', compact('dev', 'test', 'online'));
    }
    
    public function table_correct()
    {
        $conn   = \Input::get('conn', 'dev');
        $Dir    = '/vanthink/rpc_server';
        $dir    = realpath($Dir.'/database/migrations');
        $pdo    = $this->getPdo($conn);
        $sql    = $this->buildSql('list_tables', $this->getDbName($conn));
        $tables = $this->resultToArray($pdo->query($sql));
        $mig_s  = $this->resultToArray($pdo->query($this->buildSql('list_migrations', null)));
        dd($tables);
        
        $mig_tables    = [];
        $_no_mig_file  = [];
        $_mig_no_table = [];
        foreach ($mig_s as $mig) {
            $module = explode('_', $mig)[5];
            if ($module == 'puzzle') $module = 'reward';
            if ($module == 'logs') $module = 'log';
            if (in_array($module, ['failed', 'jobs', 'hot', 'device'])) continue;
            $migration = realpath($dir.'/'.$module.'/'.$mig.'.php');
            if (!is_file($migration)) {
                $_no_mig_file[] = $mig;
            } else {
                $file = file_get_contents($migration, FILE_USE_INCLUDE_PATH);
                preg_match('/Schema\:\:(create|table)\(\'([a-z_]+)\'/i', $file, $matches);
                $mig_tables[$matches[2]][] = $mig;
                if (!in_array($matches[2], $tables)) $_mig_no_table[] = $mig;
            }
        }
        $mig_table_names = array_keys($mig_tables);
        $_table_no_mig   = [];
        foreach ($tables as $table) {
            if (!in_array($table, $mig_table_names)) $_table_no_mig[] = $table;
        }
        dd(['_no_mig_file', $_no_mig_file], ['_mig_no_table', $_mig_no_table], ['_table_no_mig', $_table_no_mig]);
    }
    
    protected function list_tables($database)
    {
        return "SELECT table_name FROM information_schema.tables where table_schema='$database'";
    }
    
    protected function list_migrations($null)
    {
        return "SELECT migration FROM migrations";
    }
    
    protected function resultToArray($result)
    {
        $array = [];
        foreach ($result as $item) {
            $array[] = $item[0];
        }
        return $array;
    }
    
}
