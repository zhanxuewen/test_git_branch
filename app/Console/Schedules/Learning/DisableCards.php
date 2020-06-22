<?php


namespace App\Console\Schedules\Learning;


use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;



class DisableCards extends BaseSchedule
{
    public function handle()
    {
        if (Carbon::today()->format('d') == '8'){
            \DB::setPdo($this->getConnPdo('learning', 'online'));

            $school_ids = [2095, 2126, 2225];

            // 查看学校余额
            $balance = \DB::table('finance_school_balance')->whereIn('school_id',$school_ids)->get();

            $balance = json_decode(json_encode($balance, true));

            $school_balance = array_combine(
                array_column($balance, 'school_id'),
                array_column($balance, 'balance')
            );



            $start_time = Carbon::today()->subMonth()->startOfMonth()->startOfDay()->toDateTimeString();
            $end_time = Carbon::today()->subMonth()->endOfMonth()->endOfDay()->toDateTimeString();


            // 查找学习卡 信息
            $sql = <<<EOF
SELECT
	card.id, card.card_number,card_prototype.`name`, card.student_id, user.phone, user.`name` user_name , card.school_id, school.name school_name,
	card.expired_at,  LEFT( card.created_at,10) card_date ,card.created_at
FROM
	`learning`.`card` 
	left join card_prototype on card_prototype.id = card.prototype_id
	LEFT JOIN user on user.id = card.student_id 
	left join school on school.id = card.school_id
WHERE
	card.`school_id` IN ( 2095, 2126, 2225 ) 
	AND card.`deleted_at` IS NULL 
	AND card.`created_at` >= '$start_time' 
	AND card.`created_at` <= '$end_time' 
EOF;

            $card_info = \DB::select(\DB::raw($sql));


            // 初始状态
            $report = [];
            $report[] = ['卡id','卡名称','学生id','学生手机','学生名称','学校id','学校名称','有效期','开卡时间'];

            $card_ids = [];
            $insert_statements = [];

            foreach ($card_info as $item){
                if (Carbon::parse($item->expired_at)->gt(Carbon::today())){
                    $card_ids[] = $item->id;

                    $insert_statements[] = [
                        'school_id' => $item->school_id,
                        'account_id'=> $item->student_id,
                        'type'      => 'student_card_extension',
                        'label_id'  => 9,
                        'approval_code' => '定时任务',
                        'content'   => '学生已购买激活卡延期，卡片ID：【'.$item->id.'】学生ID：【'.$item->student_id.'】原截止日期：【'.$item->expired_at.'】现截止日期：【'.
                            Carbon::yesterday()->toDateString().'】',
                        'fee'      => 0,
                        'before'   => $school_balance[$item->school_id],
                        'after'    => $school_balance[$item->school_id],
                        'date'     => Carbon::today()->toDateString(),
                        'status'   => 2,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString(),
                    ];
                }
                $report[] = [
                    $item->id,
//                $item->card_number,
                    $item->name,
                    $item->student_id,
                    $item->phone,
                    $item->user_name,
                    $item->school_id,
                    $item->school_name,
                    $item->expired_at,
                    $item->card_date,
                ];
            }


            //  操作卡 过期

            if (count($card_ids)){
                \DB::table('card')->whereIn('id', $card_ids)->update([
                    'expired_at' => Carbon::yesterday()->toDateTime()
                ]);
            }

            if (count( $insert_statements )){
                \DB::table('card')->insert($insert_statements);
            }


            // 操作后
            // 查找学习卡 信息
            $sql = <<<EOF
SELECT
	card.id, card.card_number,card_prototype.`name`, card.student_id, user.phone, user.`name` user_name , card.school_id, school.name school_name,
	card.expired_at,  LEFT( card.created_at,10) card_date ,card.created_at
FROM
	`learning`.`card` 
	left join card_prototype on card_prototype.id = card.prototype_id
	LEFT JOIN user on user.id = card.student_id 
	left join school on school.id = card.school_id
WHERE
	card.`school_id` IN ( 2095, 2126, 2225 ) 
	AND card.`deleted_at` IS NULL 
	AND card.`created_at` >= '$start_time' 
	AND card.`created_at` <= '$end_time' 
EOF;

            $card_info = \DB::select(\DB::raw($sql));


            // 初始状态
            $report2 = [];
            $report2[] = ['卡id','卡名称','学生id','学生手机','学生名称','学校id','学校名称','有效期','开卡时间'];



            foreach ($card_info as $item){

                $report2[] = [
                    $item->id,
//                $item->card_number,
                    $item->name,
                    $item->student_id,
                    $item->phone,
                    $item->user_name,
                    $item->school_id,
                    $item->school_name,
                    $item->expired_at,
                    $item->card_date,
                ];
            }



            $filename = 'BXG_disable_cards_' . date('md');
            $path = 'learning';
            $file = $this->sheetsStore($path . '/' . $filename, ['学习卡列表'=>$report, '操作记录'=>$report2]);

//        $this->email(['xiemin68@163.com','shirui2811@126.com'],
//            'emails.export2', ['object' => '百项过体验校学习卡过期操作'],
//            '百项过体验校学习卡过期操作_'.date('md'), realpath($file));

        }

    }
}