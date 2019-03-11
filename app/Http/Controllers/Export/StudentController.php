<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    protected $titles
        = [
            '_id' => 'id',
            '_name' => '名称',
            'labels' => '标签',
            'mark_name' => '备注名',
            'student_id' => '学生ID',
            'created_at' => '创建时间',
            'vocabulary' => '单词',
            'translation' => '解释',
            'joined_time' => '加入时间',
            'vanclass_id' => '班级ID',
            'vanclass_name' => '班级名',
            'fluency_level' => '熟练度',
            'last_finish_at' => '最后完成'
        ];

    public function student()
    {
        return view('export.student');
    }

    public function postExport(Request $request)
    {
        $query = $request->get('query');
        $request->filled('label_id') ? $params['label_id'] = $request->get('label_id', null) : null;
        $request->filled('label_ids') ? $params['label_ids'] = $this->handleIds($request->get('label_ids', null)) : null;
        $request->filled('student_id') ? $params['student_id'] = $request->get('student_id', null) : null;
        $request->filled('teacher_id') ? $params['teacher_id'] = $request->get('teacher_id', null) : null;
        isset($params) or die('没有参数');
        $pdo = $this->getPdo('online');
        $rows = $pdo->query($this->$query($params));
        $name = $query . '_' . $this->handleTableName($params);
        return $this->exportExcel($name, $this->getRecord($rows));
    }

    protected function handleTableName($params)
    {
        foreach ($params as &$param) {
            $param = substr($param, 0, 20);
        }
        return implode('_', $params);
    }

    protected function handleIds($ids)
    {
        $array = array_unique(explode(',', $ids));
        return implode(',', $array);
    }

    protected function student_fluency($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vocabulary, fluency_level, last_finish_at FROM word_student_fluency INNER JOIN wordbank ON wordbank.id = word_student_fluency.wordbank_id WHERE student_id = " . $params['student_id'] . " AND word_student_fluency.fluency_level > 0 ORDER BY	last_finish_at DESC";
    }

    protected function fluency_record($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vocabulary, word_student_fluency_record.fluency_level, word_student_fluency_record.created_at FROM word_student_fluency INNER JOIN wordbank ON wordbank.id = word_student_fluency.wordbank_id INNER JOIN word_student_fluency_record ON word_student_fluency_record.student_fluency_id = word_student_fluency.id WHERE student_id = " . $params['student_id'] . " AND word_student_fluency.fluency_level > 0 ORDER BY word_student_fluency_record.created_at DESC";
    }

    protected function teacher_word_homework($params)
    {
        !isset($params['teacher_id']) ? die('没有 教师ID') : null;
        return "SELECT word_homework.name, word_homework.id, word_homework_student.vanclass_id, vanclass.name as vanclass_name, group_concat(word_homework_student.label_ids) AS labels, word_homework.created_at FROM word_homework_student INNER JOIN word_homework ON word_homework.id = word_homework_student.word_homework_id INNER JOIN vanclass ON vanclass.id = word_homework_student.vanclass_id WHERE word_homework.teacher_id = " . $params['teacher_id'] . " GROUP BY word_homework_student.vanclass_id, word_homework.id";
    }

    protected function student_vanclass_word($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vanclass.`name`, vanclass.id, mark_name, joined_time, group_concat(word_homework.`name`) AS _name, group_concat(word_homework.id) AS _id FROM vanclass_student INNER JOIN vanclass ON vanclass_student.vanclass_id = vanclass.id INNER JOIN word_homework_student ON word_homework_student.student_id = vanclass_student.student_id INNER JOIN word_homework ON word_homework.id = word_homework_student.word_homework_id WHERE vanclass_student.student_id = " . $params['student_id'] . " GROUP BY vanclass.id";
    }

    protected function get_labels($params)
    {
        !isset($params['label_ids']) ? die('没有 标签ID') : null;
        return "SELECT label.id, concat_ws(' - ', label_5.`name`, label_4.`name`, label_3.`name`, label_2.`name`, label.`name`) AS _name FROM label LEFT JOIN label AS label_2 ON label.parent_id = label_2.id LEFT JOIN label AS label_3 ON label_2.parent_id = label_3.id LEFT JOIN label AS label_4 ON label_3.parent_id = label_4.id LEFT JOIN label AS label_5 ON label_4.parent_id = label_5.id WHERE label.id IN (" . $params['label_ids'] . ") AND label.deleted_at IS NULL";
    }

    protected function label_wordbank($params)
    {
        !isset($params['label_id']) ? die('没有 标签ID') : null;
        return "SELECT vocabulary FROM wordbank_translation_label INNER JOIN wordbank ON wordbank.id = wordbank_translation_label.wordbank_id INNER JOIN wordbank_translation ON wordbank.id = wordbank_translation.wordbank_id WHERE label_id = " . $params['label_id'] . " GROUP BY wordbank.id ORDER BY wordbank_translation_label.id";
    }

    protected function parent_label_wordbank($params)
    {
        !isset($params['label_id']) ? die('没有 父标签ID') : null;
        return "SELECT label.`name`, vocabulary FROM label INNER JOIN wordbank_translation_label ON wordbank_translation_label.label_id = label.id INNER JOIN wordbank ON wordbank.id = wordbank_translation_label.wordbank_id WHERE parent_id = " . $params['label_id'] . " AND wordbank.deleted_at IS NULL GROUP BY label.id, vocabulary ORDER BY label.`name`";
    }

    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $i => $row) {
            if ($i == 0) $record[] = $this->getTitle($row);
            $data = [];
            foreach ($row as $key => $item) {
                is_numeric($key) ? $data[] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }

    protected function getTitle($row)
    {
        $data = [];
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) {
                $data[] = isset($this->titles[$key]) ? $this->titles[$key] : $key;
            }
        }
        return $data;
    }

}
