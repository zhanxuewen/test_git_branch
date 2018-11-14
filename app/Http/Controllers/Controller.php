<?php

namespace App\Http\Controllers;

use Auth;
use App\Export;
use Maatwebsite\Excel\Facades\Excel;
use Validator;
use App\Helper\Builder;
use App\Foundation\PdoBuilder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, PdoBuilder;
    
    protected $user;
    
    protected $rules
        = [
            'username' => 'required|unique:user_account,username|max:10',
            'password' => 'required|confirmed|different:username',
        ];
    
    protected $builder;
    
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }
    
    protected function getUser($field = null)
    {
        if (empty($this->user)) $this->user = Auth::user();
        return is_null($field) ? $this->user : $this->user->$field;
    }
    
    protected function delUserCache($ids)
    {
        foreach ($ids as &$id) {
            $id = $id.'_routes';
        }
        return $this->getRedis('analyze')->del($ids);
    }
    
    protected function logContent($content)
    {
        $data = ['section' => '', 'log_type' => 'export', 'account_id' => $this->getUser('id'), 'content' => $content];
        $this->builder->setModel('log')->create($data);
    }
    
    protected function buildSql($query, $param)
    {
        return $this->$query($param);
    }
    
    protected function getZabbixToken()
    {
        return 'Cookie:zbx_sessionid=6b594637293a09024ff0c881f59d64c0';
    }
    
    protected function getManageToken()
    {
        $redis = $this->getRedis('analyze');
        if (!$token = $redis->get('manage_token')) {
            $url   = 'http://api.manage.wxzxzj.com/api/auth/login';
            $data  = 'phone=18202542402&password=fuminny&remberme=n';
            $data  = $this->curlPost($url, $data);
            $token = $data->token;
            $redis->set('manage_token', $token);
        }
        return $token;
    }
    
    protected function validate($request)
    {
        foreach (array_keys($request) as $key) {
            isset($this->rules[$key]) ? $rules[$key] = $this->rules[$key] : null;
        }
        $validator = Validator::make($request, isset($rules) ? $rules : []);
        return $validator->fails() ? $validator->messages()->toArray() : true;
    }
    
    protected function exportExcel($name, $record)
    {
        $this->logContent($name);
        return Excel::download(new Export($record), $name.'.xls');
    }
    
    protected function curlPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_HEADER, 0);  //设置头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  //设置post的数据
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        $data = json_decode($data)->data;
        return $data;
    }
    
}
