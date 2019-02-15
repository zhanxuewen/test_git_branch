<?php

namespace App\Console\Commands\Rpc;

use App\Models\Rpc\Repo\Functions;
use App\Models\Rpc\Service\Api;
use App\Models\Rpc\Repo\Repository;
use App\Models\Rpc\Service\ApiCall;
use App\Models\Rpc\Service\Service;
use Illuminate\Console\Command;

class CaptureRpcService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capture:rpc:service';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture The Rpc Service Database';
    
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
        $Services_Dir = env('RPC_DIR').'/app/Services';
        $Services     = scandir($Services_Dir);
        $bar          = $this->output->createProgressBar(count($Services));
        foreach ($Services as $service) {
            if ($service == '.' or $service == '..' or $service == '_BaseService.php') {
                $bar->advance();
                continue;
            }
            $service_file = file_get_contents($Services_Dir.'/'.$service, FILE_USE_INCLUDE_PATH);
            preg_match('/(namespace [a-zA-Z\\\\ ]+;)[\s\S]*class( [a-zA-Z]+ )/i', $service_file, $matches);
            $service_file = str_replace(array_shift($matches), '', $service_file);
            $class_name   = str_replace(['namespace', ';', ' '], '', implode('\\', $matches));
            preg_match('/__construct[\s]*\(([a-zA-Z\$\\\\ ,\s]+)\)\s+\{\s+([a-zA-Z0-9\$-> =;\s]+)\}/i', $service_file, $matches);
            if (!empty($matches)) {
                $service_file = str_replace(array_shift($matches), '', $service_file);
                list($repos, $vars) = $matches;
                $repos = explode(',', preg_replace('/(\$[a-zA-Z]+)|\s|(R\\\\)/', '', $repos));
                asort($repos);
                $vars = preg_replace('/(\$[a-zA-Z]+)|->|;|\s/', '', str_replace('=', ',', $vars));
                $vars = explode(',', substr($vars, 0, -1));
                asort($vars);
            }
            $create  = [
                'code' => str_replace('.php', '', $service),
                'class_name' => $class_name,
                'ioc_variables' => isset($vars) ? implode(',', $vars) : null,
                'ioc_repos' => isset($repos) ? implode(',', $repos) : null,
            ];
            $Service = Service::firstOrCreate($create);
            unset($repos, $vars);
            $this->getFunctions($service_file, $Service->id);
            $bar->advance();
        }
        $bar->finish();
    }
    
    /**
     * @author LuminEe
     * @param string $content
     * @param int $service_id
     * @return void
     */
    protected function getFunctions($content, $service_id)
    {
        $preg = '/(public|protected|private) function ([0-9a-zA-Z_]+)\(([\$0-9a-zA-Z, _=\[\]\'"]*)\)[\s]*\{[\s]*';
        $preg .= '([\s\S](?!function)[\s\S])*\}[\s]*/i';
        preg_match($preg, $content, $matches);
        if (!empty($matches) && substr_count($matches[0], 'function') > 1) {
            $preg = '/(public|protected|private) function ([0-9a-zA-Z_]+)\(([\$0-9a-zA-Z, _=\[\]\'"]*)\)[\s]*\{[\s]*';
            $preg .= '([\s\S](?! function )[\s\S])*\}[\s]*/i';
            preg_match($preg, $content, $matches);
        }
        if (!empty($matches)) {
            $string = array_shift($matches);
            list($modifier, $function_name, $params) = $matches;
            $has_transaction = strstr($string, 'DB::beginTransaction') ? 1 : 0;
            preg_match('/return \$this->success\(([^;]*)\);/i', $string, $matches);
            $return = empty($matches) ? null : $matches[1];
            $create = [
                'service_id' => $service_id,
                'function_name' => $function_name,
                'modifier' => $modifier,
                'params' => empty($params) ? null : str_replace(' ', '', $params),
                'has_transaction' => $has_transaction,
                'return' => $return,
                'author' => ''
            ];
            $Api    = Api::firstOrCreate($create);
            $this->getCalls($string, $Api->id);
            $content = str_replace($string, '', $content);
            $this->getFunctions($content, $service_id);
        }
    }
    
    protected function getCalls($content, $api_id)
    {
        preg_match('/\$this->([\w->]+)\(([^;]*)\);/i', $content, $matches);
        if (!empty($matches)) {
            $string = array_shift($matches);
            list($function_name, $params) = $matches;
            $repository_id = 0;
            $function_id   = 0;
            if (strstr($function_name, '->')) {
                list($repo_name, $function_name) = explode('->', $function_name);
                $service = Api::find($api_id)->service;
                $ioc     = array_combine(explode(',', $service->ioc_variables), explode(',', $service->ioc_repos));
                if ($repo = Repository::where('code', $ioc[$repo_name])->first()) {
                    $repository_id = $repo->id;
                    if ($function = Functions::where('repository_id', $repository_id)
                        ->where('function_name', $function_name)->first()) {
                        $function_id = $function->id;
                    }
                }
            }
            $create = [
                'api_id' => $api_id,
                'repository_id' => $repository_id,
                'function_id' => $function_id,
                'function_name' => $function_name,
                'params' => empty($params) ? null : str_replace(' ', '', $params)
            ];
            ApiCall::firstOrCreate($create);
            $content = str_replace($string, '', $content);
            $this->getCalls($content, $api_id);
        }
    }
}
