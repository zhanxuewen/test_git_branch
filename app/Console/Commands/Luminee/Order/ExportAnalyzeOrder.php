<?php

namespace App\Console\Commands\Luminee\Order;

use DB;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class ExportAnalyzeOrder extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:analyze:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        DB::setPdo($this->getPdo('online'));

        $this->analyzeStudent();
    }

    protected function analyzeMoney()
    {
        $students = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')
            ->whereNull('deleted_at')->where('commodity_id', '=', 4)->where('pay_fee', '>', 30)
            ->whereBetween('finished_at', ['2018-05-21', '2018-06-04'])->pluck('student_id');
        $students = $students->toArray();
        $this->info(count($students));
        $chunk = array_chunk($students, 500);
        $ids = [];
        foreach ($chunk as $items) {
            $status = DB::table('payment_student_status')->distinct()->whereIn('student_id', $items)
                ->where('paid_type', 'improve_card')->where('expired_at', '>=', '2019-06-04')->pluck('student_id');
            $ids = array_merge($ids, $status->toArray());
        }
        $ids = array_unique($ids);
        $this->line(count($ids));
        $s_1 = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $s_2 = DB::table('order_offline')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $two_ids = array_merge($s_1->toArray(), $s_2->toArray());
        $two_ids = array_unique($two_ids);
        $this->line(count($s_1) . ' ' . count($s_2) . ' ' . count($two_ids));
    }

    protected function analyzeMoney2()
    {
        $students = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
            ->whereBetween('finished_at', ['2018-05-21', '2018-06-04'])->pluck('student_id');
        $students = $students->toArray();
        $pay_1 = DB::table('order')->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
            ->whereBetween('finished_at', ['2018-05-21', '2018-06-04'])->sum('pay_fee');
        $pay_2 = DB::table('order')->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
//            ->whereBetween('finished_at', ['2019-05-21', '2019-06-04'])
            ->sum('pay_fee');
        dd($pay_1, $pay_2);
        $this->info(count($students));
        $chunk = array_chunk($students, 500);
        $ids = [];
        foreach ($chunk as $items) {
            $status = DB::table('payment_student_status')->distinct()->whereIn('student_id', $items)
                ->where('paid_type', 'improve_card')->where('expired_at', '>=', '2019-06-04')->pluck('student_id');
            $ids = array_merge($ids, $status->toArray());
        }
        $ids = array_unique($ids);
        $this->line(count($ids));
        $s_1 = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $s_2 = DB::table('order_offline')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $students)
            ->whereNull('deleted_at')->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $two_ids = array_merge($s_1->toArray(), $s_2->toArray());
        $two_ids = array_unique($two_ids);
        $this->line(count($s_1) . ' ' . count($s_2) . ' ' . count($two_ids));
    }


    protected function analyzeStudent()
    {
        $students = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')
            ->whereNull('deleted_at')->where('commodity_id', '=', 4)->where('pay_fee', '>', 30)
            ->whereBetween('finished_at', ['2018-05-21', '2018-06-04'])->pluck('student_id');
        $students = $students->toArray();
        $this->info('all student ' . count($students));
        $chunk = array_chunk($students, 500);

        // In Days
        $in_days = [];
        foreach ($chunk as $items) {
            $status = DB::table('payment_student_status')->distinct()->whereIn('student_id', $items)
                ->where('paid_type', 'improve_card')->where('expired_at', '>=', '2019-06-04')->pluck('student_id');
            $in_days = array_merge($in_days, $status->toArray());
        }
        $in_days = array_unique($in_days);
        $this->line('in days ' . count($in_days));

        // Out Days
        $out_days = [];
        foreach ($chunk as $items) {
            $status = DB::table('payment_student_status')->distinct()->whereIn('student_id', $items)
                ->where('paid_type', 'improve_card')->where('expired_at', '<', '2019-06-04')->pluck('student_id');
            $out_days = array_merge($out_days, $status->toArray());
        }
        $out_days = array_unique($out_days);
        $this->line('out days ' . count($out_days));

        // In school
        $in_school = DB::table('school_member')->whereIn('account_id', $out_days)->where('is_active', 1)
            ->where('account_type_id', 5)->distinct()->pluck('account_id');
        $in_school = array_unique($in_school->toArray());
        $this->line('in school ' . count($in_school));

//        // Export In School Info
//        $info = DB::table('user_account')->join('user', 'user.id', '=', 'user_account.user_id')
//            ->join('payment_student_status', 'payment_student_status.student_id', '=', 'user_account.id')
//            ->join('vanclass_student', 'vanclass_student.student_id', '=', 'user_account.id', 'left')
//            ->join('vanclass', 'vanclass.id', '=', 'vanclass_student.vanclass_id', 'left')
//            ->join('school_member', 'school_member.account_id', '=', 'user_account.id', 'left')
//            ->join('school', 'school.id', '=', 'school_member.school_id', 'left')->whereIn('user_account.id', $out_days)
//            ->where('paid_type', 'improve_card')
//            ->selectRaw('nickname, phone, user_account.id, group_concat(vanclass.name) as van_name, group_concat(DISTINCT school.id) as sch_id, group_concat(DISTINCT school.name) as sch_name, expired_at')->groupBy('user_account.id')->get();
//        $data = [['昵称', '手机', 'ID', '班级', '学校ID', '学校', '过期时间']];
//        foreach ($info as $stu) {
//            $data[] = [$stu->nickname, $stu->phone, $stu->id, $stu->van_name,$stu->sch_id, $stu->sch_name, $stu->expired_at];
//        }
//
//        $this->store('analyze_order', $data);
//        dd(1);

//        // Export Expired
//        $expired = DB::table('payment_student_status')->whereIn('student_id', $in_school)
//            ->where('paid_type', 'improve_card')->selectRaw('count(student_id) as coo, expired_at')->groupBy('expired_at')->orderBy('expired_at')->get();
//        $table = [];
//        foreach ($expired as $item) {
//            $table[] = [$item->coo, $item->expired_at];
//        }
//        $this->table(['数量', '日期'], $table);
//        dd(1);


        $s_1 = DB::table('order')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $in_days)
            ->whereNull('deleted_at')->where('commodity_id', '<', 5)->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $s_2 = DB::table('order_offline')->distinct()->where('pay_status', 'like', '%success%')->whereIn('student_id', $in_days)
            ->whereNull('deleted_at')->where('pay_fee', '>', 30)
            ->where('finished_at', '>', '2019-05-01')->pluck('student_id');
        $two_ids = array_merge($s_1->toArray(), $s_2->toArray());
        $two_ids = array_unique($two_ids);
        $this->line(count($s_1) . ' ' . count($s_2) . ' ' . count($two_ids));
    }

}
