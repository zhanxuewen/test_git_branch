<?php

namespace App\Http\Controllers\Bank;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LearningController extends Controller
{
    public function searchTestbank(Request $request)
    {
        $conn = $request->get('conn', 'online');
        $type = $request->get('type', 'testbank');
        $id = $request->get('id');
        $data = [];
        if (!is_null($id)) {
            if ($type == 'testbank') $data = $this->getTestbank($id, $conn);
            if ($type == 'bill') $data = $this->getBill($id, $conn);
        }
        return view('bank.learning.search', array_merge(compact('id', 'conn', 'type'), $data));
    }

    protected function getTestbank($id, $conn)
    {
        $core_testbank = DB::setPdo($this->getConnPdo('core', $this->connections[$conn]))->table('testbank')->whereNull('deleted_at')->find($id);
        $core_extra = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_item_value')->first();
        $core_entities = DB::table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->whereNull('testbank_extra_value')->get()->keyBy('id');
        $learn_testbank = DB::setPdo($this->getConnPdo('learning', $conn))->table('testbank')->where('core_related_id', $id)->whereNull('deleted_at')->first();
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

    protected function getBill($id, $conn)
    {
        $core_bill = DB::setPdo($this->getConnPdo('core', $this->connections[$conn]))->table('testbank_collection')->whereNull('deleted_at')->find($id);
        $core_testbank_s = DB::table('testbank')->whereRaw('id in (' . $core_bill->item_ids . ')')->whereNull('deleted_at')->orderByRaw("FIND_IN_SET(id, '" . $core_bill->item_ids . "')")->get();
        $learn_bill = DB::setPdo($this->getConnPdo('learning', $conn))->table('testbank_collection')->where('core_related_id', $id)->whereNull('deleted_at')->first();
        if (!empty($learn_bill)) {
            $learn_testbank_s = DB::table('testbank')->whereRaw('id in (' . $learn_bill->item_ids . ')')->whereNull('deleted_at')->orderByRaw("FIND_IN_SET(id, '" . $learn_bill->item_ids . "')")->get();
            return compact('core_bill', 'core_testbank_s', 'learn_bill', 'learn_testbank_s');
        } else {
            return compact('core_bill', 'core_testbank_s');
        }
    }

    public function syncEntity(Request $request)
    {
        $conn = $request->get('conn', 'online');
        $core_id = $request->get('core_id');
        $learn_id = $request->get('learn_id');
        $ass_id = $request->get('ass_id');
        $pdo = $this->getConnPdo('learning', $conn);
        $data = [];
        if (!empty($core_id)) {
            $core = DB::setPdo($this->getConnPdo('core', $this->connections[$conn]))->table('testbank_entity')->find($core_id);
            DB::setPdo($pdo);
            if ($request->get('type') == 'update') {
                $ass = DB::table('assessment_question_entity')->find($ass_id);
                DB::table('testbank_entity')->where('id', $learn_id)->update(['testbank_item_value' => $core->testbank_item_value]);
                DB::table('assessment_question_entity')->where('id', $ass_id)->update($this->buildAssItem($ass, $core->testbank_item_value));
                $content = "Conn: $conn, Learning: $learn_id, Ass: $ass_id; Update Entity";
                $this->logContent('bank_learn', 'replace', $content);
            }
            if ($request->get('type') == 'update_word') {
                $ass = DB::table('assessment_question_entity')->find($ass_id);
                DB::table('testbank_entity')->where('id', $learn_id)->update(['testbank_item_value' => $core->testbank_item_value]);
                DB::table('assessment_question_entity')->where('id', $ass_id)->update($this->buildWordAssItem($ass, $core->testbank_item_value));
                $content = "Conn: $conn, Learning: $learn_id, Ass: $ass_id; Update Word Entity";
                $this->logContent('bank_learn', 'replace', $content);
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

    protected function buildWordAssItem($ass, $value)
    {
        $item = json_decode($ass->item_value);
        foreach (json_decode($value) as $k => $v) {
            $item->$k = $v;
        }
        return ['item_value' => json_encode($item)];
    }

    public function syncArticle(Request $request)
    {
        $conn = $request->get('conn', 'online');
        $core_id = $request->get('core_id');
        $learn_id = $request->get('learn_id');
        $ques_id = $request->get('ques_id');
        $pdo = $this->getConnPdo('learning', $conn);
        $data = [];
        if (!empty($core_id)) {
            $core = DB::setPdo($this->getConnPdo('core', $this->connections[$conn]))->table('testbank_entity')->find($core_id);
            DB::setPdo($pdo);
            if ($request->get('type') == 'update') {
                $ques = DB::table('assessment_question')->find($ques_id);
                DB::table('testbank_entity')->where('id', $learn_id)->update(['testbank_extra_value' => $core->testbank_extra_value]);
                DB::table('assessment_question')->where('id', $ques_id)->update($this->buildAssContent($ques, $core->testbank_extra_value));
                $content = "Conn: $conn, Learning: $learn_id, Ques: $ques_id; Update Article";
                $this->logContent('bank_learn', 'replace', $content);
            }
            $learn = DB::table('testbank_entity')->find($learn_id);
            $ques = DB::table('assessment_question')->find($ques_id);
            $data = compact('core', 'learn', 'ques');
        }
        return view('bank.learning.syncArticle', array_merge(compact('core_id', 'learn_id', 'ques_id', 'conn'), $data));
    }

    protected function buildAssContent($ques, $value)
    {
        $item = json_decode($ques->content);
        foreach (json_decode($value) as $k => $v) {
            $item->$k = $v;
        }
        return ['content' => json_encode($item)];
    }

    public function appendOrRemoveEntity(Request $request)
    {
        $conn = $request->get('conn');
        $type = $request->get('type');
        $id = $request->get('entity_id');
        $core_id = 0;
        if ($type == 'append') $core_id = $this->appendEntity($conn, $id);
        if ($type == 'remove') $core_id = $this->removeEntity($conn, $id);
        return redirect('bank/learning/search/testbank?id=' . $core_id . '&type=testbank&conn=' . $conn);
    }

    protected function appendEntity($conn, $id)
    {
        DB::setPdo($this->getConnPdo('core', $this->connections[$conn]));
        $entity = DB::table('testbank_entity')->find($id);
        $core_id = $entity->testbank_id;
        DB::setPdo($this->getConnPdo('learning', $conn));
        $learn_t = DB::table('testbank')->where('core_related_id', $core_id)->first();
        $learn_t_id = $learn_t->id;
        $learn_e_id = $this->insertEntity($entity, $learn_t_id);
        $update = ['item_ids' => rtrim($learn_t->item_ids) . ',' . $learn_e_id];
        DB::table('testbank')->where('id', $learn_t_id)->update($update);
        $search = '{"id":' . $learn_t->id . ',%';
        $ass_t_s = DB::table('assessment_question')->where('content', 'like', $search)->whereNull('deleted_at')->get();
        $learn_e = DB::table('testbank_entity')->find($learn_e_id);
        foreach ($ass_t_s as $ass_t) {
            $ass_t_id = $ass_t->id;
            $ass_e_id = $this->insertAssEntity($learn_e, $ass_t_id);
            $update = ['item_ids' => rtrim($ass_t->item_ids) . ',' . $ass_e_id];
            DB::table('assessment_question')->where('id', $ass_t_id)->update($update);
        }
        return $core_id;
    }

    protected function insertEntity($entity, $t_id)
    {
        $create = [
            'testbank_id' => $t_id,
            'testbank_extra_value' => $entity->testbank_extra_value,
            'testbank_item_value' => $entity->testbank_item_value,
            'fix' => $entity->fix,
            'created_at' => $entity->created_at,
            'updated_at' => $entity->updated_at,
            'deleted_at' => $entity->deleted_at
        ];
        return DB::table('testbank_entity')->insertGetId($create);
    }

    protected function insertAssEntity($entity, $ass_id)
    {
        $now = date('Y-m-d H:i:s');
        $item = json_decode($entity->testbank_item_value, true);
        foreach ($item as $key => $value) {
            $entity->$key = $value;
        }
        unset($entity->testbank_extra_value);
        unset($entity->testbank_item_value);
        unset($entity->deleted_at);
        $data = [
            'question_id' => $ass_id,
            'item_value' => json_encode($entity),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        return DB::table('assessment_question_entity')->insertGetId($data);
    }

    protected function removeEntity($conn, $id)
    {
        list($type, $_id) = explode('_', $id);
        DB::setPdo($this->getConnPdo('learning', $conn));
        $core_id = 0;
        if ($type == 'l') {
            $entity = DB::table('testbank_entity')->find($_id);
            $learn_t = DB::table('testbank')->find($entity->testbank_id);
            $core_id = $learn_t->core_related_id;
            DB::table('testbank_entity')->where('id', $_id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $items = explode(',', str_replace($_id, '', $learn_t->item_ids));
            $update = ['item_ids' => implode(',', array_filter($items))];
            DB::table('testbank')->where('id', $learn_t->id)->update($update);
        }
        if ($type == 'a') {
            $a_entity = DB::table('assessment_question_entity')->find($_id);
            $ass_t = DB::table('assessment_question')->find($a_entity->question_id);
            $core_id = json_decode($ass_t->content)->core_related_id;
            DB::table('assessment_question_entity')->where('id', $_id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $items = explode(',', str_replace($_id, '', $ass_t->item_ids));
            $update = ['item_ids' => implode(',', array_filter($items))];
            DB::table('assessment_question')->where('id', $ass_t->id)->update($update);
        }
        return $core_id;
    }

}