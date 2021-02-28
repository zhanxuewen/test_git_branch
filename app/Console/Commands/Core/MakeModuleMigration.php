<?php

namespace App\Console\Commands\Core;

use Illuminate\Console\Command;

/**
 * @author LuminEe
 */
class MakeModuleMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:migration {module} {migration}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Migration to Module Direction';
    
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
        $module = $this->argument('module');
        $dir    = database_path('migrations').'/'.$module;
        if (!is_dir($dir)) {
            $this->error("Module [$module] folder doesn't exist! Please create and try again.");
        } else {
            $this->call('make:migration', ['--path' => 'database/migrations/'.$module, 'name' => $this->argument('migration')]);
        }
    }
}
