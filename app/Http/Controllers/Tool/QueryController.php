<?php

namespace App\Http\Controllers\Tool;

use Carbon\Carbon;
use DB;
use App\Helper\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QueryController extends Controller
{
    protected $projects = [
        'core' => ['dev'],
        'learning' => ['dev'],
        'kids' => ['dev'],
    ];

    public function getQuery(Request $request)
    {
        $projects = $this->projects;
        return view('tool.query', compact('projects'));
    }

    public function ajaxQuery(Request $request)
    {
        $_conn = $request->get('conn');
        list($project, $conn) = explode('-', $_conn);
        $sql = trim($request->get('sql'));
        if (is_null($pdo = $this->getConnPdo($project, $conn)))
            return 'f';
        if (!strstr($sql, 'limit'))
            return 'l';
        $start = microtime(true);
        $sta = $pdo->query($sql);
        $time = round(microtime(true) - $start, 3);
        $keys = [];
        foreach ($first = $sta->fetchObject() as $key => $item) {
            $keys[] = $key;
        }
        $this->setModel('navicat')->create($this->buildNavicat($project, $conn, $sql, $time));
        return json_encode(['keys' => $keys, 'time' => $time, 'rows' => array_merge([$first], $sta->fetchAll())]);
    }

    protected function buildNavicat($project, $conn, $sql, $time)
    {
        return [
            'account_id' => $this->getUser('id'),
            'project' => $project,
            'connection' => $conn,
            'query' => $sql,
            'time' => $time,
            'created_at' => date('Y-m-d H:i:s')
        ];
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
