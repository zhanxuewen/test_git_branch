<?php

namespace App\Console\Commands\XLDN;

use App\Console\Schedules\Learning\DisableCards;
use App\Console\Schedules\Learning\ExportBXGMonthReport;
use App\Foundation\Excel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Console\Schedules\BaseSchedule;
use Illuminate\Database\Query\JoinClause;

class SyncSchoolInfo extends Command
{
    use Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:school:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步学校信息';

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
    public function handle_()
    {

        ini_set('memory_limit', '2048M');
        config(['database.default' => 'zxzj_online_search']);
        $date_type = Carbon::yesterday()->toDateString();

        // 获得 所有的学校
        $sql = <<<EOF
SELECT
	school.id school_id,school.`name`, marketer_user.nickname marketer_name , after_user.nickname after_name
FROM
	`school`
	left join user_account marketer_user on marketer_user.id = school.marketer_id
	left join school_attribute  on school_attribute.school_id = school.id and school_attribute.`key`='after_sales'
	left join user_account after_user on after_user.id = school_attribute.`value`
EOF;

        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);

        $school_info = collect($school_info)->keyBy('school_id')->toArray();

        // 初步获得延期学生
//        $sql = <<<EOF
//SELECT
//DISTINCT user_account.user_id
//FROM
//user_account
//WHERE
//user_id IN (
//SELECT DISTINCT
//user_id
//FROM
//school_popularize_data AS pd
//INNER JOIN school_member ON school_member.school_id = pd.school_id
//INNER JOIN user_account ON user_account.id = school_member.account_id
//WHERE
//pd.`key` = 'is_partner_school'
//AND pd.`value` = 1
//AND school_member.account_type_id IN ( 4, 6, 7 )
//)
//AND user_type_id = 5
//ORDER BY
//user_account.user_id
//EOF;

        $sql = <<<EOF
SELECT DISTINCT
	user_account.user_id 
FROM
	user_account 
WHERE
	user_id IN (

SELECT
	DISTINCT
		user_id 
FROM
	statistic_school_record 
		INNER JOIN school_member ON school_member.school_id = statistic_school_record.school_id and school_member.account_type_id IN ( 4, 6, 7 ) 
		INNER JOIN user_account ON user_account.id = school_member.account_id 
WHERE
	date_type = '$date_type' 
	AND contract_class <> 'N'
	
	) 
	AND user_type_id = 5 
ORDER BY
	user_account.user_id
EOF;

        $user_info = \DB::select(\DB::raw($sql));

        $user_info = json_decode(json_encode($user_info),true);

        $user_ids = array_column($user_info, 'user_id');

        $start_date = Carbon::today()->subMonths(3)->toDateString();

        // 获得学校的 最后延期时间
        $student_last_act_arr = [];
        $teacher_last_act_arr = [];

        foreach (array_chunk($user_ids, 100) as $chunk_user_ids){
            $user_ids_str = implode(',', $chunk_user_ids);

            // 查找 学生的最后登入时间
            $sql = <<<EOF
SELECT
	user_account.id student_id, max(created_date) last_date
FROM
	user_account 
	left join statistic_student_activity on statistic_student_activity.student_id = user_account.id and statistic_student_activity.created_date >= '$start_date'
WHERE
	user_id IN ( $user_ids_str ) 
	AND user_type_id = 5
	GROUP BY user_account.id
EOF;

            $student_info = \DB::select(\DB::raw($sql));

            $student_info = json_decode(json_encode($student_info),true);

            $student_last_act_arr = $student_last_act_arr +
                array_combine(
                    array_column($student_info, 'student_id'),
                    array_column($student_info,'last_date'));


            // 查找老师的最后登入时间
            $sql = <<<EOF
SELECT
	user_account.id teacher_id, max(created_date) last_date
FROM
	user_account 
	left join statistic_teacher_activity on statistic_teacher_activity.teacher_id = user_account.id and statistic_teacher_activity.created_date >= '$start_date'
WHERE
	user_id IN ( $user_ids_str ) 
	AND user_type_id = 4
	GROUP BY user_account.id
EOF;

            $teacher_info = \DB::select(\DB::raw($sql));

            $teacher_info = json_decode(json_encode($teacher_info),true);

            $teacher_last_act_arr = $teacher_last_act_arr +
                array_combine(
                    array_column($teacher_info, 'teacher_id'),
                    array_column($teacher_info,'last_date'));
        }


