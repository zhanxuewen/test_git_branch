<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function getRegister()
    {
        return view('auth.register');
    }

    public function postRegister(Request $request)
    {
        $validator = $this->validate($request->all());
        if ($validator !== true) {
            return redirect()->back()->with('message', array_shift($validator)[0]);
        }
        $guest = $this->builder->setModel('role')->where('code', 'guest')->first();
        $user = $this->builder->setModel('account')->create($request->all());
        $this->builder->setModel('accountRole')->create(['account_id' => $user->id, 'role_id' => $guest->id]);
        return redirect()->route('login');
    }

    public function getForgetPassword(Request $request)
    {
        $username = $request->get('username');
        if (!$this->builder->setModel('account')->where('username', $username)->first()) {
            return redirect()->back()->with('message', "Can not find user $username");
        }
        $redis = $this->getRedis('analyze', true);
        $captcha = $this->generateCaptcha(6);
        $redis->setex('forget_password_' . $username . '_captcha', 60 * 10, $captcha);
        return view('auth.forgetPassword', compact('username'));
    }

    public function postForgetPassword(Request $request)
    {
        $username = $request->get('username');
        $account = $this->builder->setModel('account')->where('username', $username)->first();
        $captcha = $request->get('captcha');
        $redis = $this->getRedis('analyze');
        if ($redis->get('forget_password_' . $username . '_captcha') != $captcha) {
            return redirect()->route('login')->with('message', 'Captcha is not correct or not exist');
        }
        $account->password = $request->get('password');
        $account->save();
        $redis->del(['forget_password_' . $username . '_captcha']);
        return redirect()->route('login')->with('success', 'Password Reset Success');
    }

}
