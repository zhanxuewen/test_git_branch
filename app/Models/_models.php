<?php

return [
    'log' => App\Models\Log\Log::class,
    
    'tableIncrement' => App\Models\Monitor\TableIncrement::class,
    
    'server' => App\Models\TcpServer::class,
    
    'account' => App\Models\User\Account::class,
    
    'model' => App\Models\Rpc\DB\Model::class,
    'module' => App\Models\Rpc\DB\Module::class,
    'relation' => App\Models\Rpc\DB\Relation::class,
    
    'functions' => App\Models\Rpc\Repo\Functions::class,
    'repository' => App\Models\Rpc\Repo\Repository::class,
    
    'api' => App\Models\Rpc\Service\Api::class,
    'call' => App\Models\Rpc\Service\ApiCall::class,
    'service' => App\Models\Rpc\Service\Service::class,

];