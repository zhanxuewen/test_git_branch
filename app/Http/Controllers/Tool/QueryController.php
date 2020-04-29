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
        if (!stristr($sql, 'limit'))
            return 'l';
        preg_match('/(select|insert|update|delete)/i', $sql, $matches);
        $type = empty($matches) ? 'unknown' : strtolower($matches[0]);
        $start = microtime(true);
        $sta = $pdo->query($sql);
        $time = round(microtime(true) - $start, 3);
        $keys = [];
        foreach ($first = $sta->fetchObject() as $key => $item) {
            $keys[] = $key;
        }
        $this->setModel('navicat')->create($this->buildNavicat($project, $conn, $type, $sql, $time));
        return json_encode(['keys' => $keys, 'time' => $time, 'rows' => array_merge([$first], $sta->fetchAll())]);
    }

    public function showQueries(Request $request)
    {
        $account_id = $request->get('account_id', 0);
        $order_by = $request->get('order_by', 'created_at');
        $accounts = $this->setModel('navicat')->with('account')->selectRaw('distinct account_id')->get()->keyBy('account_id');
        $query = $this->setModel('navicat')->orderBy($order_by, 'desc');
        if ($account_id > 0) $query->where('account_id', $account_id);
        $queries = $query->paginate($this->getPerPage());
        return view('tool.show', compact('accounts', 'queries', 'account_id'));
    }

    protected function buildNavicat($project, $conn, $type, $sql, $time)
    {
        return [
            'account_id' => $this->getUser('id'),
            'project' => $project,
            'connection' => $conn,
            'type' => $type,
            'query' => $sql,
            'time' => $time,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

}
