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
        $_query = Input::get('query');
        $_id    = Input::get('id');
        $_group = Input::get('group', 0);
        if (isset($_id)) {
            $sql          = DB::select("SELECT * FROM sql_log WHERE id = ".$_id." LIMIT 1")[0];
            $bindings     = $sql->bindings;
            $bindings     = str_replace('&apos;', '\'', str_replace('&quot;', '"', Helper::decodeBindings($bindings)));
            $sql->query   = vsprintf(str_replace("?", "'%s'", $sql->query), Helper::carbonToString($bindings));
            $sql->explain = DB::select("EXPLAIN ".$sql->query);
            $total        = [];
            foreach ($sql->explain as $rows) {
                $table         = $rows->table;
                $total[$table] = DB::table($table)->count();
            }
        } else {
            $query = DB::table('sql_log');
            isset($_type) ? $query->where('type', $_type) : null;
            isset($_auth) ? $query->where('auth', $_auth) : null;
            isset($_query) ? $query->where('query', $_query) : null;
            $_group == 1 ? $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc') : $query->orderBy('time', 'desc');
            isset($_query) ? $query->orderBy('time', 'desc') : null;
            $sql_s = $query->limit(30)->get();
        }
        return view('sql.list', compact('auth_s', 'types', 'sql_s', '_auth', '_type', '_group', 'sql', 'total'));
    }
}
