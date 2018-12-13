<?php

namespace App\Console\Commands\Core;

use Illuminate\Console\Command;

/**
 * @author LuminEe
 */
class ModuleSeed extends Command
{
    protected $batch;

    protected $seeders;

    protected $count = 0;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:seed {module=all} {--class=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed Data Into Database By Modules';

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
        $this->prepareSeederTable();
        $_module = $this->argument('module');
        $class = $this->option('class');
        if ($_module == 'all') {
            $path = database_path('seeds');
            $dir_count = 0;
            foreach (scandir($path) as $module) {
                if ($module == '.' or $module == '..' or !is_dir($path . '/' . $module)) continue;
                $this->seedModule($module, $class);
                $dir_count++;
            }
            if ($dir_count == 0) $this->error('Empty Dir, Nothing to Seed -_-!');
        } else {
            $this->seedModule($_module, $class);
        }
        $this->info('Module Seed Has Done! ^_^');
    }

    protected function prepareSeederTable()
    {
        $this->createSeedersTable();
        $this->seeders = \DB::table('seeders')->pluck('seeder');
        $this->batch = \DB::table('seeders')->max('batch') + 1;
    }

    protected function seedModule($module, $_class = null)
    {
        $seeders = scandir(database_path('seeds') . '/' . $module);
        foreach ($seeders as $key => $seeder) {
            if (in_array($seeder, ['.', '..'])) continue;
            $class = $this->convertUnderline(str_replace('.php', '', $seeder));
            if (!is_null($_class) && $class != $_class) continue;
            if (in_array($class, $this->seeders)) continue;
            $Class = new $class;
            $this->seedClass($Class);
        }
        if ($this->count == 0) {
            $this->line('Module ' . $module . ' Nothing to seed.');
        } else {
            $this->info('Module ' . $module . ' Seed done!');
            $this->count = 0;
        }
    }

    protected function seedClass($class)
    {
        $class->run();
        $this->recordSeeder(get_class($class));
        $this->count++;
        $this->info(get_class($class) . ' Seed.');
    }

    protected function convertUnderline($str)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }

    protected function createSeedersTable()
    {
        if (!empty(\DB::select("Show tables like 'seeders'"))) return true;
        $schema = "CREATE TABLE IF NOT EXISTS `seeders` (`seeder` varchar(255) COLLATE utf8_unicode_ci NOT NULL, `batch` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        return \DB::select($schema);
    }

    protected function recordSeeder($class)
    {
        \DB::table('seeders')->insert(['seeder' => $class, 'batch' => $this->batch]);
    }
}
