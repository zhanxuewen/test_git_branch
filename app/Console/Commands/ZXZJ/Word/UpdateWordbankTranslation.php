<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateWordbankTranslation extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:wordbank:translation {pdo=local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将原有的 解释合并为一条解释';

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

        $save_time = Carbon::now()->toDateTimeString();
        $this->output->progressStart(10000/100);

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getPdo($pdo_type);

        $delete_ids = [];
        $update_date = [];


        for ($i=1; $i<10000; $i = $i+100){
            $word_ids = range($i, $i+99);
            // 查找单词解释
            $words_str = str_repeat("?,", count($word_ids)-1) . "?";;
            // 获得所有单词的例句解释
            $sql = 'select `id`, `wordbank_id`,`part_of_speech`,`translation` , `power` from `wordbank_translation` 
                where `wordbank_id` in ('.$words_str.') and `deleted_at` is null ';
            $res  = $pdo->prepare($sql);
            $res->execute($word_ids);
            $word_trans_list = $res->fetchAll(\PDO::FETCH_ASSOC  );

            collect($word_trans_list)->groupBy('wordbank_id')
                ->map(function ($word) use(&$update_date, &$delete_ids) {


                    $word = $word->sortByDesc('power')->values();
                    $save_trans_str = '';
                    foreach ($word  as $key=>$item){
                        $save_trans_str .= $item['part_of_speech'].' '.$item['translation'].';';
                    }


                    $word = $word->sortBy('power')->values();
                    $save_id = 0;
                    foreach ($word  as $key=>$item){
                        if (!$key){
                            $save_id = $item['id'];
                        }else{
                            $delete_ids[] = $item['id'];
                        }
                    }

                    $save_trans_str = trim($save_trans_str,';');
                    $update_date[] = [
                        'id' => $save_id,
                        'part_of_speech' => '',
                        'translation' => $save_trans_str,
//                        'power' => 1
                    ];

                });
            $this->output->progressAdvance();

        }

        foreach (array_chunk($delete_ids,1000)as  $ids){
            if (count($ids)){
                $delete_ids_str = implode(',', $ids);
                $sql = "UPDATE `wordbank_translation` SET `deleted_at` = '".$save_time."' WHERE `id` IN ($delete_ids_str)";
                $res=$pdo->exec($sql);
                if ($res != count($ids)){
                    dd($sql, '删除单词例句失败');
                }
            }
        }


        foreach (array_chunk($update_date,1000)as  $date){
            config(['database.default' => 'dev']);
            if (count($date))
                $this->batchUpdate($date);
        }
        $this->output->progressFinish();


    }

    protected function batchUpdate($multipleData = array())
    {

        if (empty($multipleData)) {
            return false;
        }
        $updateColumn    = array_keys($multipleData[0]);
        $referenceColumn = $updateColumn[0]; //e.g id
        unset($updateColumn[0]);
        $whereIn = "";

        $q = "UPDATE wordbank_translation SET ";
        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn." = CASE ";

            foreach ($multipleData as $data) {
                $q .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN '".$data[$uColumn]."' ";
            }
            $q .= "ELSE ".$uColumn." END, ";
        }
        foreach ($multipleData as $data) {
            $whereIn .= "'".$data[$referenceColumn]."', ";
        }
        $q = rtrim($q, ", ")." WHERE ".$referenceColumn." IN (".rtrim($whereIn, ', ').")";

        // Update
        return \DB::update(\DB::raw($q));
    }
}
