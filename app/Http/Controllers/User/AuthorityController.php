<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthorityController extends Controller
{
    public function listRole()
    {
        $roles = $this->builder->setModel('role')->get();
        return view('user.listRole', compact('roles'));
    }
    
    public function createRole()
    {
        return view('user.createRole');
    }
    
    public function saveRole(Request $request)
    {
        $this->builder->setModel('role')->create($request->all());
        return redirect('user/listRole');
    }
    
    public function editRole($role_id)
    {
        $role = $this->builder->setModel('role')->find($role_id);
        return view('user.editRole', compact('role'));
    }
    
    public function updateRole(Request $request, $role_id)
    {
        $role = $this->builder->setModel('role')->find($role_id);
        $role->fill($request->all())->save();
        return redirect('user/listRole');
    }
    
    public function editRolePower($role_id)
    {
        $role   = $this->builder->setModel('role')->find($role_id);
        $ids    = $this->builder->setModel('rolePower')->where('role_id', $role_id)->pluck('power_id')->toArray();
        $config = $this->builder->setModel('config')->where('key', 'group_sets')->first();
        $keys   = json_decode($config->value, true);
        $groups = [];
        $items  = $this->builder->setModel('power')->whereNotNull('group')->where('group', '<>', '')->get();
        foreach ($items as $item) {
            $groups[$item->group][] = $item;
        }
        return view('user.editRolePower', compact('role', 'ids', 'keys', 'groups'));
    }
    
    public function updateRolePower(Request $request, $role_id)
    {
        $ids       = $this->builder->setModel('rolePower')->where('role_id', $role_id)->pluck('power_id')->toArray();
        $power_ids = $request->get('power_id');
        $delete    = [];
        foreach ($ids as $id) {
            if (!in_array($id, $power_ids)) $delete[] = $id;
        }
        $create = [];
        $now    = Carbon::now()->toDateTimeString();
        foreach ($power_ids as $power_id) {
            if (in_array($power_id, $ids)) continue;
            $create[] = ['role_id' => $role_id, 'power_id' => $power_id, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->builder->setModel('rolePower')->insert($create);
        $this->builder->setModel('rolePower')->where('role_id', $role_id)->whereIn('power_id', $delete)->delete();
        $this->delUserCache($this->getUsersByRoleId($role_id));
        return back();
    }
    
    protected function getUsersByRoleId($role_id)
    {
        return $this->builder->setModel('accountRole')->where('role_id', $role_id)->pluck('account_id')->all();
    }
    
    public function listPower()
    {
        $powers = $this->builder->setModel('power')->get();
        $config = $this->builder->setModel('config')->where('key', 'group_sets')->first();
        $keys   = json_decode($config->value, true);
        $groups = [];
        $items  = $this->builder->setModel('power')->whereNotNull('group')->where('group', '<>', '')->get();
        foreach ($items as $item) {
            $groups[$item->group][] = $item;
        }
        return view('user.listPower', compact('powers', 'keys', 'groups'));
    }
    
    public function editPower($power_id)
    {
        $power  = $this->builder->setModel('power')->find($power_id);
        $config = $this->builder->setModel('config')->where('key', 'group_sets')->first();
        $groups = json_decode($config->value, true);
        return view('user.editPower', compact('power', 'groups'));
    }
    
    public function updatePower(Request $request, $power_id)
    {
        $power = $this->builder->setModel('power')->find($power_id);
        $power->fill($request->all())->save();
        return back();
    }
    
    public function initRoute()
    {
        $routes = $this->builder->setModel('power')->pluck('route')->toArray();
        $create = [];
        $now    = Carbon::now()->toDateTimeString();
        foreach (\Route::getRoutes() as $route) {
            $uri = $route->uri();
            $url = implode('|', $route->methods()).'@'.$uri;
            if (in_array($url, $routes)) continue;
            $create[] = [
                'route' => $url,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }
        $this->builder->setModel('power')->insert($create);
        return back();
    }
    
}
