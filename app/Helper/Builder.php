<?php

namespace App\Helper;

class Builder
{
    private $ModelMap;
    
    public function __construct()
    {
        $this->ModelMap = include_once app_path('Models').'/_models.php';
    }
    
    /**
     * @param string $model
     * @return \App\Models\BaseModel
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