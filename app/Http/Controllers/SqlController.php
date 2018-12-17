<?php

namespace App\Http\Controllers;

use DB;
use App\Helper\Helper;
use Illuminate\Http\Request;

class SqlController extends Controller
{
    public function analyze($_type, $_group, $_auth = null)
    {
        $conn = $this->getAnalyzeConn();
        $db = DB::setPdo($this->getPdo($conn));
        $auth_s = $db->table('sql_log')->distinct()->pluck('auth');
        $type_s = $db->table('sql_log')->distinct()->pluck('type');
        $group_s = ['no_group', 'in_group'];
        $query = $db->table('sql_log')->where('type', $_type);
        isset($_auth) ? $query->where('auth', $_auth) : null;
        $_group == 'in_group'
            ? $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc')
            : $query->orderBy('time', 'desc');
        $sql_s = $query->paginate(30);
        return view('sql.analyze', compact('auth_s', 'type_s', 'group_s', 'sql_s', '_auth', '_type', '_group'));
    }

    public function querySql(Request $request)
    {
        $conn = $this->getAnalyzeConn();
        $query = $request->get('query');
        $sql_s = DB::setPdo($this->getPdo($conn))->table('sql_log')->where('query', $query)->orderBy('time', 'desc')->paginate(30);
        return view('sql.query_sql', compact('sql_s'));
    }

    public function queryId($id)
    {
        $conn = $this->getAnalyzeConn();
        $db = DB::setPdo($this->getPdo($conn));
        $sql = $db->select("SELECT * FROM sql_log WHERE id = " . $id . " LIMIT 1")[0];
        if (is_null($sql->bindings)) {
            $sql->query = str_replace('&apos;', '\'', str_replace('&quot;', '"', $sql->query));
        } else {
            $bindings = str_replace('&apos;', '\'', str_replace('&quot;', '"', Helper::decodeBindings($sql->bindings)));
            $sql->query = vsprintf(str_replace("?", "'%s'", $sql->query), Helper::carbonToString($bindings));
        }
        $sql->explain = $db->select("EXPLAIN " . $sql->query);
        $sql->trace = Helper::convertQuot(json_decode($sql->trace, true));
        $total = [];
        foreach ($sql->explain as $rows) {
            $table = $rows->table;
            $total[$table] = $db->table($table)->count();
        }
        return view('sql.query_id', compact('sql', 'total'));
    }

    public function emptySql(Request $request)
    {
        $conn = $this->getAnalyzeConn();
        $auth = $request->get('auth');
        DB::setPdo($this->getPdo($conn))->table('sql_log')->where('auth', $auth)->delete();
        return back();
    }

    public function ajaxQuerySql(Request $request)
    {
        $conn = $this->getAnalyzeConn();
        $start = microtime(true);
        DB::setPdo($this->getPdo($conn))->select($request->get('sql'));
        return round((microtime(true) - $start) * 1000, 2);
    }

    protected function getAnalyzeConn()
    {
        $redis = $this->getRedis('analyze');
        $user_id = $this->getUser('id');
        $key = $user_id . '_sql_analyze_conn';
        if (!$conn = $redis->get($key)) {
            $conn = 'dev';
            $redis->setex($key, 60 * 60 * 24, $conn);
        }
        return $conn;
    }
}
