<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function lists()
    {
        $user_id = $this->getUser('id');
        $notices = $this->builder->setModel('notice')->where(function ($query) use ($user_id) {
            $query->where('receiver_id', $user_id)->orWhere('sender_id', $user_id);
        })->where('status', 1)->get();
        $user_ids = $notices->pluck('sender_id')->merge($notices->pluck('receiver_id'));
        $system = collect([['id' => 0, 'username' => 'System', 'avatar' => '/asset/image/system.png']]);
        $users = $this->builder->setModel('account')->select(['id', 'username', 'avatar'])->whereIn('id', $user_ids)->orderBy('id', 'desc')->get()->toArray();
        $users = $system->merge($users)->keyBy('id');
        $groups = [];
        foreach ($notices as $notice) {
            $notice->sender_id == $user_id ? $groups[$notice->receiver_id][] = $notice : $groups[$notice->sender_id][] = $notice;
        }
        return view('notice.lists', compact('groups', 'users'));
    }

    public function hasRead(Request $request)
    {
        $id = $request->get('id');
        $this->builder->setModel('notice')->where('id', $id)->update(['has_read' => 1]);
        return 1;
    }

    public function ajaxCheck()
    {
        $notices = $this->builder->setModel('notice')->with('sender')->where('receiver_id', $this->getUser('id'))->where('status', 1)
            ->where('has_read', 0)->where('is_visible', 1)->orderBy('created_at', 'desc')->paginate(10);
        return $notices->toJson();
    }
}