        $should_student_ids = [];
        // 需要返回的数据
        $return = [];
        $return[] = [
            'phone' => '手机',
            'user_id' => '用户id',
            'student_id' => '学生id',
            'student_last_act' => '学生最后一次登入',

            'teacher_id' => '老师id',
            'teacher_last_act' => '老师最后一次登入',
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'school_marketer' => '市场专员',
            'school_after' => '运营专员',

            'principal_id' => '校长id',
            'principal_last_act' => '校长最后一次登入',

            'manager_id' => '校管id',
            'manager_last_act' => '校管最后一次登入',
        ];

        $return_2 = [];
        $return_2[] = [
            'phone' => '手机',
            'user_id' => '用户id',
            'student_id' => '学生id',
            'student_last_act' => '学生最后一次登入',

            'teacher_id' => '老师id',
            'teacher_last_act' => '老师最后一次登入',
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'school_marketer' => '市场专员',
            'school_after' => '运营专员',

            'principal_id' => '校长id',
            'principal_last_act' => '校长最后一次登入',

            'manager_id' => '校管id',
            'manager_last_act' => '校管最后一次登入',
        ];


        foreach (array_chunk($user_ids, 100) as $user_ids_chunk_2){

            $user_ids_str = implode(',', $user_ids_chunk_2);
            $sql = <<<EOF
select user.phone, user_account.id,user_account.nickname,user_type_id, user_account.school_id, user_account.last_login_time, user_account.user_id
FROM
user_account
left join user on user.id = user_account.user_id
WHERE
user_id IN ( $user_ids_str ) 
EOF;

            $user_accounts_info = \DB::select(\DB::raw($sql));

            $user_accounts_info = json_decode(json_encode($user_accounts_info),true);

            $user_accounts_arr = collect($user_accounts_info)->groupBy('user_id')->toArray();


            foreach ($user_ids_chunk_2 as $user_id){
                $teacher_flag = false;
                $student_flag = false;

                $user_student_ids = [];

                $student_id = '0';
                $student_last_act = ''; //学生最后一次登入

                $teacher_id = '0';
                $teacher_last_act = '';     //老师最后一次登入
                $school_id = '0';            // 老师所在学校id
                $school_name = '';           //老师所在学校
                $school_marketer = '';
                $school_after = '';


                $principal_id ='0';
                $principal_last_act = '';   //校长最后一次登入

                $manager_id = '0';
                $manager_last_act = '';     // 校管最后一次登入


                $user_account_info = isset($user_accounts_arr[$user_id]) ? $user_accounts_arr[$user_id] : [];



                foreach ($user_account_info as $item){
                    $phone = $item['phone'];
                    $user_type_id = $item['user_type_id'];
                    $account_id = $item['id'];

                    switch ($user_type_id) {
                        case 4: //老师
                            $teacher_id = $account_id;
                            $teacher_last_act = isset($teacher_last_act_arr[$teacher_id]) ? $teacher_last_act_arr[$teacher_id] : '';

                            $teacher_school_id = $item['school_id'];
                            $school_id = $teacher_school_id;
                            $school_name = isset($school_info[$school_id]) && isset($school_info[$school_id]['name']) ? $school_info[$school_id]['name'] : '';
                            $school_marketer = isset($school_info[$school_id]) && isset($school_info[$school_id]['marketer_name']) ? $school_info[$school_id]['marketer_name'] : '';
                            $school_after = isset($school_info[$school_id]) && isset($school_info[$school_id]['after_name']) ? $school_info[$school_id]['after_name'] : '';

                            if (!empty($teacher_last_act) && !$teacher_flag){
                                $teacher_flag = Carbon::parse($teacher_last_act)->addMonths(3)->gte(Carbon::today());
                            }
                            break;
                        case 5: // 学生
                            $student_id = $account_id;
                            $user_student_ids[] = $student_id;
                            $student_last_act = isset($student_last_act_arr[$student_id]) ? $student_last_act_arr[$student_id] : '';
                            if (!empty($student_last_act)&& !$student_flag){
                                $student_flag = Carbon::parse($student_last_act)->addMonths(3)->gte(Carbon::today());
                            }
                            break;
                        case 6: // 校长
                            $principal_id = $account_id;
                            $principal_last_act = substr($item['last_login_time'],0,10);
                            if (!empty($principal_last_act) && !$teacher_flag ){
                                $teacher_flag = Carbon::parse($principal_last_act)->addMonths(3)->gte(Carbon::today());
                            }
                            break;
                        case 7: // 校管
                            $manager_id = $account_id;
                            $manager_last_act = substr($item['last_login_time'],0,10);
                            if (!empty($manager_last_act) && !$teacher_flag ){
                                $teacher_flag = Carbon::parse($manager_last_act)->addMonths(3)->gte(Carbon::today());
                            }
                            break;
                    }
                }

                // 学生满足要求 给予 推送
                if ($teacher_flag && $student_flag){
                    $should_student_ids = array_merge($should_student_ids, $user_student_ids);
                    foreach ($user_student_ids as $student_id){
                        $return_2[] = [
                            'phone' => $phone,
                            'user_id' => $user_id,
                            'student_id' => $student_id,
                            'student_last_act' => isset($student_last_act_arr[$student_id]) ? $student_last_act_arr[$student_id] : '',

                            'teacher_id' => $teacher_id,
                            'teacher_last_act' => isset($teacher_last_act_arr[$teacher_id]) ? $teacher_last_act_arr[$teacher_id] : '',
                            'school_id' => empty($school_id) ? '0' : $school_id ,
                            'school_name' => $school_name,
                            'school_marketer' => $school_marketer,
                            'school_after' => $school_after,

                            'principal_id' => $principal_id,
                            'principal_last_act' => $principal_last_act,

                            'manager_id' => $manager_id,
                            'manager_last_act' => $manager_last_act,
                        ];
                    }

                }else{
                    foreach ($user_student_ids as $student_id){
                        $return[] = [
                            'phone' => substr_replace($phone, '****', 3, 4),
                            'user_id' => $user_id,
                            'student_id' => $student_id,
                            'student_last_act' => isset($student_last_act_arr[$student_id]) ? $student_last_act_arr[$student_id] : '',

                            'teacher_id' => $teacher_id,
                            'teacher_last_act' => isset($teacher_last_act_arr[$teacher_id]) ? $teacher_last_act_arr[$teacher_id] : '',
                            'school_id' => empty($school_id) ? '0' : $school_id ,
                            'school_name' => $school_name,
                            'school_marketer' => $school_marketer,
                            'school_after' => $school_after,

                            'principal_id' => $principal_id,
                            'principal_last_act' => $principal_last_act,

                            'manager_id' => $manager_id,
                            'manager_last_act' => $manager_last_act,
                        ];
                    }
                }


                echo '+';
            }


        }


