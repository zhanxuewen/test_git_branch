<?php

namespace App\Console\Commands\Luminee\Wordbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class SyncWordbankToLearning extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:wordbank_to:learning {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync wordbank to learning testbank';

    protected $core_pdo;
    protected $learn_pdo;

    protected $coo;
    protected $index = 1;

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
        $connections = [
            'online' => ['core' => 'online', 'learning' => 'online_learning'],
            'dev' => ['core' => 'dev', 'learning' => 'dev_learning']
        ];
        $conn = $this->argument('conn');
        $this->core_pdo = $this->getPdo($connections[$conn]['core']);
        $this->learn_pdo = $this->getPdo($connections[$conn]['learning']);
        $words = DB::setPdo($this->core_pdo)->table('wordbank')->whereNull('deleted_at')->selectRaw('vocabulary')->get();
//        $words = ['about', 'above'];
        $this->coo = count($words);
        foreach ($words as $word) {
            $this->handleWord(trim($word->vocabulary));
        }

    }

    protected function handleWord($word)
    {
        echo $this->index . '/' . $this->coo . ' ';
        $this->index++;
        $c_word = DB::setPdo($this->core_pdo)->table('wordbank')->where('vocabulary', $word)->whereNull('deleted_at')->first();
        if (is_null($c_word)) {
            echo $word . " [x] \r\n";
            return;
        }
        echo $word . ': ';
        $c_word_id = $c_word->id;
        $c_trans = DB::table('wordbank_translation')->where('wordbank_id', $c_word_id)->whereNull('deleted_at')->get();
        $c_tran_ids = $c_trans->pluck('id')->toArray();
        $c_sens = DB::table('wordbank_sentence')->whereIn('translation_id', $c_tran_ids)->whereNull('deleted_at')->get();
        $c_sen_ids = $c_sens->pluck('id')->toArray();
        if (DB::setPdo($this->learn_pdo)->table('wordbank')->where('id', $c_word_id)->whereNull('deleted_at')->count() == 0) {
            DB::table('wordbank')->insert(json_decode(json_encode($c_word), true));
            echo '{+} ';
        } else {
            echo '{} ';
        }
        $l_tran_ids = DB::table('wordbank_translation')->whereIn('id', $c_tran_ids)->whereNull('deleted_at')->get()->pluck('id')->toArray();
        $tran_create = [];
        foreach ($c_trans as $c_tran) {
            $c_tran_id = $c_tran->id;
            if (!in_array($c_tran_id, $l_tran_ids)) $tran_create[] = json_decode(json_encode($c_tran), true);
        }
        if (!empty($tran_create)) {
            DB::table('wordbank_translation')->insert($tran_create);
            echo '[' . count($tran_create) . '] ';
        } else {
            echo '[] ';
        }
        $l_sen_ids = DB::table('wordbank_sentence')->whereIn('id', $c_sen_ids)->whereNull('deleted_at')->get()->pluck('id')->toArray();
        $sen_create = [];
        foreach ($c_sens as $c_sen) {
            $c_sen_id = $c_sen->id;
            $data = json_decode(json_encode($c_sen), true);
            unset($data['wordbank_id']);
            if (!in_array($c_sen_id, $l_sen_ids)) $sen_create[] = $data;
        }
        if (!empty($sen_create)) {
            DB::table('wordbank_sentence')->insert($sen_create);
            echo '(' . count($sen_create) . ')';
        } else {
            echo '()';
        }
        echo "\r\n";
    }

}
