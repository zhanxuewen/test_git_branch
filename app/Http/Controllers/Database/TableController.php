<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;

class TableController extends Controller
{
    /**
     * List Tables
     */
    public function getTableList()
    {
        $conn   = \Input::get('conn', 'dev');
        $pdo    = $this->getPdo($conn);
        $sql    = $this->buildSql('list_tables', $this->getDbName($conn));
        $tables = $this->getRecord($pdo->query($sql));
        return view('database.table_list', compact('tables'));
    }
    
    public function getTableInfo($table_name)
    {
        $conn    = \Input::get('conn', 'dev');
        $pdo     = $this->getPdo($conn);
        $params  = ['database' => $this->getDbName($conn), 'table_name' => $table_name];
        $table   = $this->getRecord($pdo->query($this->buildSql('table_info', $params)))[0];
        $columns = $this->getRecord($pdo->query($this->buildSql('list_columns', $params)));
        return view('database.table_info', compact('table', 'columns'));
    }
    
    protected function list_tables($database)
    {
        return "SELECT GROUP_CONCAT(column_name) as columns, table_name FROM information_schema.columns WHERE table_schema='$database' GROUP BY table_name ORDER BY table_name";
    }
    
    protected function table_info($params)
    {
        return "SELECT table_name, engine, table_rows, auto_increment FROM information_schema.tables WHERE table_schema = '".$params['database']."' AND table_name = '".$params['table_name']."'";
    }
    
    protected function list_columns($params)
    {
        return "SELECT column_name, column_default, is_nullable, data_type, column_type FROM information_schema.columns WHERE table_schema = '".$params['database']."' AND table_name = '".$params['table_name']."' ORDER BY ordinal_position";
    }
    
    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($row as $key => $item) {
                !is_numeric($key) ? $data[$key] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }
    
}
