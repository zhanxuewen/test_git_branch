<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Input;

class RedisController extends Controller
{
    public function throttle()
    {
        $date    = Input::get('date', date('Y-m-d'));
        $data    = Redis::get('throttle_record_'.Carbon::parse($date)->format('Ymd'));
        $list    = json_decode($data);
        $keys    = [];
        $_tokens = [];
        $ids     = [];
        if (empty($list)) {
            $list = [];
        } else {
            foreach ($list as &$item) {
                list($method, $uri, $token) = explode('|', $item);
                $key = $method.'|'.$uri;
                if (!in_array($key, $keys)) $keys[] = $key;
                list($token, $count) = explode('#', $token);
                if (!in_array($token, $_tokens)) $_tokens[] = $token;
                if (!strstr($token, '.')) $ids[] = $token;
                $item = ['method' => $method, 'uri' => $uri, 'token' => $token, 'count' => $count];
            }
        }
        $pdo      = $this->getPdo('online');
        $accounts = $this->fetchRows($pdo->query($this->buildSql('list_accounts', $ids)), true);
        return view('redis.throttle', compact('keys', '_tokens', 'accounts', 'list', 'date'));
    }
    
    protected function list_accounts($ids)
    {
        return "SELECT id, nickname, user_type_id, school_id FROM user_account WHERE id IN (".implode(',', $ids).")";
    }
    
    protected function fetchRows($rows)
    {
        $record = [];
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
