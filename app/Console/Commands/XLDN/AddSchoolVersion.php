<?php

namespace App\Console\Commands\XLDN;

use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Learning\ExportBXGMonthFee;
use App\Console\Schedules\Learning\ExportBXGMonthReport;
use App\Console\Schedules\Learning\ExportBXGWeekReport;
use App\Console\Schedules\Learning\ExportSchoolLearningStudent;
use App\Console\Schedules\ZXZJ\ExportZXZJWeekOperator;
use App\Console\Schedules\Monitor\ScheduleHeartbeatV2;
use App\Foundation\Excel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AddSchoolVersion extends Command
{
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:school:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '添加学校版本';

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


//        $tmp = new ExportBXGMonthReport();
//        $tmp = new ExportZXZJWeekOperator();
//        $tmp = new ExportBXGWeekReport();
//        $tmp = new ExportZXZJWeekOperator();
        $tmp = new ExportBXGMonthReport();
        $res = $tmp->handle();

        dd($res, 'done');




        config(['database.default' => 'zxzj_online_search']);


        $sql = <<<EOF
select 

*

from 


(
select  

 student_user.phone, 
 vanclass_student.student_id , 
 vanclass_student.vanclass_id,
 vanclass_student.mark_name, vanclass.`name` vanclass_name, vanclass.school_id vanclass_school_id,
 user_account.nickname teacher_name, user_account.school_id teacher_school, school.`name` school_name, student_account.nickname student_name
 

from 

vanclass_student 
left join vanclass on vanclass.id = vanclass_student.vanclass_id
left join vanclass_teacher on vanclass_teacher.vanclass_id = vanclass_student.vanclass_id
left join user_account on user_account.id = vanclass_teacher.teacher_id
left join school on school.id = user_account.school_id
left join user_account as student_account on student_account.id = vanclass_student.student_id
left join user as student_user on student_user.id = student_account.user_id


where vanclass_student.student_id in (


select student_id from (

select 

student_id, count(*) ccc

from vanclass_student where student_id in (


SELECT
--   GROUP_CONCAT(DISTINCT account_id), count(account_id), count(distinct account_id)
 distinct account_id
FROM
	`b_vanthink_online`.`school_member` 
WHERE
	`school_id` = '932' 
	AND `account_type_id` = '5'
	
	) 
	and   vanclass_student.is_active = 1
	GROUP BY student_id
	HAVING ccc > 1
	
	
	) as tmp_vanclass_student
	
	)
	
	and  vanclass_student.is_active = 1
	
	ORDER BY student_user.phone
	
	) tmp1
	

EOF;

        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);

        foreach ($school_info as $item){


            if ($item['vanclass_school_id'] != $item['teacher_school']){

                dd($item);
            }
        }

        $resoult_data = [];
        collect($school_info)->groupBy('phone')->map(
            function ($student_info) use(&$resoult_data) {


                $school_count = $student_info->pluck('teacher_school')->unique()->count();
                if ($school_count > 1){

                    $student_first = $student_info->first();

                    $tmp = [
                        'phone' => $student_first['phone'],
                        'student_id' => $student_first['student_id'],
                        'student_name' => $student_first['student_name'],
                    ];

                    $student_info = $student_info->sortBy('teacher_school')->toArray();

                    foreach ($student_info as $ii){

                        $tmp[] = $ii['vanclass_id'];
                        $tmp[] = $ii['vanclass_name'];
                        if (empty($ii['vanclass_school_id'])){
                            $tmp[] = 0;
                            $tmp[] = $ii['teacher_name'];
                        }else{
                            $tmp[] = $ii['vanclass_school_id'];
                            $tmp[] = $ii['school_name'];
                        }
                    }


                    $resoult_data[] = $tmp;



                }
            }

        );



        $this->store('启智学生_'.rand(0,100), $resoult_data, '.xlsx');

        dd('done');






        $ids_arr = [
            650205,652158,656471,645788,645789,645790,645791
        ];

        $logs = \DB::table('logs')
            ->selectRaw('id, content,created_at')
            ->where('log_type_id', 34)->get();

        $school_ids = [];
        $res = [];
        foreach ($logs as $log){
            $id = $log->id;
            $content = unserialize($log->content);
            $content_arr = explode('; ', $content['object']);

            $school_id = str_replace('学校id: ', '', $content_arr[0]);
            $resource_str = str_replace('资源id: ', '', $content_arr[1]);
            $resource_ids = explode(',', $resource_str);
            $type = str_replace('资源类型： ', '', $content_arr[2]);
            $created_at = substr($log->created_at,0,10);


            if ($type == 'bill'){
                if (count(array_intersect($ids_arr, $resource_ids)) >= 4){

                  $res[] = [
                      $id,
                      $school_id,
                      $created_at,
                      $log->content,
                  ];

                    $tmp_school_ids = explode(',', $school_id);

                    $school_ids = array_merge($tmp_school_ids, $school_ids);
                }
            }
        }



        $this->store('操作记录_'.rand(0,100), $res, '.xlsx');
        dd(json_encode(array_values(array_unique($school_ids))));





