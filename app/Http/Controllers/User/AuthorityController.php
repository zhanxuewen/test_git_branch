<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthorityController extends Controller
{
    public function listRole()
    {
        $roles = $this->setModel('role')->get();
        return view('user.listRole', compact('roles'));
    }

    public function createRole()
    {
        return view('user.createRole');
    }

    public function saveRole(Request $request)
    {
        $this->setModel('role')->create($request->all());
        return redirect('user/listRole');
    }

    public function editRole($role_id)
    {
        $role = $this->setModel('role')->find($role_id);
        return view('user.editRole', compact('role'));
    }

    public function updateRole(Request $request, $role_id)
    {
        $role = $this->setModel('role')->find($role_id);
        $role->fill($request->all())->save();
        return redirect('user/listRole');
    }

    public function editRolePower($role_id)
    {
        $role = $this->setModel('role')->find($role_id);
        $ids = $this->setModel('rolePower')->where('role_id', $role_id)->pluck('power_id')->toArray();
        $keys = $this->setModel('group')->get();
        $groups = $this->setModel('power')->get()->groupBy('group_id');
        return view('user.editRolePower', compact('role', 'ids', 'keys', 'groups'));
    }

    public function updateRolePower(Request $request, $role_id)
    {
        $ids = $this->setModel('rolePower')->where('role_id', $role_id)->pluck('power_id')->toArray();
        $power_ids = $request->get('power_id');
        $delete = [];
        foreach ($ids as $id) {
            if (!in_array($id, $power_ids)) $delete[] = $id;
        }
        $create = [];
        $now = Carbon::now()->toDateTimeString();
        foreach ($power_ids as $power_id) {
            if (in_array($power_id, $ids)) continue;
            $create[] = ['role_id' => $role_id, 'power_id' => $power_id, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->setModel('rolePower')->insert($create);
        $this->setModel('rolePower')->where('role_id', $role_id)->whereIn('power_id', $delete)->delete();
        $this->delUsersRouteCache($this->getUsersByRoleId($role_id));
        return back();
    }

    protected function getUsersByRoleId($role_id)
    {
        return $this->setModel('accountRole')->where('role_id', $role_id)->pluck('account_id')->all();
    }

    public function listPower()
    {
        $powers = $this->setModel('power')->with('group')->get();
        $role_count = $this->setModel('role')->count();
        $rolePowers = $this->setModel('rolePower')->selectRaw('power_id, count(DISTINCT role_id) AS coo')->groupBy('power_id')->get();
        $rolePowers = collect($rolePowers)->keyBy('power_id');
        $labels = $this->setModel('group')->get();
        $groups = $this->setModel('power')->get()->groupBy('group_id');
        return view('user.listPower', compact('powers', 'rolePowers', 'role_count', 'labels', 'groups'));
    }

    public function editPower($power_id)
    {
        $power = $this->setModel('power')->find($power_id);
        $groups = $this->setModel('group')->get();
        return view('user.editPower', compact('power', 'groups'));
    }

    public function updatePower(Request $request, $power_id)
    {
        if ($request->get('delete') == 'need_delete') {
            $this->setModel('power')->where('id', $power_id)->delete();
            $this->setModel('rolePower')->where('power_id', $power_id)->delete();
            $user_ids = $this->setModel('account')->pluck('id')->toArray();
            $this->delUsersRouteCache($user_ids);
            return redirect('user/listPower');
        } else {
            $power = $this->setModel('power')->find($power_id);
            $power->fill($request->all())->save();
            return back();
        }
    }

    public function dispatchRoute(Request $request)
    {
        $power_id = $request->get('power_id');
        $old = $this->setModel('rolePower')->where('power_id', $power_id)->pluck('role_id');
        $all = $this->setModel('role')->where('is_active', 1)->select(['id', 'label'])->get();
        $create = [];
        $now = date('Y-m-d H:i:s');
        foreach ($all->pluck('id')->diff($old) as $id) {
            $create[] = ['role_id' => $id, 'power_id' => $power_id, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->setModel('rolePower')->insert($create);
        $user_ids = $this->setModel('account')->pluck('id')->toArray();
        $this->delUsersRouteCache($user_ids);
        return redirect('user/listPower');
    }

    public function initRoute()
    {
        $routes = $this->setModel('power')->pluck('route')->toArray();
        $create = [];
        $now = Carbon::now()->toDateTimeString();
        foreach (\Route::getRoutes() as $route) {
            $uri = $route->uri();
            $url = implode('|', $route->methods()) . '@' . $uri;
            if (in_array($url, $routes)) continue;
            $create[] = [
                'action' => $uri,
                'route' => $url,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        $this->setModel('power')->insert($create);
        return back();
    }

}
