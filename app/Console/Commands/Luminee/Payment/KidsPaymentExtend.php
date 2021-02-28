<?php

namespace App\Console\Commands\Luminee\Payment;

use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KidsPaymentExtend extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kids:payment:extend';

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
     * @throws
     */
    public function handle()
    {
        $phones = $this->ask('Input Phones:');
        \DB::setPdo($this->getConnPdo('kids', 'online'));
        $accounts = \DB::table('user')->join('user_account', 'user_account.user_id', '=', 'user.id')
            ->join('payment_student_status', 'payment_student_status.student_id', '=', 'user_account.id')
            ->whereIn('phone', explode(',', $phones))->where('user_type_id', 1)
            ->selectRaw('user_account.id, nickname, school_id, expired_at')->get();
        $table = [];
        foreach ($accounts as $k => $account) {
            $table[] = [$k, $account->id, $account->nickname, $account->school_id, $account->expired_at];
        }
        $this->table(['K', 'ID', 'Nickname', 'School Id', 'Expired At'], $table);
        $keys = $this->ask('Chose [k] to handle', 0);
        $handle = [];
        foreach (explode(',', $keys) as $key) {
            list($k, $id, , , $date) = $table[$key];
            $new_date = Carbon::now()->addMonth()->toDateString();
            \DB::table('payment_student_status')->where('student_id', $id)->update(['expired_at' => $new_date]);
            \DB::table('payment_student_status_record')->where('student_id', $id)
                ->where('obtain_way', 'individual')->update(['value' => $new_date]);
            $show = \DB::table('payment_student_status')->join('payment_student_status_record',
                'payment_student_status.student_id', '=', 'payment_student_status_record.student_id')
                ->where('payment_student_status.student_id', '=', $id)->where('obtain_way', '=', 'individual')
                ->selectRaw('payment_student_status.student_id, expired_at, value')->first();
            $line = 'Query: ' . $show->student_id . ' Expired: ' . $show->expired_at . ' Value: ' . $show->value;
            $this->line("K: $k, ID: $id, Origin: [$date] $line");
        }
    }
}
