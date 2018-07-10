<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use DB;
use Input;

class SqlController extends Controller
{
    public function index()
    {
        $auth_s = DB::table('sql_log')->distinct()->lists('auth');
        $types  = DB::table('sql_log')->distinct()->lists('type');
        $_auth  = Input::get('auth');
        $_type  = Input::get('type');
        $_query = Input::get('query');
        $_id    = Input::get('id');
        $query  = DB::table('sql_log');
        isset($_type) ? $query->where('type', $_type) : null;
        isset($_auth) ? $query->where('auth', $_auth) : null;
        isset($_query) ? $query->where('query', $_query) : null;
        isset($_query) ? $query->orderBy('time', 'desc') : $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc');
        $sql_s = $query->simplePaginate(30);
        if (isset($_id)) {
            $sql          = DB::select("SELECT * FROM sql_log WHERE id = ".$_id." LIMIT 1")[0];
            $bindings     = $sql->bindings;
            $bindings     = str_replace('&apos;', '\'', str_replace('&quot;', '"', json_decode($bindings)));
            $sql->query   = vsprintf(str_replace("?", "'%s'", $sql->query), Helper::carbonToString($bindings));
            $sql->explain = DB::select("EXPLAIN ".$sql->query);
            $total        = [];
            foreach ($sql->explain as $rows) {
                $table         = $rows->table;
                $total[$table] = DB::table($table)->count();
            }
        }
        return view('sql.index', compact('auth_s', 'types', 'sql_s', '_auth', '_type', 'sql', 'total'));
    }
}
