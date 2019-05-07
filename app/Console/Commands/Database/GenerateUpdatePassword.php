<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;

/**
 * @author LuminEe
 */
class GenerateUpdatePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:update:password {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Update Password';

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
        $user = $this->argument('user');
        $users = [
            'root' => 'superman',
            'dba' => 'dbadmin',
            'rw' => 'director',
            'ro' => 'developer'
        ];
        $user = $this->ask('User name is', $users[$user]);
        $pwd = $this->ask('New Password is');
        $ip = $this->ask('Host is', '172.17.%');
        $sql = "update mysql.user set authentication_string = password('$pwd') where user = '$user' and Host = '$ip';";
        $this->line($sql);
    }
}
