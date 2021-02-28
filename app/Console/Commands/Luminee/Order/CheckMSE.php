<?php

namespace App\Console\Commands\Luminee\Order;

use DB;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use App\Library\Curl;
use Illuminate\Console\Command;

class CheckMSE extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:mse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $teachers = [];
    protected $students = [];
    protected $need = [];
    protected $report = [['学生ID', '开始日期', '结束日期', '差异天数', '可操作天数']];
    protected $except = [187310, 186635, 187994, 528305, 188116, 710434, 195933, 147811, 148998, 186590, 655667, 188056, 149789, 710461, 187313, 188106, 504624, 188010, 710555, 710620, 185249, 710742, 710907, 189326];
    protected $special = [
        184354 => '2018-09-29 23:59:59',
        697289 => '2020-01-21 23:59:59',
        710066 => '2020-02-15 23:59:59',
        187447 => '2018-10-05 23:59:59',
        187480 => '2018-10-05 23:59:59',
        188047 => '2018-10-06 23:59:59',
        188749 => '2018-10-07 23:59:59',
        188431 => '2018-10-06 23:59:59',
        193948 => '2018-10-13 23:59:59',
        189326 => '2018-10-08 23:59:59',
    ];

    protected $now = '2020-04-03 14:31:00';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->getTeachers();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        DB::setPdo($this->getConnPdo('core', 'online4'));
