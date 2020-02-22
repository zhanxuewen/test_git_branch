<?php

namespace App\Http\Controllers\Bank;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransmitController extends Controller
{
    protected $core_pdo;

    protected $learn_pdo;

    protected $type;

    protected $conn;

    protected $output = [];

    /**
     * @param Request $request
     * @return mixed
     */
    public function learningTestbank(Request $request)
    {
        $type = $request->get('type', 'bill');
        $id = $request->get('id');
        $conn = $request->get('conn');
        $this->type = $type;
        $this->conn = $conn;
        if (empty($id)) return view('bank.learning.transmit', compact('type', 'conn'));
        $this->core_pdo = $this->getConnPdo('core', $this->connections[$conn]);
        $this->learn_pdo = $this->getConnPdo('learning', $conn);
        switch ($type) {
            case 'bill':
                return $this->handleBill($id);
            case 'testbank':
                $testbank = DB::setPdo($this->core_pdo)->table('testbank')->where('id', $id)->first();
                return $this->handleTestbank($testbank);
            default:
                return $this->error('类型错误!');
        }
    }

    public function deleteBill(Request $request)
    {
        $ids = str_replace('，', ',', $request->get('ids'));
        $conn = $request->get('conn');
        $info = '';
        if (!empty($ids)) {
            DB::setPdo($this->getConnPdo('learning', $conn));
            foreach (explode(',', $ids) as $id) {
                if ($request->get('with_testbank')) {
                    $items = DB::table('testbank_collection')->where('core_related_id', $id)->first(['item_ids'])->item_ids;
                    DB::table('testbank_entity')->whereRaw("testbank_id in ($items)")->delete();
                    DB::table('testbank')->whereRaw("id in ($items)")->delete();
                }
                DB::table('testbank_collection')->where('core_related_id', $id)->delete();
                $info .= "Bill : $id has been deleted.<br>";
            }
            $this->logContent('bank_learn', 'delete', "Conn: $conn, Bills: $ids; Been Deleted.");
        }
        return view('bank.learning.delete_bill', compact('conn', 'info'));
    }

    protected function handleBill($id)
    {
        $c_bill = DB::setPdo($this->core_pdo)->table('testbank_collection')->where('id', $id)->first();
        $create = json_decode(json_encode($c_bill), true);
        $id = $create['id'];
        $create['core_related_id'] = $id;
        $create['is_public'] = 1;
        unset($create['id'], $create['system_label_ids']);
        $l_bill = DB::setPdo($this->learn_pdo)->table('testbank_collection')->where('core_related_id', $id)->whereNull('deleted_at')->first();
        if (!empty($l_bill)) return $this->response('题单 ' . $id . ' 已存在。', true);
        $item_ids = $create['item_ids'];
        if (strstr($item_ids, 'c')) return $this->response('题单 ' . $id . ' 包含引用题。', true);
        $ids = $this->getIds($item_ids);
        if ($ids === false) return $this->response('题单 ' . $id . ' 内大题错误!', true);
        $this->createBill($ids, $create);
        $output = implode(' ', $this->output);
        return $this->response($output . ' | 题单 ' . $id . ' 添加成功');
    }

    protected function createBill($ids, $create)
    {
        $_ids = [];
        foreach ($ids as $_id) {
            $_ids[] = $_id->id;
        }
        $create['item_ids'] = implode(',', $_ids);
        DB::setPdo($this->learn_pdo)->table('testbank_collection')->insert($create);
    }

    protected function getIds($item_ids)
    {
        $ids = DB::table('testbank')->selectRaw('id, core_related_id')
            ->whereRaw("core_related_id in ($item_ids)")->whereNull('deleted_at')->get()->keyBy('core_related_id')->toArray();
        $keys = array_keys($ids);
        $diff = array_diff(explode(',', $item_ids), $keys);
        if (!empty($diff)) {
            $testbank_s = DB::setPdo($this->core_pdo)->table('testbank')->whereIn('id', $diff)->whereNull('deleted_at')->get();
            foreach ($testbank_s as $testbank) {
                $this->handleTestbank($testbank);
            }
            $ids = DB::setPdo($this->learn_pdo)->table('testbank')->selectRaw('id, core_related_id')
                ->whereRaw("core_related_id in ($item_ids)")->whereNull('deleted_at')->get()->keyBy('core_related_id')->toArray();
            $keys = array_keys($ids);
            $diff = array_diff(explode(',', $item_ids), $keys);
            if (!empty($diff)) return false;
        }
        $_ids = [];
        foreach (explode(',', $item_ids) as $id) {
            $_ids[] = $ids[$id];
        }
        return $_ids;
    }

    protected function handleTestbank($testbank)
    {
        $create = json_decode(json_encode($testbank), true);
        $id = $create['id'];
        $create['core_related_id'] = $id;
        $create['is_public'] = 1;
        unset($create['id'], $create['system_label_ids']);
        $l_testbank = DB::setPdo($this->learn_pdo)->table('testbank')->where('core_related_id', $id)->whereNull('deleted_at')->first();
        if (!empty($l_testbank)) {
            return $this->type == 'testbank' ? $this->response('大题 ' . $id . ' 已存在。', true) : $this->output[] = '=';
        }
        $items = DB::setPdo($this->core_pdo)->table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->get()->keyBy('id');
        $new_id = DB::setPdo($this->learn_pdo)->table('testbank')->insertGetId($create);
        $ids = $this->handleEntity($items, $create['item_ids'], $new_id);
        DB::table('testbank')->where('id', $new_id)->update(['item_ids' => $ids]);
        return $this->type == 'testbank' ? $this->response('大题 ' . $id . ' 添加成功') : $this->output[] = '+';
    }

    // With Learning Pdo
    protected function handleEntity($items, $ids, $new_id)
    {
        $item_create = [];
        $extra = array_diff(array_keys($items->toArray()), explode(',', $ids));
        foreach (array_merge($extra, explode(',', $ids)) as $id) {
            $item = $items[$id];
            $item_create[] = [
                'testbank_id' => $new_id,
                'testbank_extra_value' => $item->testbank_extra_value,
                'testbank_item_value' => $item->testbank_item_value,
                'fix' => $item->fix,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'deleted_at' => $item->deleted_at
            ];
        }
        DB::table('testbank_entity')->insert($item_create);
        return DB::table('testbank_entity')->selectRaw('GROUP_CONCAT(id) as ids')->where('testbank_id', $new_id)
            ->whereNotNull('testbank_item_value')->whereNull('deleted_at')->first()->ids;
    }

    protected function response($message, $error = false)
    {
        return json_encode(['msg' => $message, 'error' => $error]);
    }

}