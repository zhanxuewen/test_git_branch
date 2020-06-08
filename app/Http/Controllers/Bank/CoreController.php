<?php

namespace App\Http\Controllers\Bank;

use App\Http\Controllers\Bank\Model\Entity;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CoreController extends Controller
{
    public function resource(Request $request)
    {
        $search = $request->get('search', '');
        $conn = $request->get('conn', 'test');
        $type = $request->get('type', 'search');
        $id = $request->get('id', '');
        $dev_pdo = $this->getConnPdo('core', 'dev');
        $online_pdo = $this->getConnPdo('core', $conn);
        if (!empty($id)) {
            $data = $this->getData(DB::setPdo($dev_pdo)->table('resource')->find($id));
            if (count(DB::setPdo($online_pdo)->table('resource')->where('name', $data['name'])->get()) == 0) {
                DB::setPdo($online_pdo)->table('resource')->insert($data);
                $this->logContent('bank_core', 'insert', 'insert resource to [' . $conn . ']', ['object_type' => 'resource', 'object_id' => $id]);
            }
            return redirect()->back();
        }
        if ($type == 'sync') {
            if (!empty($search)) {
                $name = '%' . $search . '%';
                $dev_res = DB::setPdo($dev_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                $online_res = DB::setPdo($online_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                foreach ($online_res as $name => $item) {
                    if ($item->url != $dev_res[$name]->url) {
                        \DB::table('resource')->where('id', $item->id)->update(['url' => $dev_res[$name]->url]);
                    }
                }
                $this->logContent('bank_core', 'update', 'sync resource url by ' . $search);
            }
            return redirect()->back();
        }
        if ($type == 'search') {
            $dev_res = $online_res = [];
            if (!empty($search)) {
                $name = '%' . $search . '%';
                $dev_res = DB::setPdo($dev_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                $online_res = DB::setPdo($online_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
            }
        }
        return view('bank.core.resource', compact('dev_res', 'online_res', 'search', 'type', 'conn'));
    }

    public function updateTestbankEntity(Request $request)
    {
        $conn = $request->get('conn', 'dev');
        $id = $request->get('id', '');
        $type = $request->get('type', 'search');
        if (empty($id)) return view('bank.core.testbankEntity', compact('conn', 'id', 'type'));
        $pdo = $this->getConnPdo('core', $conn);
        DB::setPdo($pdo);
        if ($type == 'search') {
            $model = new Entity();
            $entities = $model->where('testbank_id', $id)->get()->toArray();
            return view('bank.core.testbankEntity', compact('conn', 'id', 'type', 'entities'));
        }
        if ($type == 'sync') {
            $entity_id = $request->get('entity_id');
            if (empty($entity_id)) dd('请选择小题');
            $search = trim(json_encode($request->get('search')), '"');
            $replace = trim(json_encode($request->get('replace')), '"');
            if ($request->get('quote') == 1) {
                $search = '"' . $search . '"';
                $replace = '"' . $replace . '"';
            }
            $field = $request->get('field') == 0 ? 'testbank_item_value' : 'testbank_extra_value';
            $value = DB::table('testbank_entity')->where('id', $entity_id)->first()->$field;
            $value = str_replace($search, $replace, $value);
            DB::table('testbank_entity')->where('id', $entity_id)->update([$field => $value]);
            $model = new Entity();
            $entities = $model->where('testbank_id', $id)->get()->toArray();
            $type = 'search';
            return view('bank.core.testbankEntity', compact('conn', 'id', 'type', 'entities'));
        }
        dd('Wrong Type');
    }

    protected function getData($item)
    {
        $data = json_decode(json_encode($item), true);
        unset($data['id']);
        return $data;
    }

}