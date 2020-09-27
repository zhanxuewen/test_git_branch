<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    protected  $groups = ['backend'=>'后端','frontend'=>'前端'];
    protected  $projects = ['core'=>'在线助教','learning'=>'百项过','kids'=>'优英迪诺'];
    protected  $sub_groups=['core'=>'core_api','learning'=>'learning','kids'=>'kids'];
    public function getBranch(Request $request)
    {
        $group = $request->get('group', null);
        $project = $request->get('project', null);
        $is_available = $request->get('is_available', '1');

        $groups =  $this->groups;
        $projects = $this->projects;
        $query = $this->setModel('branch')->orderBy('id', 'desc');
        if (!empty($group)) $query->where('group', $group);
        if (!empty($project)) $query->where('project', $project);
        if ($is_available =='1') {
            $query->where('is_available', $is_available);
        }
        $branch_sets = $query->paginate($this->getPerPage());
        $this->updateProject('core','backend');
        return view('branch.branch', compact('branch_sets','group','project','is_available','groups', 'projects'));
    }

    public function postBranch(Request $request)
    {
        $group = $request->get('group', 'backend');
        $project = $request->get('project', 'core');
        $sub_group = $this->sub_groups[$project]??'';
        $branch = $request->get('branch', '');
        $url = $request->get('url', '');
        $label = $request->get('label', '');
        $data = ['group' => $group,'sub_group'=>$sub_group,'project'=>$project,'branch'=>$branch,'url'=>$url,'label'=>$label,'is_available'=>1];
        $pdo = $this->setModel('branch')->create($data);
        $this->updateProject($project,$group);
        return back()->with('success','操作成功');
    }
    public function removeBranch(Request $request)
    {
        $id = intval($request->get('id', null));
        $project = $request->get('project', null);
        $group = $request->get('group', null);
        if (!empty($id)) {
            $this->setModel('branch')->where('id',$id)->update(['is_available' => 0]);
        }
        $this->updateProject($project,$group);
        return back()->with('success','操作成功');
    }

    /**
     * 更新对应项目redis
     * @param $project
     * @param $group
     */
    protected function updateProject($project,$group)
    {
        $redis = $this->getRedis('analyze');
        $res = $this->setModel('branch')->orderBy('id', 'desc')->where('project', $project)->where('group', $group)->where('is_available', 1)->get();
        $redis->set($project.'_dev_branch_set',json_encode($res->toArray()));
    }

}
