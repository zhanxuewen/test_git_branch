<?php

namespace App\Console\Commands\Luminee\Order;

use DB;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use App\Library\Curl;
use Illuminate\Console\Command;

class ExportSchools extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:schools';

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
        DB::setPdo($this->getConnPdo('core','online'));
        $schools = DB::table('school')
            ->join('user_account AS mar', 'mar.id', '=', 'school.marketer_id', 'left')
            ->join('school_attribute AS reg', function ($join) {
                $join->on('reg.school_id', '=', 'school.id')->where('reg.key', '=', 'region');
            }, null, null, 'left')
            ->join('statistic_school_record AS ssr', function ($join) {
                $join->on('ssr.school_id', '=', 'school.id')->where('ssr.created_date', '=', '2020-01-21');
            }, null, null, 'left')
            ->selectRaw('school.id, school.`name`, mar.nickname, reg.`value`, school.created_at, ssr.contract_class, ssr.sign_contract_date')
            ->where('school.is_active', 1)->where('ssr.contract_class', '<>', 'N')->get();
        $report = [];
        $report[] = ['ID', '学校', '市场专员', '地区', '注册时间', '合作档', '签约日期', '注册学生', '提分版人数', '仅试用人', '注册老师', '已结算额', '账户余额', 'WAS上周', 'WAS上上周', 'WAS上3周', 'WAS上4周', 'WAS上5周', 'WAS上6周', 'WAS上7周', 'WAS上8周', 'WAS上9周'];
        $this->output->progressStart(count($schools));
        foreach($schools as $school){
            $id = $school->id;
            $data = $this->getOpInfo($id);
            $d2 = $this->getStuInfo($id)->act_student;
            $row = [
                'id' => $id,
                'name' => $school->name,
                'nick' => $school->nickname,
                'region' => $school->value,
                'crea' => $school->created_at,
                'clas' => $school->contract_class,
                'date' => $school->sign_contract_date,
                'stu' => $data->student_total,
                'vip' => $data->vip_student,
                'try' => $data->try_student,
                'tea' => $data->teacher_total,
                'mony' => $data->money_done,
                'total' => $data->balance_fee,
                's' => $d2->上周,
                'ss' => $d2->上上周,
                's3' => $d2->上3周,
                's4' => $d2->上4周,
                's5' => $d2->上5周,
                's6' => $d2->上6周,
                's7' => $d2->上7周,
                's8' => $d2->上8周,
                'x9' => $d2->上9周
            ];
            $report[] = $row;
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $filename = '学校列表';
        $path = 'summary';
        $file = $this->store($path . '/' . $filename, $report);
    }

    protected function getOpInfo($school_id)
    {
        $token    = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMzNDksImlzcyI6Imh0dHA6Ly9hcGkubWFuYWdlLnd4enh6ai5jb20vYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE1ODM1NzE5MzcsImV4cCI6MTU4NDc4MTUzNywibmJmIjoxNTgzNTcxOTM3LCJqdGkiOiIzQVg0cDZyVmNORUM5OVpuIn0.aMVN27ph1kmLNUIGbVnUPx4vp-i3X1rF4HK-hqv56WI';
        $url = 'https://manage.wxzxzj.com/api/school/get/schoolOperationInfo?token='.$token;
        // $data = Curl::curlPost($url, 'school_id=' . $school_id);
        $data = $this->request_post($url, 'school_id='.$school_id);
        return $data;
    }

    protected function getStuInfo($school_id)
    {
        $token    = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMzNDksImlzcyI6Imh0dHA6Ly9hcGkubWFuYWdlLnd4enh6ai5jb20vYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE1ODM1NzE5MzcsImV4cCI6MTU4NDc4MTUzNywibmJmIjoxNTgzNTcxOTM3LCJqdGkiOiIzQVg0cDZyVmNORUM5OVpuIn0.aMVN27ph1kmLNUIGbVnUPx4vp-i3X1rF4HK-hqv56WI';
        $url = 'https://manage.wxzxzj.com/api/school/get/studentStatistics?token='.$token;
        $data = $this->request_post($url, 'school_id='.$school_id);
        return $data;
    }

    protected function request_post($url, $curlPost)
    {
        $token    = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMzNDksImlzcyI6Imh0dHA6Ly9hcGkubWFuYWdlLnd4enh6ai5jb20vYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE1ODM1NzE5MzcsImV4cCI6MTU4NDc4MTUzNywibmJmIjoxNTgzNTcxOTM3LCJqdGkiOiIzQVg0cDZyVmNORUM5OVpuIn0.aMVN27ph1kmLNUIGbVnUPx4vp-i3X1rF4HK-hqv56WI';
        $curl     = curl_init();  //初始化
        curl_setopt($curl, CURLOPT_URL, $url);  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_HEADER, 0);  //设置头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);  //设置post的数据
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        $data = json_decode($data)->data;
        return $data;
    }

}