//        $tmp = new ExportBookLearningProcess();
//        $tmp->handle();
//
//        dd('done');

//        $tmp = new ExportBXGMonthReport();
//        $tmp->handle();
//
//        dd('done');


        config(['database.default' => 'local']);
        $sql = <<<EOF
SELECT * FROM `logs` WHERE `log_type_id` = '4'
 AND `project` = 'manage' 
 AND `section` = 'marketer' 
 AND `id` >= 1046441 
 AND `id` <= 1106617 
 AND `content` LIKE '%售后%'
 order by 'id'
EOF;


        $trans = [
            '运营--Miya'=>542608,
            '运营--May'=>542609,
            '运营--Maple'=>542610,
            '运营备用'=>550097,
            'Coral-运营'=>658080,
            '运营--Sherly'=>671196,
            'Chris运营'=>849620,
        ];


        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);


        $school_record = [];

        foreach ($school_info as $item ){


            $content = unserialize($item['content']);

            $object_arr = explode(';', $content['object']);

            $school_name = explode(':' ,$object_arr[1])[1];
            $school_id = $item['object_id'];

            // "更换售后专员:运营--Sherly--->运营备用"

            $record_arr = explode(':', $content['record']);


            $old_man = '';
            $new_man = '';
            if (strpos($content['record'], '添加售后专员') !== false){

                $new_man = trim($record_arr[1]);
            }

            if (strpos($content['record'], '更换售后专员') !== false){

                $tmp = explode('--->', $record_arr[1]);

                $old_man = $tmp[0];
                $new_man = $tmp[1];
            }

            $school_record[] = [
                'id'        => $item['id'],
                'school_id' => $school_id,
                'school_name' => $school_name,
                'old_man' => $old_man,
                'new_man' => $new_man,
                'old_man_name' => isset($trans[$old_man]) ? $trans[$old_man] : 0,
                'new_man_name' => isset($trans[$new_man]) ? $trans[$new_man] : 0,
                'create_date' => substr($item['created_at'], 0,10)
             ];
        }

        $return_data = [];
        collect($school_record)->groupBy('school_id')->map(function ($school) use(&$return_data){
            $tmp = $school->sortBy('id')->toarray();
            foreach ($tmp as $item){
                $return_data[] = $item;
            }
        });

        $this->store('售后记录_'.rand(0,100), $return_data, '.xlsx');
        dd('done');


        $school_record = collect($school_record)->groupBy('school_id')->map(function ($school){

            $real_man = '';

            foreach ($school as $item){
                if ($item['create_date'] < '2020-05-01'){

                    $real_man = $item['new_man'].' ; ';

                }

                if ($item['create_date'] >= '2020-05-01' && $item['create_date'] < '2020-06-01'){

                    $real_man .=  empty($item['old_man']) ? '未设置' : $item['old_man'].'--->'.$item['new_man'];

                }


                if ($item['create_date'] >= '2020-06-01'){

                    $real_man .=   $item['old_man'];

                }





            }

            return [
                'school_id' => $school->first()['school_id'],
                'real_man' => $real_man
            ];

//            dd($school);
//
//            // 5836,5614,5135,1318,3007,1752,6139,6142,6081,5962,6163,6130,6146,6137,5400,5267,5268,5024,3179,3373,3311,5963,5974,5991,3492,6131,5795,5878,5242,5539,5307,892,5700,3238,5477,5460,5478,850,6141,6195,5742,5983,485,929,621,642,682,925,985,1843,3471,6207,1531,6215,6233,6132,5701,6242,6259,6219,6319,71,6316,6275,6299,6300,6301,6274,6298,6271,853,6231,6256,547,6335,6342,6334,6343,6346,6349,6350,6333,6353,6352,6362,6363,6364,6360,6365,1793,6216,5793,6290,6302,6258,6373,6382,6385,6332,5606,6358,5283,6344,6110,6320,6119,6223,6331,5342,6423,1532,6443,6442,6210,6444,6202,3092,1586,1685,1822,6230,6336,6398,5493,6249,6477,6480,6174,6220,1668,5937,887,6263,6510,6441,6397,6509,3414,6291,6471,6504,6243,6458,6436,6470,6468,6450,777,6122,1233,6523,5311,5851,5979,5733,1618,6313,6466,6540,2784,5438,6548,6511,6262,6552,6006,6087,6431,604,2007,5189,6562,6051,5439,6193,6566,627,5363,6526,6003,6235,6236,6486,449,1714,6248,6049,6113,6211,6573,6578,6579,6393,5711,6585,6528,6574,6587,6592,6571,6542,6583,6584,6586,5624,6594,6601,6595,6596,1463,3280,6609,6624,6627,6625,6534,6205,6618,5285,6620,3502,6610,6546,6391,6576,5586
//            if ($school->count() == 1){
//                if ($school->first()['create_date'] <= '2020-05-01'){
//                    return [];
//                }
//            }
//
//
//            // 2780,1673,6679,6653,6719,6698,1616,6617,6654,6735,6650,6317,6752,6717,6765,6771,6772,6773,6768,6635,6767,6749,6780,6655,6577,814,6777
//            if ($school->count() == 1){
//                if ($school->first()['create_date'] >= '2020-06-01' && empty($school->first()['old_man'])){
//                    return $school;
//                }
//            }
//
//            return [];




        })->toArray();



        $this->store('日志记录_'.rand(0,100), $school_record, '.xlsx');

        dd('日志记录');
































        $tmp = new ExportBookLearningProcess();
        $tmp->handle();

        dd('done');



        $str = '[{"code":"yyyuga","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200509174222_student_env_onlineYyyugaRelease_v1.0.1.apk","version_name":"1.0.1","md5":"07f9edb361a2effbbc0ed365a9606cd8","size":"35814129","platform":"android"},{"code":"dmyykw","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200509174222_student_env_onlineDmyykwRelease_v1.0.1.apk","version_name":"1.0.1","md5":"6f5bcbba2e684a70f88f9edba39640d9","size":"35796022","platform":"android"},{"code":"yyqhdz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineYyqhdzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"30f118354c2012a06079fe4613c72f00","size":"35802057","platform":"android"},{"code":"qypxhc","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineQypxhcRelease_v1.0.1.apk","version_name":"1.0.1","md5":"eeba128df07d4f521616a3749d43cbd5","size":"35799047","platform":"android"},{"code":"yyjyyz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineYyjyyzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ef23b8fd358aea08eb5582334e88b0b8","size":"35819944","platform":"android"},{"code":"jcjygn","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineJcjygnRelease_v1.0.1.apk","version_name":"1.0.1","md5":"dc6249b93b1fcddd931da7dee7749dc1","size":"35804092","platform":"android"},{"code":"hbychp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineHbychpRelease_v1.0.1.apk","version_name":"1.0.1","md5":"4240686c62a6eeec9d4d93a32fea0d3c","size":"35803560","platform":"android"},{"code":"kljyod","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineKljyodRelease_v1.0.1.apk","version_name":"1.0.1","md5":"42381c65dbb36f0f656d5871865cf104","size":"35799692","platform":"android"},{"code":"tyhyni","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineTyhyniRelease_v1.0.1.apk","version_name":"1.0.1","md5":"b6183aecdcce485f6bffd39e5bd07735","size":"35799096","platform":"android"},{"code":"jcsqgh","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200507201128_student_env_onlineJcsqghRelease_v1.0.1.apk","version_name":"1.0.1","md5":"31a6b22f9786cd7b8820b2e54c8b09d8","size":"35806045","platform":"android"},{"code":"smlsdu","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200507201128_student_env_onlineSmlsduRelease_v1.0.1.apk","version_name":"1.0.1","md5":"6c5bb66d5043fbe577585f2f9cba594e","size":"35817180","platform":"android"},{"code":"syyyxr","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200507201128_student_env_onlineSyyyxrRelease_v1.0.1.apk","version_name":"1.0.1","md5":"4e4627c761f0572f6424ded090154e4a","size":"35799867","platform":"android"},{"code":"afeyxs","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200506201101_student_env_onlineAfeyxsRelease_v1.0.1.apk","version_name":"1.0.1","md5":"14d6a4dd2f2265c9e2842493140d8e58","size":"35805191","platform":"android"},{"code":"jyjybw","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200506201101_student_env_onlineJyjybwRelease_v1.0.1.apk","version_name":"1.0.1","md5":"48a3d52db832a6a7f9865966dc40bde1","size":"35793659","platform":"android"},{"code":"dnseke","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200506201101_student_env_onlineDnsekeRelease_v1.0.1.apk","version_name":"1.0.1","md5":"43d6c698773ef52888dba86abb3b5193","size":"35797784","platform":"android"},{"code":"megjip","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200506201101_student_env_onlineMegjipRelease_v1.0.1.apk","version_name":"1.0.1","md5":"c4a51beef6a4bb1694bcf16e0fa2ffbb","size":"35801595","platform":"android"},{"code":"wxyyrh","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineWxyyrhRelease_v1.0.1.apk","version_name":"1.0.1","md5":"35f8122c05487cd738b49044c2d17d3e","size":"35809594","platform":"android"},{"code":"jzcyhu","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineJzcyhuRelease_v1.0.1.apk","version_name":"1.0.1","md5":"faca190d52fdc70f1c9acffaf531a6de","size":"35834101","platform":"android"},{"code":"mljywc","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineMljywcRelease_v1.0.1.apk","version_name":"1.0.1","md5":"d87436d7472f20e3469fc0313f9f4f60","size":"35832923","platform":"android"},{"code":"yyxxvl","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineYyxxvlRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ff3a3d24186764ebf270523652a52f7f","size":"35811486","platform":"android"},{"code":"wtjyvi","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineWtjyviRelease_v1.0.1.apk","version_name":"1.0.1","md5":"fbfc657d28d31fefb523c6c26c318bb3","size":"35827342","platform":"android"},{"code":"llsyab","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineLlsyabRelease_v1.0.1.apk","version_name":"1.0.1","md5":"89f3c2c5b775a576a816cf5e70719af0","size":"35823671","platform":"android"},{"code":"snsshg","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineSnsshgRelease_v1.0.1.apk","version_name":"1.0.1","md5":"d406fb4a1898f5c271967327b90ffb66","size":"35814367","platform":"android"},{"code":"nxjybx","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineNxjybxRelease_v1.0.1.apk","version_name":"1.0.1","md5":"7a48738f8f9a3c00de48be011685eedd","size":"35827509","platform":"android"},{"code":"ssjybg","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineSsjybgRelease_v1.0.1.apk","version_name":"1.0.1","md5":"10a11ae02dee38b42af000554d4bf00e","size":"35819333","platform":"android"},{"code":"klwhht","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineKlwhhtRelease_v1.0.1.apk","version_name":"1.0.1","md5":"83c14cb2e8dea765b02fe92d354f508a","size":"35825813","platform":"android"},{"code":"ygjyot","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineYgjyotRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ab742f8b4cde2063435994e1a43a4208","size":"35819569","platform":"android"},{"code":"lcdcfx","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineLcdcfxRelease_v1.0.1.apk","version_name":"1.0.1","md5":"c5fd2c4ab28ebb9bfed6dd19794d24f6","size":"35892908","platform":"android"},{"code":"hzswgq","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineHzswgqRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ba9571e9452a2a618af0528de79d7954","size":"35830980","platform":"android"},{"code":"hxyyjy","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineHxyyjyRelease_v1.0.1.apk","version_name":"1.0.1","md5":"6c37a2da960ec4b83482aa63e45efdec","size":"35812875","platform":"android"},{"code":"dsxtrs","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineDsxtrsRelease_v1.0.1.apk","version_name":"1.0.1","md5":"c6dc73f3d4913fa06292d9b0ee22d776","size":"35821447","platform":"android"},{"code":"vanthink","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineVanthinkRelease_v1.0.1.apk","version_name":"1.0.1","md5":"b0c367bbafad7d367f1516af12bfbb8e","size":"35829385","platform":"android"},{"code":"qdyslg","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineQdyslgRelease_v1.0.1.apk","version_name":"1.0.1","md5":"0584f6425094f0969b9b01b82b3d50eb","size":"35805933","platform":"android"},{"code":"jyowna","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineJyownaRelease_v1.0.1.apk","version_name":"1.0.1","md5":"5369f1faefb8c03c86769f202af95086","size":"35815141","platform":"android"},{"code":"xyddtm","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineXyddtmRelease_v1.0.1.apk","version_name":"1.0.1","md5":"dca243d0fddb1476c8e731ddf3bb5918","size":"35818024","platform":"android"},{"code":"llsxuz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineLlsxuzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"c93547c631eb92e090e4804ab8a29ce2","size":"35822026","platform":"android"},{"code":"sryyuh","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineSryyuhRelease_v1.0.1.apk","version_name":"1.0.1","md5":"7da8db3d704a0d1553ae19ae6fb7db4c","size":"35829473","platform":"android"},{"code":"ajdlqp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineAjdlqpRelease_v1.0.1.apk","version_name":"1.0.1","md5":"c714837c16b0ba5db83c4e097d7461e3","size":"35815109","platform":"android"},{"code":"yycstw","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineYycstwRelease_v1.0.1.apk","version_name":"1.0.1","md5":"54429f2199d448c17ae8f921cf91a300","size":"35818038","platform":"android"},{"code":"ydetqy","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200424152306_student_env_onlineYdetqyRelease_v1.0.1.apk","version_name":"1.0.1","md5":"49f9d64d21d7335f4c4d2a7269715cd0","size":"35859456","platform":"android"},{"code":"gspxjp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineGspxjpRelease_v1.0.1.apk","version_name":"1.0.1","md5":"dd20db83e89dbdeb11df6a1860d52bba","size":"35827759","platform":"android"},{"code":"whshef","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineWhshefRelease_v1.0.1.apk","version_name":"1.0.1","md5":"4a32e7d1c2e3d2e1b71712440d0a1959","size":"35806607","platform":"android"},{"code":"yjyycw","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineYjyycwRelease_v1.0.1.apk","version_name":"1.0.1","md5":"f8204222647a897ddb5fab81e2395eef","size":"35816336","platform":"android"},{"code":"mxygnc","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineMxygncRelease_v1.0.1.apk","version_name":"1.0.1","md5":"3bfa70347d250c50e1eb0f693f20dda1","size":"35834382","platform":"android"},{"code":"lrjynz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineLrjynzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"2e98ca1ef7ec77d7afae9062f9d6390a","size":"35835603","platform":"android"},{"code":"vanthink","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineVanthinkRelease_v1.0.1.apk","version_name":"1.0.1","md5":"b0c367bbafad7d367f1516af12bfbb8e","size":"35829385","platform":"android"},{"code":"zhxggt","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineZhxggtRelease_v1.0.1.apk","version_name":"1.0.1","md5":"e070f87b1304480e713f3d9ee7644250","size":"35846059","platform":"android"},{"code":"amjyow","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineAmjyowRelease_v1.0.1.apk","version_name":"1.0.1","md5":"818541e33f6dc9dcb4aed6702718dfd0","size":"35825973","platform":"android"},{"code":"llpxno","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineLlpxnoRelease_v1.0.1.apk","version_name":"1.0.1","md5":"0d207108098fb4adead5a172b4266326","size":"35840227","platform":"android"},{"code":"yljyys","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426122936_student_env_onlineYljyysRelease_v1.0.1.apk","version_name":"1.0.1","md5":"6d7e22f6258ec18642e5036917d414af","size":"35831522","platform":"android"},{"code":"sjzlfl","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineSjzlflRelease_v1.0.1.apk","version_name":"1.0.1","md5":"04b596926a1c1f2d4f9b47c286276ce1","size":"35833062","platform":"android"},{"code":"jxjyuf","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineJxjyufRelease_v1.0.1.apk","version_name":"1.0.1","md5":"f340c9e55b018711a2de4106662135fa","size":"35812845","platform":"android"},{"code":"htjygk","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineHtjygkRelease_v1.0.1.apk","version_name":"1.0.1","md5":"54ade3f9c983c8040f1b8e81a6fb21e2","size":"35834847","platform":"android"},{"code":"jojwom","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineJojwomRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ce2b0fce08ea89c0c028fb68be3b139c","size":"35830603","platform":"android"},{"code":"ssyyrx","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineSsyyrxRelease_v1.0.1.apk","version_name":"1.0.1","md5":"378ebece6c65d8418eaf6cdc63728aba","size":"35819329","platform":"android"},{"code":"btswzp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineBtswzpRelease_v1.0.1.apk","version_name":"1.0.1","md5":"faf9b413ca3785ce9c3a6a7883a28812","size":"35857634","platform":"android"},{"code":"yylxqg","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200428164250_student_env_onlineYylxqgRelease_v1.0.1.apk","version_name":"1.0.1","md5":"d3b40619b0c6cf7516afc3723908625d","size":"35850520","platform":"android"},{"code":"yyjyrq","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200427165741_student_env_onlineYyjyrqRelease_v1.0.1.apk","version_name":"1.0.1","md5":"5280fb7b07af559f0c27cd65fe38bceb","size":"35825518","platform":"android"},{"code":"yjtjhf","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426173914_student_env_onlineYjtjhfRelease_v1.0.1.apk","version_name":"1.0.1","md5":"2422455159f7ee137edf7a82b67cec81","size":"35884936","platform":"android"},{"code":"vanthink","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426163407_student_env_onlineVanthinkRelease_v1.0.1.apk","version_name":"1.0.1","md5":"b0c367bbafad7d367f1516af12bfbb8e","size":"35829385","platform":"android"},{"code":"cfjyxd","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426163407_student_env_onlineCfjyxdRelease_v1.0.1.apk","version_name":"1.0.1","md5":"f7df57cc91b56b3d5cb9f2a43277d489","size":"35808420","platform":"android"},{"code":"cxyyyb","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200426163407_student_env_onlineCxyyybRelease_v1.0.1.apk","version_name":"1.0.1","md5":"3eed8ed5be41fb04687a8f7f107fc2fb","size":"35804659","platform":"android"},{"code":"bxzyiv","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200429170947_student_env_onlineBxzyivRelease_v1.0.1.apk","version_name":"1.0.1","md5":"2046caac5730060ed4f8a42e23be0b1f","size":"35805375","platform":"android"},{"code":"kkyyyf","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200429170947_student_env_onlineKkyyyfRelease_v1.0.1.apk","version_name":"1.0.1","md5":"041a1703ce3b3ff5ed9314ae78b7f5a6","size":"35823893","platform":"android"},{"code":"aljyvz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200429170947_student_env_onlineAljyvzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"7493f5d101bb9f98c680d46e17b0f39b","size":"35823505","platform":"android"},{"code":"bkjytr","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200430171438_student_env_onlineBkjytrRelease_v1.0.1.apk","version_name":"1.0.1","md5":"30c9a4ceee3e358f91cb69b8a0e85621","size":"35817534","platform":"android"},{"code":"csjyvh","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200430171438_student_env_onlineCsjyvhRelease_v1.0.1.apk","version_name":"1.0.1","md5":"8c8e7c9e8ff658727681709f7271581a","size":"35822812","platform":"android"}]';
        $str = '[{"code":"xfxgio","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200512200335_student_env_onlineXfxgioRelease_v1.0.1.apk","version_name":"1.0.1","md5":"5d1776fbbf64804658b875c76018c890","size":"35792636","platform":"android"}]';
        $str = '[{"code":"xddcud","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200514200843_student_env_onlineXddcudRelease_v1.0.1.apk","version_name":"1.0.1","md5":"67aa930357cac0f9f8dd06ab6b3e16da","size":"35819648","platform":"android"},{"code":"tlsqjh","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200514200843_student_env_onlineTlsqjhRelease_v1.0.1.apk","version_name":"1.0.1","md5":"396c34d3565a00c64fe5de443d14c46f","size":"35798004","platform":"android"},{"code":"yhpxeb","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200514200843_student_env_onlineYhpxebRelease_v1.0.1.apk","version_name":"1.0.1","md5":"4798ff7cfd0fecf6c5a2dc47aba702f9","size":"35802012","platform":"android"}]';
        $arr = json_decode($str, true);

//        dd(count($arr));
//        ;
//       $return = [];
//        foreach ($arr as $key=>$item){
//
//            $return[$item['code']][] = $key;
//        }
//
//        dd($return);

        config(['database.default' => 'kids_online']);
        $save_time = Carbon::now()->toDateTimeString();
        foreach ($arr as $value) {

            $school_code = $value['code'];
            $url = $value['url'];
            $school = \DB::table('school')->where('code', $school_code)->first();
            if (empty($school)) {
                \Log::info($school_code);
            } else {
                $school_id = $school->id;
                \DB::table('school_popularize_data')->insert([
                    'school_id' => $school_id,
                    'key' => 'android_app_download_url',
                    'value' => $url,
                    'created_at' => $save_time,
                    'updated_at' => $save_time,
                ]);
            }
            echo '+';
        }

    }
}
