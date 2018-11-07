<?php

namespace App\Http\Controllers\User;

use Input;
use Carbon\Carbon;
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
    
    public function saveRole()
    {
        $this->builder->setModel('role')->create(Input::all());
        return redirect('user/listRole');
    }
    
    public function editRole($role_id)
    {
        $role = $this->builder->setModel('role')->find($role_id);
        return view('user.editRole', compact('role'));
    }
    
    public function updateRole($role_id)
    {
        $role = $this->builder->setModel('role')->find($role_id);
        $role->fill(Input::all())->save();
        return redirect('user/listRole');
    }
    
    public function editRolePower($role_id)
    {
        $role   = $this->builder->setModel('role')->find($role_id);
        $ids    = $this->builder->setModel('rolePower')->where('role_id', $role_id)->lists('power_id')->toArray();
        $config = $this->builder->setModel('config')->where('key', 'group_sets')->first();
        $keys   = json_decode($config->value, true);
        $groups = [];
        $items  = $this->builder->setModel('power')->whereNotNull('group')->where('group', '<>', '')->get();
        foreach ($items as $item) {
            $groups[$item->group][] = $item;
        }
        return view('user.editRolePower', compact('role', 'ids', 'keys', 'groups'));
    }
    
    public function updateRolePower($role_id)
    {
        $ids       = $this->builder->setModel('rolePower')->where('role_id', $role_id)->lists('power_id')->toArray();
        $power_ids = Input::get('power_id');
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
        return back();
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
    
    public function updatePower($power_id)
    {
        $power = $this->builder->setModel('power')->find($power_id);
        $power->fill(Input::all())->save();
        return back();
    }
    
    public function initRoute()
    {
        $routes = $this->builder->setModel('power')->lists('route')->toArray();
        $create = [];
        $now    = Carbon::now()->toDateTimeString();
        foreach (\Route::getRoutes() as $route) {
            $uri = $route->getUri();
            $url = implode('|', $route->getMethods()).'@'.$uri;
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
