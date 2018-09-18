<?php

namespace App\Console\Commands;

use App\Models\Rpc\Service\Api;
use App\Models\Rpc\Repo\Repository;
use App\Models\Rpc\Service\ApiCall;
use App\Models\Rpc\Service\Service;
use App\Models\TcpServer;
use Illuminate\Console\Command;

class CaptureTcpServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capture:tcp:server';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture The Tcp Server Database';
    
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
        $TcpServerFile = env('RPC_DIR').'\app\Console\Commands\TcpServer.php';
        $server_file   = file_get_contents($TcpServerFile, FILE_USE_INCLUDE_PATH);
//        $this->getServices($server_file);
        $this->getRepositories($server_file);
    }
    
    protected function getServices($server_file)
    {
        $preg = '/__construct[\s]*\(([a-zA-Z\$\\\\ ,\s]+)\)\s+\{\s+([a-zA-Z0-9\$-> =;\s\:\_\(\)\[\]]+)\}/i';
        preg_match($preg, $server_file, $matches);
        if (!empty($matches)) {
            array_shift($matches);
            list($repos, $vars) = $matches;
            $repos    = collect(explode(',', preg_replace('/(\$[a-zA-Z]+)|\s|(S\\\\)/', '', $repos)));
            $repos    = $repos->filter(function ($value) {
                return strstr($value, 'Service');
            })->sort();
            $replace  = '/(\$[a-zA-Z]+)|->services\[\'|;|\s|\'\]/';
            $vars     = collect(explode(',', preg_replace($replace, '', str_replace('=', ',', $vars))));
            $vars     = $vars->filter(function ($value) {
                return strstr($value, 'Service');
            })->sort();
            $services = $vars->combine($repos);
            foreach ($services as $key => $value) {
                $class_name = 'App\Services\\'.$value;
                $service_id = Service::where('class_name', $class_name)->first()->id;
                TcpServer::firstOrCreate([
                    'alias' => $key,
                    'class_name' => $class_name,
                    'instance_id' => $service_id,
                    'instance_type' => 'service'
                ]);
            }
        }
    }
    
    protected function getRepositories($server_file)
    {
        $preg = '/\$modules[\s=]*\[([a-zA-Z\'" ,\s]+)\]/i';
        preg_match($preg, $server_file, $matches);
        if (!empty($matches)) {
            $repos = explode(',', str_replace(['\'', ' '], '', $matches[1]));
            foreach ($repos as $repo) {
                $class_name = 'App\Repositories\\'.ucfirst($repo).'Repository';
                if (!Repository::where('class_name', $class_name)->first()) {
                    dd($class_name);
                }
                $repo_id = Repository::where('class_name', $class_name)->first()->id;
                TcpServer::firstOrCreate([
                    'alias' => $repo,
                    'class_name' => $class_name,
                    'instance_id' => $repo_id,
                    'instance_type' => 'repository'
                ]);
            }
        }
    }
}
