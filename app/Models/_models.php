<?php

return [
    'log' => Luminee\Reporter\Models\Log::class,

    'label' => App\Models\Label\Label::class,
    'labelType' => App\Models\Label\Type::class,

    'notice' => App\Models\Notice\Notice::class,

    'account' => App\Models\User\Account::class,

    'role' => Luminee\Watchdog\Model\Role::class,
    'power' => Luminee\Watchdog\Model\Power::class,
    'group' => Luminee\Watchdog\Model\Group::class,
    'rolePower' => Luminee\Watchdog\Model\RolePower::class,
    'accountRole' => Luminee\Watchdog\Model\AccountRole::class,

    'config' => App\Models\System\Config::class,

    'column' => App\Models\Database\Column::class,
    'dbGroup' => App\Models\Database\Group::class,
    'migration' => App\Models\Database\Migration::class,

    'model' => App\Models\Rpc\DB\Model::class,
    'module' => App\Models\Rpc\DB\Module::class,
    'relation' => App\Models\Rpc\DB\Relation::class,

    'functions' => App\Models\Rpc\Repo\Functions::class,
    'repository' => App\Models\Rpc\Repo\Repository::class,

    'api' => App\Models\Rpc\Service\Api::class,
    'call' => App\Models\Rpc\Service\ApiCall::class,
    'service' => App\Models\Rpc\Service\Service::class,

    'server' => App\Models\Rpc\TcpServer::class,

    'tableIncrement' => App\Models\Monitor\TableIncrement::class,
    'orderIncrement' => App\Models\Monitor\OrderIncrement::class,
    'deviceUsageAmount' => App\Models\Monitor\DeviceUsageAmount::class,

];