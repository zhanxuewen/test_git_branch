<?php

namespace App\Http\Controllers\Tool;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ThirdController extends Controller
{
    protected $result = [];

    public function getTemplates(Request $request)
    {
        $type = $request->get('type', 'message');
        $project = $request->get('project', 'core');
        $conn = $request->get('conn', 'dev');
        \DB::setPdo($this->getConnPdo('third_party', $conn));
        $rows = \DB::table('sms_templates')->where('type', $type)->where('project', $project)->get();
        $projects = \DB::table('sms_templates')->distinct()->pluck('project')->toArray();
        return view('tool.templates', compact('rows', 'projects', 'type', 'project', 'conn'));
    }

    public function saveTemplate(Request $request)
    {
        $type = $request->get('type', 'message');
        $project = $request->get('project', 'core');
        $conn = $request->get('conn', 'dev');
        $code = $request->get('code');
        $template = $request->get('template');
        \DB::setPdo($this->getConnPdo('third_party', $conn));
        $now = date('Y-m-d H:i:s');
        $data = [
            'type' => $type,
            'project' => $project,
            'code' => $code,
            'template' => $template,
            'created_at' => $now,
            'updated_at' => $now
        ];
        \DB::table('sms_templates')->insert($data);
        return redirect($request->get('url'));
    }

}
