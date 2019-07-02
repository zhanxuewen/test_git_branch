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
        $id = $request->get('id');
        $data = [];
        if (!is_null($id)) {
            $core_testbank = DB::setPdo($this->getPdo('online'))->table('testbank')->whereNull('deleted_at')->find($id);
            $core_extra = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_item_value')->first();
            $core_entities = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_extra_value')->get()->keyBy('id');
            $learn_testbank = DB::setPdo($this->getPdo($conn))->table('testbank')->where('core_related_id', $id)->whereNull('deleted_at')->first();
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
            $data = compact('core_testbank', 'core_extra', 'core_entities', 'learn_testbank', 'learn_extra', 'learn_entities', 'ass_testbank_s', 'ass_entities');
        }
        return view('bank.learning.search', array_merge(compact('id', 'conn'), $data));
    }

    public function syncEntity(Request $request)
    {
        $conn = $request->get('conn', 'online_learning');
        $core_id = $request->get('core_id');
        $learn_id = $request->get('learn_id');
        $ass_id = $request->get('ass_id');
        $data = [];
        if (!empty($core_id)) {
            $core = DB::setPdo($this->getPdo('online'))->table('testbank_entity')->find($core_id);
            $learn = DB::setPdo($this->getPdo($conn))->table('testbank_entity')->find($learn_id);
            $ass = DB::table('assessment_question_entity')->find($ass_id);
            $data = compact('core', 'learn', 'ass');
        }
        return view('bank.learning.sync', array_merge(compact('core_id', 'learn_id', 'ass_id', 'conn'), $data));
    }

}