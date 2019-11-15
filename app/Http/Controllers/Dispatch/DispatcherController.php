<?php

namespace App\Http\Controllers\Dispatch;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DispatcherController extends Controller
{
    protected $map = [
        'rail' => 'dispatcher_rail',
        'object' => 'dispatcher_object',
        'map' => 'dispatcher_object_rail_map',
        'field' => 'dispatcher_object_rail_fields'
    ];

    public function lists(Request $request)
    {
        $conn = $request->get('conn', 'dev');
        $type = $request->get('type', 'rail');
        $pdo = $this->getConnPdo('dispatcher', $conn);
        $rows = DB::setPdo($pdo)->table($this->map[$type])->get();
        return view('dispatch.list', compact('rows', 'conn', 'type'));
    }

    public function maps(Request $request)
    {
        $conn = $request->get('conn', 'dev');
        $rail = $request->get('rail', '');
        $object = $request->get('object', '');
        DB::setPdo($this->getConnPdo('dispatcher', $conn));
        $rails = DB::table($this->map['rail'])->get();
        $objects = DB::table($this->map['object'])->get()->keyBy('id');
        if (empty($object)) {
            return view('dispatch.maps', compact('rails', 'objects', 'conn', 'rail', 'object'));
        }
        $obj = $objects[$object];
        $code = $obj->code;
        $flags = [];
        foreach ($rails as $key => $value) {
            $flags[] = ['id' => $value->id, 'name' => $value->name, 'color' => $this->colors[$key]];
        }
        if (!empty($rail)) {
            $ids = DB::table($this->map['map'])->where('object_id', $object)->where('rail_id', $rail)->pluck('object_item_id')->toArray();
            $rows = DB::table($code)->whereIn('id', $ids)->get();
            $fields = DB::table($this->map['field'])->where('object_id', $object)->where('rail_id', $rail)->first();
            $fields = explode(',', $fields->fields);
            $outline = [];
            $rails = $rails->keyBy('id');
            foreach (DB::setPdo($this->getConnPdo($rails[$rail]->code, $conn))->table($code)->get() as $row) {
                $outline[$row->id] = $row;
            }
            $data = compact('fields');
        } else {
            $raw = $obj->display_fields;
            $ignore = explode(',', $obj->ignore_fields);
            $rows = DB::table($code)->get();
            $maps = DB::table($this->map['map'])->selectRaw('object_item_id, GROUP_CONCAT(rail_id) as rails')
                ->where('object_id', $object)->groupBy('object_item_id')->get()->keyBy('object_item_id');
            $outline = [];
            foreach ($rails as $key => $value) {
                foreach (DB::setPdo($this->getConnPdo($value->code, $conn))->table($code)->get() as $row) {
                    $outline[$row->id][$value->id] = $row;
                }
            }
            $data = compact('flags', 'maps', 'raw', 'ignore');
        }
        return view('dispatch.maps', array_merge(compact('rows', 'flags', 'outline', 'rails', 'objects', 'conn', 'rail', 'object'), $data));
    }

    public function mapsUpdate(Request $request)
    {
        $conn = $request->get('conn');
        DB::setPdo($this->getConnPdo('dispatcher', $conn));
        $object = DB::table($this->map['object'])->where('code', $request->get('object'))->first();
        $rail = $request->get('rail');
        $item_id = $request->get('item_id');
        $method = $request->get('method');
        $now = date('Y-m-d H:i:s');
        $data = ['object_id' => $object->id, 'rail_id' => $rail, 'object_item_id' => $item_id];
        $check = DB::table($this->map['map'])->where($data)->first();
        if ($method == 'append' && empty($check))
            DB::table($this->map['map'])->insert(array_merge($data, ['created_at' => $now, 'updated_at' => $now]));
        if ($method == 'remove' && !empty($check))
            DB::table($this->map['map'])->delete($check->id);
        return redirect('dispatch/dispatcher/maps?object=' . $object->id . '&conn=' . $conn);
    }

    public function syncItems(Request $request)
    {
        $conn = $request->get('conn');
        DB::setPdo($this->getConnPdo('dispatcher', $conn));
        $object = DB::table($this->map['object'])->find($request->get('object'));
        $item_id = $request->get('item_id');
        $item = DB::table($object->code)->find($item_id);
        $method = $request->get('method');
        $rail = DB::table($this->map['rail'])->find($request->get('rail'));
        $fields = DB::table($this->map['field'])->where('rail_id', $rail->id)->where('object_id', $object->id)->first()->fields;
        DB::setPdo($this->getConnPdo($rail->code, $conn));
        if ($method == 'insert') {
            if (!DB::table($object->code)->find($item_id)) {
                $create = [];
                foreach (explode(',', $fields) as $field) {
                    $create[$field] = $item->$field;
                }
                DB::table($object->code)->insert($create);
                $log_data = json_encode(['data' => $create, 'rail' => $rail->code, 'conn' => $conn]);
                $this->logContent('dispatch_sync', 'insert', $log_data, ['object_type' => $object->code, 'object_id' => $item_id]);
            }
        }
        if ($method == 'update') {
            $outline = DB::table($object->code)->find($item_id);
            $update = [];
            foreach ($outline as $key => $value) {
                if ($value != $item->$key) $update[$key] = $item->$key;
            }
            if (!empty($update)) {
                DB::table($object->code)->where('id', $item_id)->update($update);
                $log_data = json_encode(['data' => $update, 'rail' => $rail->code, 'conn' => $conn]);
                $this->logContent('dispatch_sync', 'update', $log_data, ['object_type' => $object->code, 'object_id' => $item_id]);
            }
        }
        return redirect('dispatch/dispatcher/maps?object=' . $object->id . '&rail=' . $rail->id . '&conn=' . $conn);
    }
}
