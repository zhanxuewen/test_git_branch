<?php

return [
    'log' => Luminee\Reporter\Models\Log::class,

    'label' => App\Models\Label\Label::class,
    'labelType' => App\Models\Label\Type::class,

    'notice' => App\Models\Notice\Notice::class,

    'navicat' => App\Models\Log\Navicat::class,

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

    'tableIncrement' => App\Models\Monitor\TableIncrement::class,
    'orderIncrement' => App\Models\Monitor\OrderIncrement::class,
    'deviceUsageAmount' => App\Models\Monitor\DeviceUsageAmount::class,

];