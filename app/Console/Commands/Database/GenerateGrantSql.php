<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;

/**
 * @author LuminEe
 */
class GenerateGrantSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:grant:sql {grant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Grant Sql';

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
     * @throws
     * @return void
     */
    public function handle()
    {
        $grant = $this->argument('grant');
        $grants = [
            'all' => 'all privileges',
            'dba' => 'select,insert,update,delete,create,alter,index,lock tables',
            'rw' => 'select,insert,update,delete',
            'ro' => 'select'
        ];
        $users = [
            'all' => 'superman',
            'dba' => 'dbadmin',
            'rw' => 'director',
            'ro' => 'developer'
        ];
        if (!isset($grants[$grant])) die('Grant not exist!');
        $privileges = $grants[$grant];
        $user = $this->ask('User name is', $users[$grant]);
        $pwd = $this->ask('Password is');
        $db = $this->ask('DB name is', '*');
        $ip = $this->ask('Ip is', '172.17.%');
        $sql = "grant $privileges on $db.* to '$user'@'$ip' identified by '$pwd';";
        $this->line($sql);
    }
}
