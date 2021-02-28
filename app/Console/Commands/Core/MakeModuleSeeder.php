<?php

namespace App\Console\Commands\Core;

use Artisan;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * @author LuminEe
 */
class MakeModuleSeeder extends Command
{
    protected $files;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:seeder {module} {seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Seeder to Module Direction';

    /**
     * Create a new command instance.
     *
     * @param $file Filesystem
     * @return void
     */
    public function __construct(Filesystem $file)
    {
        $this->files = $file;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws
     * @return void
     */
    public function handle()
    {
        $module = $this->argument('module');
        $dir = database_path('seeds') . '/' . $module;
        if (!is_dir($dir)) {
            $this->error("Module [$module] folder doesn't exist! Please create and try again.");
        } else {
            $this->createSeeder($dir);
        }
    }

    /**
     * @param string $path
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createSeeder($path)
    {
        $seeder = $this->argument('seeder');
        $name = date('Y_m_d_His') . '_' . $seeder;
        $file = $path . '/' . $name . '.php';
        $class = $this->convertUnderline($seeder);
        if (class_exists($class)) {
            $this->error("Class $class Has Exist!");
            return;
        }
        $stub = $this->files->get(app_path('Console') . '/Stub/seeder.stub');
        $stub = str_replace('{$Class}', $class, $stub);
        $this->files->put($file, $stub);
        Artisan::call('op');
        $this->info("File $name.php Create Success!");
    }

    protected function convertUnderline($str)
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }
}
