<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Input;

class RedisController extends Controller
{
    public function throttle()
    {
        $date   = Input::get('date', date('Y-m-d'));
        $data   = Redis::get('throttle_record_'.Carbon::parse($date)->format('Ymd'));
        $list   = json_decode($data);
        $keys   = [];
        $_tokens = [];
        if (empty($list)) {
            $list = [];
        } else {
            foreach ($list as &$item) {
                list($method, $uri, $token) = explode('|', $item);
                $key = $method.'|'.$uri;
                if (!in_array($key, $keys)) $keys[] = $key;
                list($token, $count) = explode('#', $token);
                if (!in_array($token, $_tokens)) $_tokens[] = $token;
                $item = ['method' => $method, 'uri' => $uri, 'token' => $token, 'count' => $count];
            }
        }
        return view('redis.throttle', compact('keys', '_tokens', 'list', 'date'));
    }
}
