<?php

namespace App\Http\Controllers\Select;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    protected $pdo;

    /**
     * List And Search Quit Student
     * @param Request $request
     * @return mixed
     */
    public function quitStudent(Request $request)
    {
        $student_id = $request->get('student_id', null);
        if (is_null($student_id)) return view('select.student', compact('student_id'));
        $pdo = $this->getConnPdo('core', 'online');
        $quit_ids = $this->getArray($pdo->query($this->quit_vanclass($student_id)));
        $exist_ids = $this->getArray($pdo->query($this->exist_vanclass($student_id)));
        foreach ($quit_ids as $key => $id) {
            if (in_array($id, $exist_ids)) unset($quit_ids[$key]);
        }
        $student = $this->getArray($pdo->query($this->find_student($student_id)));
        $vanclass = $this->getRecord($pdo->query($this->list_vanclass($quit_ids)));
        return view('select.student', compact('vanclass', 'student', 'student_id'));
    }

    public function learningCards(Request $request)
    {
        $phones = $request->get('phones', null);
        if (is_null($phones)) return view('select.learning.cards', compact('phones'));
        $phone_s = explode(',', str_replace('，', ',', $phones));
        $rows = DB::setPdo($this->getConnPdo('learning', 'online'))->table('user')
            ->join('card', 'card.student_id', '=', 'user.id')
            ->join('card_prototype', 'card_prototype.id', '=', 'card.prototype_id')
            ->join('course_user_book_record', 'course_user_book_record.card_id', '=', 'card.id')
            ->join('course_book', 'course_book.id', '=', 'course_user_book_record.book_id')
            ->selectRaw('phone,	user.name AS nickname, card_number, card.id, card.activated_at, card_prototype.name, course_book.name AS book')
            ->whereIn('user.phone', $phone_s)->whereNull('card.deleted_at')->get();
        return view('select.learning.cards', compact('phones', 'rows'));
    }

    public function inventory(Request $request)
    {
        DB::setPdo($this->getConnPdo('inventory', 'online'));
        $mapping = ['code' => '商品编号', 'children' => '包含明细', 'alias' => '配货编码', 'unit' => '单位',
            'series' => '系列', 'p_name' => '商品名称', 'weight' => '重量', 'qty' => '库存'];
        $raw = [
            'product.`code`, GROUP_CONCAT(child.`code`) AS children',
            'product.alias, unit, product_series.`name` AS series',
            'product.`name` AS p_name, product_inventory.weight, product_inventory.qty'
        ];
        $rows = DB::table('product')->join('product_series', 'product.series_id', '=', 'product_series.id')
            ->join('product_inventory', 'product_inventory.product_id', '=', 'product.id')
            ->join('product AS child', 'product.id', '=', 'child.parent_id', 'left')
            ->selectRaw(implode(',', $raw))->groupBy(['product.id'])->get();
        if ($request->get('action') == 'export') {
            $record = [$mapping];
            foreach ($rows as $row) {
                $data = [];
                foreach ($mapping as $k => $map) {
                    $data[$k] = $row->$k;
                }
                $record[] = $data;
            }
            $name = date('Y_m_d') . '_库存信息';
            return $this->exportExcel($name, $record, 'export_inventory');
        }
        return view('select.inventory', compact('rows', 'mapping'));

    }

    protected function find_student($student_id)
    {
        return "SELECT nickname, phone FROM user_account INNER JOIN user ON user.id = user_account.user_id WHERE user_account.id =" . $student_id;
    }

    protected function quit_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = " . $student_id . " AND deleted_at IS NOT NULL";
    }

    protected function exist_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = " . $student_id . " AND deleted_at IS NULL";
    }

    protected function list_vanclass($ids)
    {
        return "SELECT vanclass.id, vanclass.`name`, vanclass.student_count, teacher_id, nickname, user_account.school_id FROM vanclass INNER JOIN vanclass_teacher ON vanclass_teacher.vanclass_id = vanclass.id INNER JOIN user_account ON user_account.id = vanclass_teacher.teacher_id WHERE vanclass.id IN (" . implode(',', $ids) . ")";
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

    protected function getArray($rows)
    {
        $record = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $item) {
                !is_numeric($key) ? $record[] = $item : null;
            }
        }
        return $record;
    }

}