//        $this->getStudents();
        $teacher_id = [148986, 178631, 183718, 183719, 183736, 183747, 183750, 183757, 183797, 183810, 183853, 183858, 183859, 183864, 184047, 184068, 184074, 184077, 184080, 184098, 184141, 224134, 224136, 520667, 617340, 706347];
        $students = DB::select("SELECT vanclass_student.student_id, vanclass_teacher.teacher_id, vanclass_student.joined_time FROM vanclass_teacher INNER JOIN vanclass_student ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id WHERE vanclass_teacher.teacher_id IN (" . implode(',', $teacher_id) . ") AND vanclass_student.is_active = 1");
        $this->output->progressStart(count($students));
        foreach ($students as $student) {
            $this->handleStudent($student);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $filename = '莱德尔MSE';
        $path = 'summary';
//        $file = $this->store($path . '/' . $filename, $this->report);
        DB::setPdo($this->getConnPdo('core', 'online'));
        foreach ($this->need as $student_id => $days) {
            $this->update($student_id, $days);
        }
        dd(count($this->need));
    }

    protected function handleStudent($student)
    {
        $student_id = $student->student_id;
        if (in_array($student_id, $this->except)) return;
        $in = $student->joined_time;
        $teacher = $this->teachers[$student->teacher_id];
        if ($in > $teacher[1]) return;
        if ($in > $teacher[0]) $teacher[0] = $in;
        $payment = DB::table('payment_student_status_record')->where('student_id', $student_id)->where('paid_type', 'improve_card')
            ->selectRaw('obtain_way, value')->get()->keyBy('obtain_way');
        $self = $payment['individual']->value . ' 23:59:59';
        if (isset($this->special[$student_id])) $self = $this->special[$student_id];
        if ($self >= $teacher[1]) return;
        if ($self > $teacher[0]) $teacher[0] = $self;
        $start = Carbon::parse($teacher[0]);
        if ($start->hour == 23) $start = $start->addHour();
        $end = Carbon::parse($teacher[1])->endOfDay();
        $diff = $end->diffInDays($start) + 1;
        if ($student_id == 184354) $diff -= 3;
        $this->students[$student_id] = $diff;
        $start_at = DB::table('user_account_attribute')->where('account_id', $student_id)
            ->where('key', 'improve_card_start_at')->first(['value']);
        $days = $this->getDays($diff, $payment, $start_at);
        $this->report[] = [$student_id, $start, $end, $diff, $days];
        if ($days != '未购买' && $days > 0)
            $this->need[$student_id] = $days;
    }

    protected function update($student_id, $days)
    {
        $used = DB::table('payment_student_status_record')->where('student_id', $student_id)
            ->where('paid_type', 'improve_card')->where('obtain_way', 'used')->first();
        if (empty($used)) {
            $insert = ['student_id' => $student_id, 'paid_type' => 'improve_card', 'obtain_way' => 'used', 'value' => $days, 'created_at' => $this->now, 'updated_at' => $this->now];
            DB::table('payment_student_status_record')->insert($insert);
        } else {
            $update = $used->value + $days;
            DB::table('payment_student_status_record')->where('id', $used->id)->update(['value' => $update, 'updated_at' => $this->now]);
        }
        $status = DB::table('payment_student_status')->where('student_id', $student_id)->where('paid_type', 'improve_card')->first();
        $date = Carbon::parse($status->expired_at)->addDays(-$days)->toDateString();
        DB::table('payment_student_status')->where('id', $status->id)->update(['expired_at' => $date, 'updated_at' => $this->now]);
    }

    protected function getDays($diff, $payment, $start)
    {
        if (!isset($payment['buy']))
            return '未购买';
        $used = isset($payment['used']) ? $payment['used']->value : 0;
        $till = Carbon::parse($start->value)->addDays($payment['buy']->value - $used);
        $now = Carbon::now();
        if (!$till->greaterThan($now)) return 0;
        $left = $till->diffInDays($now);
        return $left >= $diff ? $diff : $left;
    }

    protected function getTeachers()
    {
        $now = '2020-02-29 23:59:59';
        $this->teachers = [
            706347 => ["2020-01-11 12:45:46", $now],
            184074 => ["2020-01-11 12:43:25", $now],
            184047 => ["2020-01-11 12:43:53", $now],
            183747 => ["2020-01-11 12:44:20", $now],
            184141 => ["2020-01-11 12:49:16", $now],
            183810 => ["2020-01-11 12:50:04", $now],
            184080 => ["2020-01-11 13:11:00", $now],
            617340 => ["2020-01-11 13:11:17", $now],
            183757 => ["2020-01-11 13:11:58", $now],
            224134 => ["2020-01-11 13:12:14", $now],
            224136 => ["2020-01-11 13:12:46", $now],
            184098 => ["2020-01-11 13:19:40", $now],
            183853 => ["2020-01-11 13:32:05", $now],
            520667 => ["2020-01-11 13:46:05", $now],
            178631 => ["2020-01-11 13:46:30", '2020-01-16 09:56:44'],
            183858 => ["2020-01-11 16:51:13", $now],
            183797 => ["2020-01-11 16:53:10", $now],
            183719 => ["2020-01-12 13:51:19", $now],
            183859 => ["2020-01-12 13:52:15", $now],
            183864 => ["2020-01-15 12:46:08", $now],
            184077 => ["2020-01-17 18:32:55", $now],
            183718 => ["2020-01-18 11:24:54", $now],
            183736 => ["2020-01-18 11:26:34", '2020-02-10 13:41:20'],
            184068 => ["2020-01-19 17:29:13", $now],
            183750 => ["2020-01-20 13:40:00", $now],
            148986 => ["2020-01-21 09:12:13", $now],
        ];
    }

    protected function getStudents()
    {
        $i = '2020-01-11 00:00:00';
        $teacher_id = [148986, 178631, 183718, 183719, 183736, 183747, 183750, 183757, 183797, 183810, 183853, 183858, 183859, 183864, 184047, 184068, 184074, 184077, 184080, 184098, 184141, 224134, 224136, 520667, 617340, 706347];
        $logs = DB::select("SELECT logs.content, logs.created_at FROM vanclass_teacher INNER JOIN vanclass_student ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id AND vanclass_student.is_active = 1 INNER JOIN `logs` ON vanclass_student.student_id = `logs`.object_id AND `logs`.object_type = 'student' WHERE vanclass_teacher.teacher_id IN (" . implode(',', $teacher_id) . ") AND `logs`.content LIKE '%试用期%from%'");
        foreach ($logs as $k => $log) {
//            if ($k< 0) continue;
            $a = unserialize($log->content);
            $student = explode(' : ', $a['object'])[0];
            echo($student . ' => ["' . $a['from'] . ' 23:59:59", "' . $i . '", "' . $log->created_at . '", "' . $a['to'] . ' 23:59:59"],');
            echo "\r\n";
        }
        dd(1);


        $b = [
            184354 => ["2018-09-29 23:59:59"],
            697289 => ["2020-01-21 23:59:59"],
            710066 => ["2020-02-15 23:59:59"],
            187447 => ["2018-10-05 23:59:59"],
            187480 => ["2018-10-05 23:59:59"],
            188047 => ["2018-10-06 23:59:59"],
            188749 => ["2018-10-07 23:59:59"],
            188431 => ["2018-10-06 23:59:59"],
            193948 => ["2018-10-13 23:59:59"],
            189326 => ["2018-10-08 23:59:59"],

        ];


    }

}
