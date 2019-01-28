<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function ajaxCheck(Request $request)
    {
        $user_id = $request->get('user_id');
        $redis = $this->getReadRedis('analyze');
        $check = $redis->get('notice_check_' . $user_id);
        $check = 'has';
        if ($check != 'has') {
            return 0;
        }
        $notices = $this->builder->setModel('notice')->with('sender')->where('receiver_id', $user_id)->where('status', 1)
            ->where('has_read', 0)->where('is_visible', 1)->orderBy('created_at', 'desc')->paginate(10);
        return $notices->toJson();
    }
}
