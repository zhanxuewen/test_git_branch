<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
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
        $roles = $this->builder->setModel('role')->get();
        $captcha = $this->getRedis('analyze')->get('forget_password_' . $account->username . '_captcha');
        return view('user.editAccount', compact('account', 'roles', 'captcha'));
    }

    public function resetPassword($account_id)
    {
        $account = $this->builder->setModel('account')->find($account_id);
        $account->password = 1111;
        $account->save();
        return redirect()->back()->with('success', 'Password Reset Success!');
    }

    public function updateAccount(Request $request, $account_id)
    {
        $account = $this->builder->setModel('account')->find($account_id);
        $username = $request->get('username');
        if ($account->username != $username) {
            $account->fill(['username' => $username])->save();
        }
        $role_id = $request->get('role_id');
        if (empty($accountRole = $this->builder->setModel('accountRole')->where('account_id', $account_id)->first())) {
            $this->builder->setModel('accountRole')->create(['account_id' => $account_id, 'role_id' => $role_id]);
        } else if ($accountRole->role_id != $role_id) {
            $accountRole->fill(['role_id' => $role_id])->save();
        }
        $this->delUsersRouteCache([$account_id]);
        return redirect('user/listAccount');
    }

}
