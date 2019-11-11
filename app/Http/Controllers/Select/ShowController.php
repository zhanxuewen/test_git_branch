<?php

namespace App\Http\Controllers\Select;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * List Marketer
     */
    public function marketer()
    {
        $pdo = $this->getConnPdo('core', 'online');
        $marketers = $this->getRecord($pdo->query($this->list_marketer(2)));
        return view('select.marketer', compact('marketers'));
    }

    /**
     * List Label Tree
     * @param Request $request
     * @return mixed
     */
    public function labels(Request $request)
    {
        $type_id = $request->get('type_id', 1);
        $project = $request->get('project', 'core');
        $sort = $request->get('sort', 'desc');
        $pdo = $this->getConnPdo($project, 'online');
        $types = $this->getRecord($pdo->query("SELECT * FROM label_type"));
        $_labels = $pdo->query($this->list_labels($type_id, $sort));
        $labels = [];
        foreach ($_labels as $label) {
            $labels[$label['parent_id']][] = $label;
        }
        return view('select.label', compact('labels', 'types', 'type_id', 'project', 'sort'));
    }

    protected function list_marketer($role_id)
    {
        return "SELECT user_account.id, nickname, phone FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id INNER JOIN user ON user.id = user_account.user_id WHERE role_id = " . $role_id;
    }

    protected function list_labels($type_id, $sort = 'decs')
    {
        return "SELECT * FROM label WHERE label_type_id = " . $type_id . " AND deleted_at IS NULL ORDER BY power $sort, id ASC";
    }

    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $item) {
                !is_numeric($key) ? $data[$key] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }

}
