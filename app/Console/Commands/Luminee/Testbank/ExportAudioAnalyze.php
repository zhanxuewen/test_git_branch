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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->map = [
//            'homework_student_audio_record' => [89912866, 90359285],
//            'library_student_audio_record' => [3333231, 3438892],
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
        DB::setPdo($this->getConnPdo('core', 'online4'));
        foreach ($this->map as $table => $item) {
            list($min, $max) = $item;
            while ($min <= $max) {
                $rows = DB::table($table)->whereBetween('id', [$min, $max])
                    ->where('star_record', '<>', '')->selectRaw('id, recognized_result')->take(5000)->get();
                $m = count($rows) - 1;
                if ($m < 1) break;
                foreach ($rows as $k => $row) {
                    $check = json_decode($row->recognized_result, true)['check_record'];
                    $this->sum($check);
                    if ($k == $m)
                        $min = $row->id + 1;
                }
                $this->info($min);
            }
            $this->line($this->one);
            $this->line($this->two);
            $this->line($this->three);
            $this->line($this->four);
            $this->line($this->five);
            $this->line('-----------------');
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
