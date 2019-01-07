<?php

namespace App\Console\Commands\Core;

use Illuminate\Console\Command;

/**
 * @author LuminEe
 */
class ModuleMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:migrate {module=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Database Like module:migrate';

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
        $this->configDbaPassword();
        $module = $this->argument('module');
        $modules = $this->listModules(database_path('migrations'));
        if (in_array($module, $modules)) {
            $this->migrate($module);
        } elseif ($module == 'all') {
            $this->migrateAll($modules);
        } else {
            $this->error("Module [$module] folder doesn't exist! Please check it.");
            exit();
        }
        $this->info('Module Migrate Has Done! ^_^');
    }

    protected function configDbaPassword()
    {
        config(['database.connections.mysql.username' => 'dbadmin']);
        config(['database.connections.mysql.password' => env('DBA_PASSWORD')]);
    }

    protected function migrate($module)
    {
        $this->call('migrate', ['--path' => 'database/migrations/' . $module]);
    }

    protected function migrateAll($modules)
    {
        foreach ($modules as $module) {
            $this->migrate($module);
        }
        $this->call('migrate');
    }

    protected function listModules($Dir)
    {
        foreach (scandir($Dir) as $dir) {
            if (is_dir(realpath($Dir . '/' . $dir)) && !in_array($dir, ['.', '..'])) $modules[] = $dir;
        }
        return isset($modules) ? $modules : [];
    }
}
