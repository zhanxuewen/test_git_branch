<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use App\Helper\Helper;
use Illuminate\Http\Request;

class SqlController extends Controller
{
    public function analyze(Request $request, $_type, $_group, $_auth = null)
    {
        $day = $request->get('day', '7_d');
        $between = [];
        if (strstr($day, '_d')) {
            $days = explode('_', $day)[0];
            $between = [Carbon::now()->subDay($days), Carbon::now()];
        }
        if (strstr($day, 'last')) {
            in_array($day, ['lastWeek', 'lastMonth']) ? null : $day = 'lastWeek';
            $between = $this->$day();
        }
        $_day = $day;
        $days = ['7_d' => '7 days', '14_d' => '14 days', 'lastWeek' => 'last week', 'lastMonth' => 'last month'];
        list($project, $conn) = $this->getAnalyzeConn();
        $db = DB::setPdo($this->getConnPdo($project, $conn));
        $auth_s = $db->table('sql_log')->distinct()->pluck('auth');
        $type_s = $db->table('sql_log')->distinct()->pluck('type');
        $group_s = ['no_group', 'in_group'];
        $uri = url(explode('?', $request->getRequestUri())[0]);
        $query = $db->table('sql_log')->where('type', $_type);
        isset($_auth) ? $query->where('auth', $_auth) : null;
        $_group == 'in_group'
            ? $query->selectRaw('*, count(*) as count')->groupBy('query')->orderBy('count', 'desc')
            : $query->orderBy('time', 'desc');
        if (!empty($between)) $query->whereBetween('created_at', $between);
        $sql_s = $query->paginate($this->getPerPage())->withPath($uri);
        return view('sql.analyze', compact('auth_s', 'type_s', 'group_s', 'sql_s', '_auth', '_type', '_group', '_day', 'days', 'project', 'conn'));
    }

    public function querySql(Request $request)
    {
        list($project, $conn) = $this->getAnalyzeConn();
        $query = $request->get('query');
        $sql_s = DB::setPdo($this->getConnPdo($project, $conn))->table('sql_log')->where('query', $query)->orderBy('time', 'desc')->paginate($this->getPerPage());
        return view('sql.query_sql', compact('sql_s'));
    }

    public function queryId($id)
    {
        list($project, $conn) = $this->getAnalyzeConn();
        $db = DB::setPdo($this->getConnPdo($project, $conn));
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
        list($project, $conn) = $this->getAnalyzeConn();
        $auth = $request->get('auth');
        DB::setPdo($this->getConnPdo($project, $conn))->table('sql_log')->where('auth', $auth)->delete();
        return back();
    }

    public function ajaxQuerySql(Request $request)
    {
        list($project, $conn) = $this->getAnalyzeConn();
        $start = microtime(true);
        DB::setPdo($this->getConnPdo($project, $conn))->select($request->get('sql'));
        return round((microtime(true) - $start) * 1000, 2);
    }

    protected function getAnalyzeConn()
    {
        $redis = $this->getRedis('analyze');
        $user_id = $this->getUser('id');
        $key = $user_id . '_sql_analyze_conn';
        if (!$conn = $redis->get($key)) {
            $conn = 'core-dev';
            $redis->setex($key, 60 * 60 * 24, $conn);
        }
        return explode('-', $conn);
    }
}
