<?php

return [
    'log' => App\Models\Log\Log::class,
    
    'tableIncrement' => App\Models\Monitor\TableIncrement::class,
    'deviceUsageAmount' => App\Models\Monitor\DeviceUsageAmount::class,
    
    'server' => App\Models\TcpServer::class,
    
    'role' => App\Models\User\Role::class,
    'power' => App\Models\User\Power::class,
    'account' => App\Models\User\Account::class,
    'rolePower' => App\Models\User\RolePower::class,
    'accountRole' => App\Models\User\AccountRole::class,
    
    'config' => App\Models\System\Config::class,
    
    'model' => App\Models\Rpc\DB\Model::class,
    'module' => App\Models\Rpc\DB\Module::class,
    'relation' => App\Models\Rpc\DB\Relation::class,
    
    'functions' => App\Models\Rpc\Repo\Functions::class,
    'repository' => App\Models\Rpc\Repo\Repository::class,
    
    'api' => App\Models\Rpc\Service\Api::class,
    'call' => App\Models\Rpc\Service\ApiCall::class,
    'service' => App\Models\Rpc\Service\Service::class,

];