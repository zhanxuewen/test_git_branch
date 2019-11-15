<?php

namespace App\Http\Controllers;

use Auth;
use Validator;
use App\Helper\Helper;
use App\Helper\Builder;
use App\Foundation\Excel;
use App\Foundation\Carbon;
use Illuminate\Mail\Message;
use App\Foundation\ArrayFunc;
use App\Foundation\PdoBuilder;
use Luminee\Prosthesis\AliOss;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Luminee\Reporter\Repositories\ReporterRepository;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests,
        PdoBuilder, ArrayFunc, Excel, Carbon;

    protected $user;

    protected $rules
        = [
            'username' => 'required|unique:user_account,username|max:10',
            'password' => 'required|confirmed|different:username',
        ];

    protected $connections = [
        'dev' => 'dev',
        'test' => 'test',
        'teach' => 'online',
        'trail' => 'online',
        'online' => 'online',
    ];

    protected $colors = ['#337ab7','#9ed189','#d9edf7','#fcf8e3','#f2dede'];

    protected $builder;
    protected $aliOss;

    protected $reporter;

    public function __construct(Builder $builder, AliOss $aliOss, ReporterRepository $reporter)
    {
        $this->builder = $builder;
        $this->aliOss = $aliOss;
        $this->reporter = $reporter;
    }

    protected function getUser($field = null)
    {
        if (empty($this->user)) $this->user = Auth::user();
        return is_null($field) ? $this->user : $this->user->$field;
    }

    protected function delUsersRouteCache($ids)
    {
        if (empty($ids)) return 0;
        foreach ($ids as &$id) {
            $id = $id . '_routes';
        }
        return $this->getRedis('analyze')->del($ids);
    }

    protected function delUserCache($id)
    {
        if (empty($id)) return 0;
        return $this->getRedis('analyze')->del([$id . '_routes', $id . '_info']);
    }

    /**
     * @param $scope
     * @param $action
     * @param $content
     * @param array | null $object
     */
    protected function logContent($scope, $action, $content, $object = null)
    {
        $pdo = \DB::getPdo();
        \DB::setPdo($this->getConnPdo('structure', 'dev'));
        $scope = $this->reporter->findScope($scope, 'code');
        $action = $this->reporter->findAction($action, 'code');
        $now = date('Y-m-d H:i:s');
        $data = ['scope_id' => $scope->id, 'account_id' => $this->getUser('id'), 'action_id' => $action->id, 'content' => $content, 'created_at' => $now, 'updated_at' => $now];
        if (!is_null($object)) $data = array_merge($data, $object);
        $this->reporter->createLog($data);
        \DB::setPdo($pdo);
    }

    protected function getPerPage()
    {
        return $this->getReadRedis('analyze')->get($this->getUser('id') . '_per_page') ?: 30;
    }

    protected function getZabbixToken()
    {
        return 'Cookie:zbx_sessionid=6b594637293a09024ff0c881f59d64c0';
    }

    protected function getManageToken()
    {
        $redis = $this->getRedis('analyze');
        if (!$token = $redis->get('manage_token')) {
            $url = 'http://api.manage.wxzxzj.com/api/auth/login';
            $data = 'phone=18202542402&password=' . env('MANAGE_PASSWORD') . '&remberme=n';
            $data = $this->curlPost($url, $data);
            $token = $data->token;
            $redis->setex('manage_token', 60 * 60 * 24, $token);
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

    protected function generateCaptcha($length)
    {
        return Helper::generateCaptcha($length);
    }

    protected function exportExcel($name, $record, $section = 'export_school', $id = null, $type = null)
    {
        $object = is_null($id) ? null : ['object_type' => $type, 'object_id' => $id];
        $this->logContent($section, 'export', $name, $object);
        return $this->export($name, $record);
    }

    protected function email($to, $blade, $data, $subject, $attach)
    {
        \Mail::send($blade, $data, function (Message $message) use ($to, $subject, $attach) {
            $message->to($to)->subject($subject);
            $message->attach($attach);
        });
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

    protected function curlGet($url, $decode = true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); //设置抓取的url
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //设置获取的信息以文件流的形式返回，而不是直接输出。
        $data = curl_exec($curl); //执行命令
        curl_close($curl); //关闭URL请求
        if ($decode) $data = json_decode($data)->data;
        return $data;
    }

    protected function error($message)
    {
        return view('frame.error', compact('message'));
    }

}
