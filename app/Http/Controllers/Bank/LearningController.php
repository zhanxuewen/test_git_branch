<?php

namespace App\Http\Controllers\Bank;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LearningController extends Controller
{
    public function searchTestbank(Request $request)
    {
        $conn = $request->get('conn', 'online_learning');
        $type = $request->get('type', 'testbank');
        $id = $request->get('id');
        $data = [];
        if (!is_null($id)) {
            if ($type == 'testbank') $data = $this->getTestbank($id, $this->getPdo($conn));
            if ($type == 'bill') $data = $this->getBill($id, $this->getPdo($conn));
        }
        return view('bank.learning.search', array_merge(compact('id', 'conn', 'type'), $data));
    }

    protected function getTestbank($id, $pdo)
    {
        $core_testbank = DB::setPdo($this->getPdo('online'))->table('testbank')->whereNull('deleted_at')->find($id);
        $core_extra = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_item_value')->first();
        $core_entities = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_extra_value')->get()->keyBy('id');
        $learn_testbank = DB::setPdo($pdo)->table('testbank')->where('core_related_id', $id)->whereNull('deleted_at')->first();
        if (!empty($learn_testbank)) {
            $learn_t_id = $learn_testbank->id;
            $learn_extra = DB::table('testbank_entity')->where('testbank_id', $learn_t_id)->whereNull('deleted_at')->whereNull('testbank_item_value')->first();
            $learn_entities = DB::table('testbank_entity')->where('testbank_id', $learn_t_id)->whereNull('deleted_at')->whereNull('testbank_extra_value')->get()->keyBy('id');
            $search = '{"id":' . $learn_t_id . ',%';
            $ass_testbank_s = DB::table('assessment_question')->where('content', 'like', $search)->whereNull('deleted_at')->get();
            $ass_entities = [];
            foreach ($ass_testbank_s as $ass_testbank) {
                $_id = $ass_testbank->id;
                $ass_entities[$_id] = DB::table('assessment_question_entity')->where('question_id', $_id)->whereNull('deleted_at')->get()->keyBy('id');
            }
            return compact('core_testbank', 'core_extra', 'core_entities', 'learn_testbank', 'learn_extra', 'learn_entities', 'ass_testbank_s', 'ass_entities');
        } else {
            return compact('core_testbank', 'core_extra', 'core_entities');
        }
    }

    protected function getBill($id, $pdo)
    {
        $core_bill = DB::setPdo($this->getPdo('online'))->table('testbank_collection')->whereNull('deleted_at')->find($id);
        $core_testbank_s = DB::table('testbank')->whereRaw('id in (' . $core_bill->item_ids . ')')->whereNull('deleted_at')->orderByRaw("FIND_IN_SET(id, '" . $core_bill->item_ids . "')")->get();
        $learn_bill = DB::setPdo($pdo)->table('testbank_collection')->where('core_related_id', $id)->whereNull('deleted_at')->first();
        if (!empty($learn_bill)) {
            $learn_testbank_s = DB::table('testbank')->whereRaw('id in (' . $learn_bill->item_ids . ')')->whereNull('deleted_at')->orderByRaw("FIND_IN_SET(id, '" . $learn_bill->item_ids . "')")->get();
            return compact('core_bill', 'core_testbank_s', 'learn_bill', 'learn_testbank_s');
        } else {
            return compact('core_bill', 'core_testbank_s');
        }
    }

    public function syncEntity(Request $request)
    {
        $conn = $request->get('conn', 'online_learning');
        $core_id = $request->get('core_id');
        $learn_id = $request->get('learn_id');
        $ass_id = $request->get('ass_id');
        $pdo = $this->getPdo($conn);
        $data = [];
        if (!empty($core_id)) {
            $core = DB::setPdo($this->getPdo('online'))->table('testbank_entity')->find($core_id);
            DB::setPdo($pdo);
            if ($request->get('type') == 'update') {
                $ass = DB::table('assessment_question_entity')->find($ass_id);
                DB::table('testbank_entity')->where('id', $learn_id)->update(['testbank_item_value' => $core->testbank_item_value]);
                DB::table('assessment_question_entity')->where('id', $ass_id)->update($this->buildAssItem($ass, $core->testbank_item_value));
                $content = "Conn: $conn, Learning: $learn_id, Ass: $ass_id; Update Entity";
                $this->logContent('Bank', 'replace', $content);
            }
            $learn = DB::table('testbank_entity')->find($learn_id);
            $ass = DB::table('assessment_question_entity')->find($ass_id);
            $data = compact('core', 'learn', 'ass');
        }
        return view('bank.learning.sync', array_merge(compact('core_id', 'learn_id', 'ass_id', 'conn'), $data));
    }

    protected function buildAssItem($ass, $value)
    {
        $item = json_decode($ass->item_value);
        $data = json_encode(['id' => $item->id, 'testbank_id' => $item->testbank_id, 'created_at' => $item->created_at, 'updated_at' => $item->updated_at]);
        return ['item_value' => str_replace('}__{', ',', $data . '__' . $value)];
    }

}