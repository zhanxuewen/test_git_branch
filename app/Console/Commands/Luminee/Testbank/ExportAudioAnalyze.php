<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class ExportAudioAnalyze extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:audio:analyze';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $one = 0;
    protected $two = 0;
    protected $three = 0;
    protected $four = 0;
    protected $five = 0;

    protected $map = [];
    protected $students = [846017,846112,846402,846651,849467,850671,850672,850673,850674,850675,850677,850679,850680,850682,850683,850685,850687,850688,850690,850691,850695,850697,850702,850705,850709,850711,850714,850715,850717,850719,850720,850721,850722,850723,850724,850725,850727,850728,850730,850731,850732,850733,850734,850735,850736,850738,850741,850744,850746,850747,850748,850751,850752,850753,850754,850755,850756,850757,850761,850764,850765,850766,850767,850771,850772,850773,850774,850775,850778,850780,850781,850783,850784,850785,850787,850788,850789,850790,850791,850792,850793,850794,850796,850797,850798,850801,850802,850804,850806,850807,850808,850812,850814,850817,850819,850820,850823,850824,850825,850827,850830,850831,850832,850833,850835,850838,850839,850841,850842,850843,850844,850847,850849,850850,850851,850852,850854,850855,850857,850858,850863,850866,850868,850869,850879,850882,850885,850887,850888,850890,850891,850892,850895,850896,850898,850900,850910,850920,850922,850925,850929,850935,850936,850940,850949,850953,850959,850962,850963,850965,850973,850989,850996,851006,851007,851008,851019,851025,851029,851031,851035,851036,851040,851041,851042,851044,851064,851074,851077,851080,851082,851084,851087,851091,851107,851109,851112,851116,851119,851138,851140,851143,851146,851151,851153,851156,851169,851177,851236,851243,851247,851254,851293,851304,851320,851361,851363,851373,851385,851386,851421,851444,851530,851569,851583,851595,851612,851658,851668,851679,851733,851784,851789,852033,852034,852517,852863,852931,853149,853425,853439,853553,853596,853908,853992,854000,854135,854495,854959,855025,855041,855923,855985,857273,857420,857422,857470,858185,858201,858204,858205,858753,858801];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->map = [
            'homework_student_audio_record' => [89912866, 90359285],
            'library_student_audio_record' => [3333231, 3438892],
            'activity_student_book_audio_record' => [20596577, 20811164]
        ];
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
//        $this->sumAndSort();
        DB::setPdo($this->getConnPdo('core', 'online4'));
        foreach ($this->map as $table => $item) {
            list($min, $max) = $item;

            // Update...
            $max = DB::table($table)->max('id');
            $this->comment($max);
            // End...

            while ($min <= $max) {
                $rows = DB::table($table)->whereBetween('id', [$min, $max])
                    ->whereIn('student_id',$this->students)
                    ->where('star_record', '<>', '')->selectRaw('id, student_id, recognized_result')->take(5000)->get();
                $students = [];
                $m = count($rows) - 1;
                if ($m < 0) break;
                foreach ($rows as $k => $row) {
                    $check = json_decode($row->recognized_result, true)['check_record'];

                    // Update..
                    $t = (substr_count(rtrim($check, ','), ',') + 1);
                    $s_id = $row->student_id;
                    isset($students[$s_id]) ? $students[$s_id] += $t : $students[$s_id] = $t;
                    // End..

//                    $this->sum($check);
                    if ($k == $m)
                        $min = $row->id + 1;
                }
                $this->info($min);

                // Update...
                \Storage::put($table . '_' . $min . '.json', json_encode($students));
            }
            $this->line($table . ' -- Done');
//            $this->line($this->one);
//            $this->line($this->two);
//            $this->line($this->three);
//            $this->line($this->four);
//            $this->line($this->five);
//            $this->line('-----------------');
        }
    }

    protected function sumAndSort()
    {
        // Read
        $path = storage_path('app');
        foreach (scandir($path) as $file) {
            if (in_array($file, ['.', '..', '.DS_Store', '.gitignore'])) continue;
            $arr = json_decode(\Storage::get($file), true);
            dd($arr);
        }
    }

    protected function sum($check)
    {
        switch (substr_count(rtrim($check, ','), ',') + 1) {
            case 1:
                return $this->one++;
            case 2:
                return $this->two++;
            case 3:
                return $this->three++;
            case 4:
                return $this->four++;
            case 5:
                return $this->five++;
            default:
                return 0;
        }
    }

}
