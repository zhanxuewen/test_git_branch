<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogController extends Controller
{
    public function logs(Request $request)
    {
        $user = $request->get('user', 0);
        $scope = $request->get('scope', null);
        $action = $request->get('action', null);
        $users = $this->setModel('account')->selectRaw('id, username, nickname')->get()->keyBy('id');
        $scopes = $this->reporter->listScopes()->keyBy('id');
        $actions = $this->reporter->listActions()->keyBy('id');
        $query = $this->setModel('log')->orderBy('id', 'desc');
        if ($user > 0) $query->where('account_id', $user);
        if (!empty($scope)) $query->where('scope_id', $scope);
        if (!empty($action)) $query->where('action_id', $action);
        $logs = $query->paginate($this->getPerPage());
        return view('log.list', compact('logs', 'users', 'user', 'scope', 'action', 'scopes', 'actions'));
    }
}
