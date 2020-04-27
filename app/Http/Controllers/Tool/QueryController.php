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
        'core' => ['online4'],
        'learning' => ['online'],
        'kids' => ['online'],
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
        $sql = $request->get('sql');
        if (is_null($pdo = $this->getConnPdo($project, $conn)))
            return 'f';
        $rows = $pdo->query($sql)->fetchColumn();
        return json_encode($rows);
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