        \Log::info(json_encode($should_student_ids));
        $this->store('合作校学生_1_'.rand(0,100), $return, '.xlsx');
        $this->store('合作校延期学生_1_'.rand(0,100), $return_2, '.xlsx');


        dd('done....');


        ########################################







        ini_set('memory_limit', '2048M');

        config(['database.default' => 'zxzj_online_search']);

        // 获得 所有的学校
        $sql = <<<EOF
SELECT
	school.id school_id,school.`name`, marketer_user.nickname marketer_name , after_user.nickname after_name
FROM
	`b_vanthink_online`.`school`
	left join user_account marketer_user on marketer_user.id = school.marketer_id
	left join school_attribute  on school_attribute.school_id = school.id and school_attribute.`key`='after_sales'
	left join user_account after_user on after_user.id = school_attribute.`value`
EOF;

        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);

        $school_info = collect($school_info)->keyBy('school_id')->toArray();


        // 初步获得延期学生
        $sql = <<<EOF
SELECT
DISTINCT user_account.user_id 
FROM
user_account
WHERE
user_id IN (
SELECT DISTINCT
user_id
FROM
school_popularize_data AS pd
INNER JOIN school_member ON school_member.school_id = pd.school_id
INNER JOIN user_account ON user_account.id = school_member.account_id
WHERE
pd.`key` = 'is_partner_school'
AND pd.`value` = 1
AND school_member.account_type_id IN ( 4, 6, 7 )
)
AND user_type_id = 5
ORDER BY
user_account.user_id 
EOF;

        $user_info = \DB::select(\DB::raw($sql));

        $user_info = json_decode(json_encode($user_info),true);

        $user_ids = array_column($user_info, 'user_id');

        $start_year = Carbon::today()->startOfYear()->toDateString();

        // 获得学校的 最后延期时间
        $student_last_act_arr = [];
        $teacher_last_act_arr = [];

        foreach (array_chunk($user_ids, 100) as $chunk_user_ids){
            $user_ids_str = implode(',', $chunk_user_ids);

            // 查找 学生的最后登入时间
            $sql = <<<EOF
SELECT
	user_account.id student_id, max(created_date) last_date
FROM
	user_account 
	left join statistic_student_activity on statistic_student_activity.student_id = user_account.id and statistic_student_activity.created_date >= '$start_year'
WHERE
	user_id IN ( $user_ids_str ) 
	AND user_type_id = 5
	GROUP BY user_account.id
EOF;

            $student_info = \DB::select(\DB::raw($sql));

            $student_info = json_decode(json_encode($student_info),true);

            $student_last_act_arr = $student_last_act_arr +
                array_combine(
                    array_column($student_info, 'student_id'),
                    array_column($student_info,'last_date'));


            // 查找老师的最后登入时间
            $sql = <<<EOF
SELECT
	user_account.id teacher_id, max(created_date) last_date
FROM
	user_account 
	left join statistic_teacher_activity on statistic_teacher_activity.teacher_id = user_account.id and statistic_teacher_activity.created_date >= '$start_year'
WHERE
	user_id IN ( $user_ids_str ) 
	AND user_type_id = 4
	GROUP BY user_account.id
EOF;

            $teacher_info = \DB::select(\DB::raw($sql));

            $teacher_info = json_decode(json_encode($teacher_info),true);

            $teacher_last_act_arr = $teacher_last_act_arr +
                array_combine(
                    array_column($teacher_info, 'teacher_id'),
                    array_column($teacher_info,'last_date'));
        }


        // 需要返回的数据
        $return = [];
        $return[] = [
            'phone' => '手机',
            'user_id' => '用户id',
            'student_id' => '学生id',
            'student_last_act' => '学生最后一次登入',

            'teacher_id' => '老师id',
            'teacher_last_act' => '老师最后一次登入',
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'school_marketer' => '市场专员',
            'school_after' => '运营专员',

            'principal_id' => '校长id',
            'principal_last_act' => '校长最后一次登入',

            'manager_id' => '校管id',
            'manager_last_act' => '校管最后一次登入',
        ];


        foreach ($user_ids as $user_id){
            $teacher_flag = false;
            $student_flag = false;

            $user_student_ids = [];

            $student_id = '0';
            $student_last_act = ''; //学生最后一次登入

            $teacher_id = '0';
            $teacher_last_act = '';     //老师最后一次登入
            $school_id = '0';            // 老师所在学校id
            $school_name = '';           //老师所在学校
            $school_marketer = '';
            $school_after = '';


            $principal_id ='0';
            $principal_last_act = '';   //校长最后一次登入

            $manager_id = '0';
            $manager_last_act = '';     // 校管最后一次登入



            $sql = <<<EOF
select user.phone, user_account.id,user_account.nickname,user_type_id
FROM
user_account
left join user on user.id = user_account.user_id
WHERE
user_id = $user_id
EOF;

            $user_account_info = \DB::select(\DB::raw($sql));

            $user_account_info = json_decode(json_encode($user_account_info),true);


            foreach ($user_account_info as $item){
                $phone = $item['phone'];
                $user_type_id = $item['user_type_id'];
                $account_id = $item['id'];

                switch ($user_type_id) {
                    case 4: //老师
                        $teacher_id = $account_id;
                        $teacher_last_act = isset($teacher_last_act_arr[$teacher_id]) ? $teacher_last_act_arr[$teacher_id] : '';

                        $teacher_school_id = $item['school_id'];
                        $school_id = $teacher_school_id;
                        $school_name = isset($school_info[$school_id]) && isset($school_info[$school_id]['name']) ? $school_info[$school_id]['name'] : '';
                        $school_marketer = isset($school_info[$school_id]) && isset($school_info[$school_id]['marketer_name']) ? $school_info[$school_id]['marketer_name'] : '';
                        $school_after = isset($school_info[$school_id]) && isset($school_info[$school_id]['after_name']) ? $school_info[$school_id]['after_name'] : '';

                        if (!empty($teacher_last_act)){
                            $teacher_flag = Carbon::parse($teacher_last_act)->addMonths(3)->gte(Carbon::today());
                        }
                        break;
                    case 5: // 学生
                        $student_id = $account_id;
                        $user_student_ids[] = $student_id;
                        $student_last_act = isset($student_last_act_arr[$student_id]) ? $student_last_act_arr[$student_id] : '';
                        if (!empty($teacher_last_act)&& !$student_flag){
                            $student_flag = Carbon::parse($student_last_act)->addMonths(3)->gte(Carbon::today());
                        }
                        break;
                    case 6: // 校长
                        $principal_id = $account_id;
                        $principal_last_act = $item['last_login_time'];
                        break;
                    case 7: // 校管
                        $manager_id = $account_id;
                        $manager_last_act = $item['last_login_time'];
                        break;
                }
            }

            if ($teacher_flag && $student_flag){

            }else{
                foreach ($user_student_ids as $student_id){
                    $return[] = [
                        'phone' => $phone,
                        'user_id' => $user_id,
                        'student_id' => $student_id,
                        'student_last_act' => isset($student_last_act_arr[$student_id]) ? $student_last_act_arr[$student_id] : '',

                        'teacher_id' => $teacher_id,
                        'teacher_last_act' => isset($teacher_last_act_arr[$teacher_id]) ? $teacher_last_act_arr[$teacher_id] : '',
                        'school_id' => empty($school_id) ? '0' : $school_id ,
                        'school_name' => $school_name,
                        'school_marketer' => $school_marketer,
                        'school_after' => $school_after,

                        'principal_id' => $principal_id,
                        'principal_last_act' => $principal_last_act,

                        'manager_id' => $manager_id,
                        'manager_last_act' => $manager_last_act,
                    ];
                }
            }


            echo '+';
        }


        $this->store('合作校学生_1_'.rand(0,100), $return, '.xlsx');


        dd('done....');


        dd(99);




