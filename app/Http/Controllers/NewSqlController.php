<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use DB;
use Input;

class NewSqlController extends Controller
{
    public function index()
    {
        $auth_s = DB::table('sql_log')->distinct()->lists('auth');
        $types  = DB::table('sql_log')->distinct()->lists('type');
        $_auth  = Input::get('auth');
        $_type  = Input::get('type');
        $_group = Input::get('group', 0);
        
        $query = DB::table('sql_log');
        isset($_type) ? $query->where('type', $_type) : null;
        isset($_auth) ? $query->where('auth', $_auth) : null;
        $_group == 1 ? $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc') : $query->orderBy('time', 'desc');
        $sql_s = $query->limit(30)->get();
        
        return view('sql.list', compact('auth_s', 'types', 'sql_s', '_auth', '_type', '_group'));
    }
    
    public function querySql()
    {
        $query = Input::get('query');
        $sql_s = DB::table('sql_log')->where('query', $query)->orderBy('time', 'desc')->limit(30)->get();
        
        return view('sql.query_sql', compact('sql_s'));
    }
    
    public function queryId($id)
    {
        $sql          = DB::select("SELECT * FROM sql_log WHERE id = ".$id." LIMIT 1")[0];
        $bindings     = str_replace('&apos;', '\'', str_replace('&quot;', '"', Helper::decodeBindings($sql->bindings)));
        $sql->query   = vsprintf(str_replace("?", "'%s'", $sql->query), Helper::carbonToString($bindings));
        $sql->explain = DB::select("EXPLAIN ".$sql->query);
        $sql->trace   = Helper::convertQuot(json_decode($sql->trace, true));
        $total        = [];
        foreach ($sql->explain as $rows) {
            $table         = $rows->table;
            $total[$table] = DB::table($table)->count();
        }
        return view('sql.query_id', compact('sql', 'total'));
    }
}
