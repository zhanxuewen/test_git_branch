<?php

namespace App\Console\Commands\Rpc;

use App\Models\Rpc\DB\Model;
use App\Models\Rpc\DB\Module;
use App\Models\Rpc\Repo\Functions;
use App\Models\Rpc\Repo\Repository;
use Illuminate\Console\Command;

class CaptureRpcRepo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capture:rpc:repo';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture The Rpc Repo Database';
    
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
        $Repos_Dir = env('RPC_DIR').'/app/Repositories';
        $Repos     = scandir($Repos_Dir);
        $bar       = $this->output->createProgressBar(count($Repos));
        foreach ($Repos as $repo) {
            if ($repo == '.' or $repo == '..' or $repo == '_BaseRepository.php') {
                $bar->advance();
                continue;
            }
            $repo_file = file_get_contents($Repos_Dir.'/'.$repo, FILE_USE_INCLUDE_PATH);
            preg_match('/(namespace [a-zA-Z\\\\ ]+;)[\s\S]*(class [a-zA-Z]+ )/i', $repo_file, $matches);
            $repo_file  = str_replace(array_shift($matches), '', $repo_file);
            $class_name = str_replace(['namespace', 'class ', ';', ' '], '', implode('\\', $matches));
            preg_match('/\$this->bindModule\(\'([a-zA-Z]+)\'\);/i', $repo_file, $matches);
            if (empty($matches)) {
                $module_id = 0;
            } else {
                $module_id = Module::where('code', $matches[1])->first()->id;
            }
            $create = [
                'code' => str_replace('.php', '', $repo),
                'module_id' => $module_id,
                'class_name' => $class_name
            ];
            $Repo   = Repository::firstOrCreate($create);
            $this->getFunctions($repo_file, $Repo->id);
            $bar->advance();
        }
        $bar->finish();
    }
    
    /**
     * @author LuminEe
     * @param string $content
     * @param int $repository_id
     * @return void
     */
    protected function getFunctions($content, $repository_id)
    {
        $preg = '/(public|protected|private) function ([0-9a-zA-Z_]+)\(([\$0-9a-zA-Z, _=\[\]\'"]*)\)[\s]*\{[\s]*';
        $preg .= '([\s\S](?!function)[\s\S])*\}[\s]*/i';
        preg_match($preg, $content, $matches);
        if (!empty($matches)) {
            $string = array_shift($matches);
            list($modifier, $function_name, $params) = $matches;
            preg_match('/->setModel\([\'"]([a-zA-Z_]+)[\'"]\)/i', $string, $matches);
            if (!empty($matches)) {
                $module_id    = Repository::find($repository_id)->module_id;
                $model        = Model::where('alias', $matches[1])->where('module_id', $module_id)
                    ->orderBy('created_at', 'desc')->first();
                $set_model_id = empty($model) ? 0 : $model->id;
            } else {
                $set_model_id = 0;
            }
            $create = [
                'repository_id' => $repository_id,
                'function_name' => $function_name,
                'modifier' => $modifier,
                'params' => empty($params) ? null : str_replace(' ', '', $params),
                'set_model_id' => $set_model_id,
                'author' => ''
            ];
            Functions::firstOrCreate($create);
            $content = str_replace($string, '', $content);
            $this->getFunctions($content, $repository_id);
        }
    }
}
