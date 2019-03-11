<?php

namespace App\Http\Controllers\Export;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SingleController extends Controller
{
    public function single()
    {
        return view('export.single');
    }

    public function ajaxExport(Request $request)
    {
        $query = $request->get('query');
        $request->filled('school_ids') ? $params['school_ids'] = $request->get('school_ids', null) : null;
        isset($params) or die('没有参数');
        $pdo = $this->getPdo('online');
        $rows = $pdo->query($this->$query($params))->fetchColumn();
        return $rows;
    }

    protected function school_year_card_student_count($params)
    {
        !isset($params['school_ids']) ? die('没有 学校IDs') : null;
        return $this->school_card_student_count_sql($params['school_ids'], 4, 365);
    }

    protected function school_half_card_student_count($params)
    {
        !isset($params['school_ids']) ? die('没有 学校IDs') : null;
        return $this->school_card_student_count_sql($params['school_ids'], 3, 183);
    }

    private function school_card_student_count_sql($ids, $id, $days)
    {
        return
            "SELECT count(DISTINCT student_id) AS coo FROM (
                (SELECT DISTINCT student_id FROM `order` WHERE school_id IN ($ids) AND commodity_id = $id AND pay_status LIKE '%success')
            UNION
	            (SELECT DISTINCT student_id FROM order_offline WHERE school_id IN ($ids) AND days = $days AND pay_status = 'success') 
	        ) AS tmp";
    }

}