//        $tmp = new ExportBXGMonthReport();
//
//        $tmp->handle();
//
//        dd('done');


        config(['database.default' => 'kids_online']);
        $sql = <<<EOF
SELECT
	school.id school_id,
	school.`name` school_name,
	school.`code` flavor,
	school_attribute.`value` app_name,
	logo.`value` app_logo,
	popularize.`value` app_down_url 
FROM
	`kids`.`school`
	LEFT JOIN school_attribute ON school_attribute.school_id = school.id 
	AND school_attribute.`key` = 'app_name'
	LEFT JOIN school_attribute logo ON logo.school_id = school.id 
	AND logo.`key` = 'app_android_icon'
	LEFT JOIN school_popularize_data popularize ON popularize.school_id = school.id 
	AND popularize.`key` = 'android_app_download_url' 
WHERE
	school_attribute.`value` IS NOT NULL
EOF;

        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);

        config(['database.default' => 'zxzj_dev']);
        $res = \DB::table('statistic_schedule_tmp')->where('key','XLDN')->update([
            'value'=>json_encode($school_info)
        ]);
        dd($res);
    }



    public function handle()
    {
        $except = [1555, 1558, 3100, 3121, 3418, 5545, 5674, 6354, 6601, 6606, 6646,
            6683, 7069, 7431, 8488, 9186, 5311, 5477, 5478, 6716, 7098];
//        DB::setPdo($this->getConnPdo('core', 'online4'));

        config(['database.default' => 'zxzj_online_search']);

        $rows = DB::table('course_school_map')->selectRaw('school_id, course_id, is_active, created_at')
            ->where('school_id', '>', 0)->whereNotIn('school_id', $except)->orderBy('school_id')->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->school_id][$row->course_id] = $row;
        }
        $ids = $rows->pluck('school_id')->unique()->toArray();

        $course = $this->getCourse($ids);

        $students8 = $this->get8Students();
        $students11 = $this->get11Students();

        $c8count = $this->get8Schools($ids, $students8);
        $c11count = $this->get8Schools($ids, $students11);

        $no_f8 = $this->getNoSchool8($students8);
        $no_f11 = $this->getNoSchool8($students11);

        $schools = DB::table('school')->join('user_account as mkt', 'mkt.id', '=', 'school.marketer_id')
            ->join('school_attribute as region', function (JoinClause $join) {
                $join->on('region.school_id', '=', 'school.id')
                    ->where('region.key', 'region');
            })->join('school_member', function (JoinClause $join) {
                $join->on('school_member.school_id', '=', 'school.id')
                    ->where('school_member.account_type_id', 6);
            }, null, null, 'left')
            ->join('user_account as pri', 'pri.id', '=', 'school_member.account_id', 'left')
            ->selectRaw('school.id, name, pri.nickname as p_name, mkt.nickname as m_name, region.value')
            ->whereIn('school.id', $ids)->get()->keyBy('id');
        $sta1 = $this->getStatus($ids, 1);
        $sta2 = $this->getStatus($ids, 2);
        $sta3 = $this->getStatus($ids, 3);

        $ord1 = $this->getOrder(1);
        $ord2 = $this->getOrder(2);
        $ord3 = $this->getOrder(3);

        $fis1 = $this->getFreeOrderInSchool(1);
        $fis2 = $this->getFreeOrderInSchool(2);
        $fis3 = $this->getFreeOrderInSchool(3);
        $no_f1 = $this->getNoSchoolFin();

        $no1 = $this->getNoSchool(1);
        $no2 = $this->getNoSchool(2);
        $no3 = $this->getNoSchool(3);

        $report = [['学校ID', '学校名称', '校长', '销售', '省', '市', '区',
            '神奇拼读.轻课状态', '绑定日期', '时间',
            '四上.轻课状态', '绑定日期', '时间',
            '五上.轻课状态', '绑定日期', '时间',

            '神.学习人数', '付费人数', '第一课完成人数', '第二课',
            '四上.学习人数', '付费人数', '全部课完成人数',
            '五上.学习人数', '付费人数', '全部课完成人数'

            ]];
        $report[] = ['0', '自由学生', '', '', '', '', '', '', '', '', '', '', '',
            // 五上 绑卡
            '', '', '',
            $no1->vco, (isset($ord1[0]) ? $ord1[0]->coo : '0') . '(入校' . $fis1->coo . ')',
            $no_f1[6610], $no_f1[6664],
            $no2->vco, (isset($ord2[0]) ? $ord2[0]->coo : '0') . '(入校' . $fis2->coo . ')', isset($no_f8->coo) ? $no_f8->coo : '0' ,
            // 五上 学习
            $no3->vco, (isset($ord3[0]) ? $ord3[0]->coo : '0') . '(入校' . $fis3->coo . ')', isset($no_f11->coo) ? $no_f11->coo : '0' ,

            ];
        foreach ($schools as $row) {
            $id = $row->id;
            $region = explode('/', $row->value);
            $dt1 = isset($map[$id][1]) ? explode(' ', $map[$id][1]->created_at) : ['/', '/'];
            $dt2 = isset($map[$id][2]) ? explode(' ', $map[$id][2]->created_at) : ['/', '/'];
            $dt3 = isset($map[$id][3]) ? explode(' ', $map[$id][3]->created_at) : ['/', '/'];
            $report[] = [
                'id' => $id,
                'name' => $row->name,
                'pri' => $row->p_name,
                'mark' => $row->m_name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'sta1' => isset($map[$id][1]) ? $map[$id][1]->is_active : '/',
                'date1' => isset($dt1[0]) ? $dt1[0] : null,
                'time1' => isset($dt1[1]) ? $dt1[1] : null,
                'sta2' => isset($map[$id][2]) ? $map[$id][2]->is_active : '/',
                'date2' => isset($dt2[0]) ? $dt2[0] : null,
                'time2' => isset($dt2[1]) ? $dt2[1] : null,

                'sta3' => isset($map[$id][3]) ? $map[$id][3]->is_active : '/',
                'date3' => isset($dt3[0]) ? $dt3[0] : null,
                'time3' => isset($dt3[1]) ? $dt3[1] : null,

                'vco1' => $sta1[$id]->vco,
                'oco1' => isset($ord1[$id]) ? $ord1[$id]->coo : '0',
                'f' => isset($course[$id][6610]) ? $course[$id][6610] : '0',
                's' => isset($course[$id][6664]) ? $course[$id][6664] : '0',
                'vco2' => $sta2[$id]->vco,
                'oco2' => isset($ord2[$id]) ? $ord2[$id]->coo : '0',
                'a' => isset($c8count[$id]) ? $c8count[$id]->coo : '0',

                'vco3' => $sta3[$id]->vco,
                'oco3' => isset($ord3[$id]) ? $ord3[$id]->coo : '0',
                'a3' => isset($c11count[$id]) ? $c11count[$id]->coo : '0'
            ];
        }

        $now = Carbon::now();
        $filename = 'qing_ke_statistic_' . $now->format('Ymd').rand(1,100);
        $path = 'school/' . $now->year . '/' . $now->month;
        $file = $this->store($path . '/' . $filename, $report);
