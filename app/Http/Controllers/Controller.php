<?php

namespace App\Http\Controllers;

use Auth;
use Validator;
use App\Helper\Builder;
use App\Foundation\Excel;
use App\Foundation\Carbon;
use Illuminate\Mail\Message;
use App\Foundation\PdoBuilder;
use App\Library\ProsthesisLib;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Luminee\Reporter\Repositories\ReporterRepository;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests,
        PdoBuilder, Excel, Carbon;

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

    public function __construct(Builder $builder, ReporterRepository $reporter)
    {
        $this->builder = $builder;
        $this->aliOss = new ProsthesisLib();
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
        return $this->getRedis('analyze')->get($this->getUser('id') . '_per_page') ?: 30;
    }

    protected function validate($request)
    {
        foreach (array_keys($request) as $key) {
            isset($this->rules[$key]) ? $rules[$key] = $this->rules[$key] : null;
        }
        $validator = Validator::make($request, isset($rules) ? $rules : []);
        return $validator->fails() ? $validator->messages()->toArray() : true;
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

    protected function error($message)
    {
        return view('frame.error', compact('message'));
    }

}
