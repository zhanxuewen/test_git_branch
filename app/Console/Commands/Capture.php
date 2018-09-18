<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Capture extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capture';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture The Project';
    
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
        if (!$rpc_dir = env('RPC_DIR', null)) {
            $this->error('File [.env] need config: RPC_DIR');
            return;
        }
        $commands = [
            [
                'comm' => 'capture:rpc:db',
                'desc' => 'Rpc -> DB',
                'args' => [],
            ],
            [
                'comm' => 'capture:rpc:db',
                'desc' => 'Rpc -> DB with Relate',
                'args' => ['--relate' => true],
            ],
            [
                'comm' => 'capture:rpc:repo',
                'desc' => 'Rpc -> Repo',
                'args' => [],
            ],
            [
                'comm' => 'capture:rpc:service',
                'desc' => 'Rpc -> Service',
                'args' => [],
            ],
        ];
        foreach ($commands as $command) {
            $this->info('Capturing '.$command['desc']);
            $this->call($command['comm'], $command['args']);
            $this->info('');
        }
    }
    
}
