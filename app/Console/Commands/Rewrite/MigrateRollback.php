<?php

namespace App\Console\Commands\Rewrite;

use Illuminate\Console\Command;

class MigrateRollback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'rewrite migrate:rollback';

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
        $this->error('Console abandoned, rollback via database please.');
    }

}
