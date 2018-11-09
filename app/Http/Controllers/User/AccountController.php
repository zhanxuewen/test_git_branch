<?php

namespace App\Http\Controllers\User;

use Input;
use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function listAccount()
    {
        $accounts = $this->builder->setModel('account')->with('role')->get();
        return view('user.listAccount', compact('accounts'));
    }
    
    public function editAccount($account_id)
    {
        $account = $this->builder->setModel('account')->with('role')->find($account_id);
        $roles   = $this->builder->setModel('role')->get();
        return view('user.editAccount', compact('account', 'roles'));
    }
    
    public function updateAccount($account_id)
    {
        $account  = $this->builder->setModel('account')->find($account_id);
        $username = Input::get('username');
        if ($account->username != $username) {
            $account->fill(['username' => $username])->save();
        }
        $role_id = Input::get('role_id');
        if (empty($accountRole = $this->builder->setModel('accountRole')->where('account_id', $account_id)->first())) {
            $this->builder->setModel('accountRole')->create(['account_id' => $account_id, 'role_id' => $role_id]);
        } else if ($accountRole->role_id != $role_id) {
            $accountRole->fill(['role_id' => $role_id])->save();
        }
        $this->delUserCache([$account_id]);
        return redirect('user/listAccount');
    }
    
}
