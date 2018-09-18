<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public function withRelatedOrderBy($relation, $order_by, $sort = 'asc')
    {
        return $this->with([$relation => function ($query) use ($order_by, $sort) {
            $query->orderBy($order_by, $sort);
        }]);
    }
}