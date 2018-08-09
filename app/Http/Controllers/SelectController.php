<?php

namespace App\Http\Controllers;

use Input;
use Maatwebsite\Excel\Facades\Excel;

class SelectController extends Controller
{
    protected $labels;
    
    protected $level = [
        1=> 'I',
        2=> 'II',
        3=> 'III',
        4=> 'IV',
        5=> 'V',
    ];
    
    public function index()
    {
        return view('export.index');
    }
    
    public function select()
    {
        $rows    = [];
        $pdo     = $this->getPdo();
        $queries = ['list_marketer' => '市场专员'];
        foreach ($queries as $query => $label) {
            $rows[$label] = $this->getRecord($pdo->query($this->buildSql($query)));
        }
        return view('select.index', compact('rows'));
    }
    
    public function labels()
    {
        $pdo    = $this->getPdo();
        $labels = $pdo->query($this->buildSql('list_labels'));
        foreach ($labels as $label) {
            $this->labels[$label['parent_id']][] = $label;
        }
        echo "Root";
        $this->getTree(0);
    }
    
    protected function getTree($p_id)
    {
        echo "<ol>";
        foreach ($this->labels[$p_id] as $label) {
            echo "<li> <".$label['id'].'> '.$label['name'].' [ '.$this->level[$label['level']].' ]';
            if (isset($this->labels[$label['id']])) $this->getTree($label['id']);
            echo "</li>";
        }
        echo "</ol>";
    }
    
    protected function getPdo()
    {
        $env = include_once base_path().'/.env.array';
        $db  = $env['mysql'];
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
    }
    
    protected function buildSql($query)
    {
        return $this->$query();
    }
    
    protected function list_marketer()
    {
        return "SELECT nickname, user_account.id FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id WHERE role_id = 2";
    }
    
    protected function list_labels()
    {
        return "SELECT * FROM label WHERE	label_type_id = 1 AND deleted_at IS NULL ORDER BY power DESC";
    }
    
    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($row as $key => $item) {
                is_numeric($key) ? $data[] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }
    
}
