<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiffController extends Controller
{
    public function diff(Request $request)
    {
        $project = $request->get('project', 'core');
        $type = $request->get('type', 'migration');
        $types = ['migration', 'seeder'];
        $projects = $this->getConnProjects();
        if (!in_array($project, $projects)) dd('error pro');
        $rows = [];
        foreach ($this->getConnections($project) as $conn) {
            $pdo = $this->getConnPdo($project, $conn);
            if (is_null($pdo)) continue;
            $rows[$conn] = \DB::setPdo($pdo)->table($type . 's')->pluck($type)->toArray();
        }
        return view('database.diff', compact('rows', 'projects', 'project', 'types', 'type'));
    }

    public function table_correct(Request $request)
    {
        $conn = $request->get('conn', 'dev');
        dd($conn);
    }

}