//        if ($send) {
//            $this->email(['xiemin68@163.com', '1608711502@qq.com', 'coral@vanthink.org'], 'emails.export', ['object' => '轻课学校统计'], $now->toDateString() . ' Qing Ke Export', realpath($file));
//        }

    }

    protected function getStatus($ids, $course_id)
    {
        $vco = ', count(DISTINCT cov.student_id) as vco';
        $oco = ', count(DISTINCT cod.student_id) as oco';
        return DB::table('school')
            ->join('school_member', function (JoinClause $join) {
                $join->on('school_member.school_id', '=', 'school.id')
                    ->where('school_member.account_type_id', 5);
            }, null, null, 'left')
            ->join('course_student_overview as cov', function (JoinClause $join) use ($course_id) {
                $join->on('cov.student_id', '=', 'school_member.account_id')
                    ->where('cov.course_id', $course_id);
            }, null, null, 'left')
            ->join('course_order as cod', function (JoinClause $join) use ($course_id) {
                $join->on('cod.student_id', '=', 'school_member.account_id')
                    ->where('cod.pay_status', 'success')->where('cod.pay_fee', '>', 1)
                    ->where('cod.course_id', $course_id);
            }, null, null, 'left')
            ->whereIn('school.id', $ids)
            ->groupBy(['school.id'])
            ->selectRaw('school.id' . $vco . $oco)
            ->get()->keyBy('id');
    }

    protected function getOrder($course_id)
    {
        return DB::table('course_order')->where('pay_status', 'success')
            ->where('pay_fee', '>', 1)->where('course_id', $course_id)
            ->groupBy(['school_id'])
            ->selectRaw('school_id, count(DISTINCT student_id) as coo')
            ->get()->keyBy('school_id');
    }

    protected function getFreeOrderInSchool($course_id)
    {
        return DB::table('course_order')->selectRaw('count(DISTINCT student_id) as coo')
            ->join('school_member', 'school_member.account_id', '=', 'course_order.student_id')
            ->where('pay_status', 'success')
            ->where('pay_fee', '>', 1)->where('course_id', $course_id)
            ->where('course_order.school_id', 0)->first();
    }

    protected function getCourse($school_ids)
    {
        $objects = [6610, 6664];
        $rows = DB::table('school_member')->selectRaw('school_id, object_id, sum(is_finished) as sf')
            ->join('course_student_overview as cso', 'school_member.account_id', '=', 'cso.student_id')
            ->whereIn('school_member.school_id', $school_ids)->whereIn('object_id', $objects)
            ->groupBy(['school_id', 'object_id'])->get();
        $course = [];
        foreach ($rows as $row) {
            $course[$row->school_id][$row->object_id] = $row->sf;
        }
        return $course;
    }

    protected function get8Students()
    {
        $rows = DB::table('course_student_overview')->selectRaw('student_id, count(DISTINCT object_id) as coo')
            ->whereIn('object_id', [7265, 7266, 7267, 7268, 7269, 7270, 7271, 7272])
            ->where('is_finished', 1)->groupBy(['student_id'])->having('coo', 8)->get();
        return $rows->pluck('student_id')->toArray();

    }

    //
    protected function get11Students()
    {
        $rows = DB::table('course_student_overview')->selectRaw('student_id, count(DISTINCT object_id) as coo')
            ->whereIn('object_id', [8014,8016,8017,8018,8019,8020,8021,8022,8023,8024,8025])
            ->where('is_finished', 1)->groupBy(['student_id'])->having('coo', 11)->get();
        return $rows->pluck('student_id')->toArray();

    }

    protected function get8Schools($school_ids, $student_ids)
    {
        return DB::table('school_member')->selectRaw('school_id, count(*) as coo')
            ->whereIn('school_member.school_id', $school_ids)->whereIn('account_id', $student_ids)
            ->groupBy(['school_id'])->get()->keyBy('school_id');
    }

    protected function getNoSchool($course_id)
    {
        $raw = 'count( DISTINCT cov.student_id ) AS vco, count( DISTINCT cod.student_id ) AS oco';
        return DB::table('user_account')->selectRaw($raw)
            ->join('school_member', 'school_member.account_id', '=', 'user_account.id', 'left')
            ->join('course_student_overview as cov', function (JoinClause $join) use ($course_id) {
                $join->on('cov.student_id', '=', 'user_account.id')->where('cov.course_id', $course_id);
            }, null, null, 'left')
            ->join('course_order as cod', function (JoinClause $join) use ($course_id) {
                $join->on('cod.student_id', '=', 'user_account.id')->where('cod.pay_status', 'success')
                    ->where('cod.pay_fee', '>', 1)->where('cod.course_id', $course_id);
            }, null, null, 'left')
            ->where('user_account.user_type_id', 5)->whereNull('school_member.id')->first();
    }

    protected function getNoSchoolFin()
    {
        $rows = DB::table('user_account')->selectRaw('object_id, sum(is_finished) as sf')
            ->join('school_member', 'school_member.account_id', '=', 'user_account.id', 'left')
            ->join('course_student_overview as cso', 'user_account.id', '=', 'cso.student_id')
            ->where('user_account.user_type_id', 5)->whereNull('school_member.id')
            ->whereIn('object_id', [6610, 6664])
            ->groupBy(['object_id'])->get();
        $course = [];
        foreach ($rows as $row) {
            $course[$row->object_id] = $row->sf;
        }
        return $course;
    }

    protected function getNoSchool8($student_ids)
    {
        return DB::table('user_account')->selectRaw('count(*) as coo')
            ->join('school_member', 'school_member.account_id', '=', 'user_account.id', 'left')
            ->whereIn('user_account.id', $student_ids)->whereNull('school_member.id')
            ->first();
    }
}
