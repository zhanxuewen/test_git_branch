<?php

namespace App\Models\Notice;

use App\Models\BaseModel;

class Notice extends BaseModel
{
    protected $table = 'notice';

    protected $fillable = ['label_id', 'sender_id', 'receiver_id', 'content', 'is_system', 'status', 'is_visible', 'has_read'];

    public function label()
    {
        return $this->belongsTo('App\Models\Label\Label', 'label_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo('App\Models\User\Account', 'sender_id', 'id');
    }

    public function receiver()
    {
        return $this->belongsTo('App\Models\User\Account', 'receiver_id', 'id');
    }

}
