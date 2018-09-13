<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use DB;
use Input;

class SqlController extends Controller
{
    public function analyze($_type, $_group, $_auth = null)
    {
        $db      = DB::setPdo($this->getPdo('dev'));
        $auth_s  = $db->table('sql_log')->distinct()->lists('auth');
        $type_s  = $db->table('sql_log')->distinct()->lists('type');
        $group_s = ['no_group', 'in_group'];
        $query   = $db->table('sql_log')->where('type', $_type);
        isset($_auth) ? $query->where('auth', $_auth) : null;
        $_group == 'in_group'
            ? $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc')
            : $query->orderBy('time', 'desc');
        $sql_s = $query->paginate(30);
        return view('sql.analyze', compact('auth_s', 'type_s', 'group_s', 'sql_s', '_auth', '_type', '_group'));
    }
    
    public function querySql()
    {
        $query = Input::get('query');
        $sql_s = DB::setPdo($this->getPdo('dev'))->table('sql_log')->where('query', $query)->orderBy('time', 'desc')->paginate(30);
        return view('sql.query_sql', compact('sql_s'));
    }
    
    public function queryId($id)
    {
        $db           = DB::setPdo($this->getPdo('dev'));
        $sql          = $db->select("SELECT * FROM sql_log WHERE id = ".$id." LIMIT 1")[0];
        $bindings     = str_replace('&apos;', '\'', str_replace('&quot;', '"', Helper::decodeBindings($sql->bindings)));
        $sql->query   = vsprintf(str_replace("?", "'%s'", $sql->query), Helper::carbonToString($bindings));
        $sql->explain = $db->select("EXPLAIN ".$sql->query);
        $sql->trace   = Helper::convertQuot(json_decode($sql->trace, true));
        $total        = [];
        foreach ($sql->explain as $rows) {
            $table         = $rows->table;
            $total[$table] = $db->table($table)->count();
        }
        return view('sql.query_id', compact('sql', 'total'));
    }
}
