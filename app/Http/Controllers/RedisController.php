<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class RedisController extends Controller
{
    public function throttle(Request $request)
    {
        $date = Carbon::parse($request->get('date', date('Y-m-d')));
        if (!is_null($op = $request->get('op', null))) $date = $date->$op();
        $_key = 'throttle_record_' . $date->format('Ymd');
        $list = json_decode($this->getReadRedis('online')->get($_key));
        $keys = $_tokens = $ids = [];
        if (empty($list)) $list = [];
        foreach ($list as &$item) {
            list($method, $uri, $token) = explode('|', $item);
            $key = $method . '|' . $uri;
            if (!in_array($key, $keys)) $keys[] = $key;
            list($token, $count) = explode('#', $token);
            if (!in_array($token, $_tokens)) $_tokens[] = $token;
            if (!strstr($token, '.')) $ids[] = $token;
            $item = ['method' => $method, 'uri' => $uri, 'token' => $token, 'count' => $count];
        }
        $count = [count($keys), count($_tokens)];
        $accounts = empty($ids) ? [] : $this->fetchRows($this->getPdo('online')->query($this->list_accounts($ids)));
        $date = $date->toDateString();
        return view('redis.throttle', compact('count', 'accounts', 'list', 'date'));
    }

    protected function list_accounts($ids)
    {
        return "SELECT id, nickname, user_type_id, school_id FROM user_account WHERE id IN (" . implode(',', $ids) . ")";
    }

    protected function fetchRows($rows)
    {
        $record = [];
        if (empty($rows)) return $record;
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $item) {
                if (!is_numeric($key)) $data[$key] = $item;
            }
            $record[$data['id']] = $data;
        }
        return $record;
    }
}
