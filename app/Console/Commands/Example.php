<?php

namespace App\Console\Commands;

use App\Models\Bill;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

class Example extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'example';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $a = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            Bill::firstOrCreate(['name' => $i]);
        }
        $b = microtime(true);
        echo $b - $a;
    }
}
