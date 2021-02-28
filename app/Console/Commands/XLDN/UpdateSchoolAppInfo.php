<?php

namespace App\Console\Commands\XLDN;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UpdateSchoolAppInfo extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:school:app_info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '上传学校 APP';

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
        $str = '
        [{"code":"yyqhdz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineYyqhdzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"30f118354c2012a06079fe4613c72f00","size":"35802057","platform":"android"},{"code":"qypxhc","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineQypxhcRelease_v1.0.1.apk","version_name":"1.0.1","md5":"eeba128df07d4f521616a3749d43cbd5","size":"35799047","platform":"android"},{"code":"yyjyyz","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineYyjyyzRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ef23b8fd358aea08eb5582334e88b0b8","size":"35819944","platform":"android"},{"code":"jcjygn","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineJcjygnRelease_v1.0.1.apk","version_name":"1.0.1","md5":"dc6249b93b1fcddd931da7dee7749dc1","size":"35804092","platform":"android"},{"code":"hbychp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineHbychpRelease_v1.0.1.apk","version_name":"1.0.1","md5":"4240686c62a6eeec9d4d93a32fea0d3c","size":"35803560","platform":"android"},{"code":"kljyod","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineKljyodRelease_v1.0.1.apk","version_name":"1.0.1","md5":"42381c65dbb36f0f656d5871865cf104","size":"35799692","platform":"android"},{"code":"tyhyni","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200508202707_student_env_onlineTyhyniRelease_v1.0.1.apk","version_name":"1.0.1","md5":"b6183aecdcce485f6bffd39e5bd07735","size":"35799096","platform":"android"}]';

        $str = '[{"code":"yyyuga","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200509174222_student_env_onlineYyyugaRelease_v1.0.1.apk","version_name":"1.0.1","md5":"07f9edb361a2effbbc0ed365a9606cd8","size":"35814129","platform":"android"},{"code":"dmyykw","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200509174222_student_env_onlineDmyykwRelease_v1.0.1.apk","version_name":"1.0.1","md5":"6f5bcbba2e684a70f88f9edba39640d9","size":"35796022","platform":"android"}]';

        $str = '[{"code":"xfxgio","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200512200335_student_env_onlineXfxgioRelease_v1.0.1.apk","version_name":"1.0.1","md5":"5d1776fbbf64804658b875c76018c890","size":"35792636","platform":"android"}]';

        $str = '[{"code":"wzslyp","url":"https:\/\/mobile-apps.oss-cn-beijing.aliyuncs.com\/dino\/androidApk\/v1.0.1\/release\/20200513200428_student_env_onlineWzslypRelease_v1.0.1.apk","version_name":"1.0.1","md5":"ec355370988e696ac169aa0e201acca9","size":"35795970","platform":"android"}]';
        $arr = json_decode($str, true);
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
