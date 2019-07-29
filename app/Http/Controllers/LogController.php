<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogController extends Controller
{
    public function logs(Request $request)
    {
        $user = $request->get('user', 0);
        $type = $request->get('type', 'all');
        $users = $this->builder->setModel('account')->selectRaw('id, username')->get();
        $types = $this->builder->setModel('log')->distinct()->pluck('log_type')->toArray();
        $query = $this->builder->setModel('log')->with('account');
        if ($user > 0) $query->where('account_id', $user);
        if ($type != 'all') $query->where('log_type', $type);
        $logs = $query->orderBy('id', 'desc')->paginate($this->getPerPage());
        return view('log.list', compact('logs', 'users', 'user', 'type', 'types'));
    }
}
