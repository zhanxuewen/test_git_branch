<?php

namespace App\Helper;

use App\Models as Models;
use App\Models\Rpc as Rpc;

class Builder
{
    private $ModelMap;
    
    public function __construct()
    {
        $this->ModelMap = [
            'model' => Rpc\DB\Model::class,
            'module' => Rpc\DB\Module::class,
            'relation' => Rpc\DB\Relation::class,
            
            'functions' => Rpc\Repo\Functions::class,
            'repository' => Rpc\Repo\Repository::class,
            
            'api' => Rpc\Service\Api::class,
            'call' => Rpc\Service\ApiCall::class,
            'service' => Rpc\Service\Service::class,
            
            'server' => Models\TcpServer::class,
            
            'account' => Models\User\Account::class,
        ];
    }
    
    /**
     * @param string $model
     * @return Models\BaseModel
     */
    public function setModel($model)
    {
        $Model = $this->ModelMap[$model];
        app()->singleton($Model, function () use ($Model) {
            return new $Model;
        });
        return app($Model);
    }
}