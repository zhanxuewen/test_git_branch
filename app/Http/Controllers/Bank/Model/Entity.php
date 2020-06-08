<?php

namespace App\Http\Controllers\Bank\Model;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends BaseModel
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    
    protected $table = 'testbank_entity';
    
    protected $hidden = ['deleted_at'];
    
    protected $fillable = ['testbank_id', 'testbank_extra_value', 'testbank_item_value', 'fix'];
    
    public function testbank()
    {
        return $this->belongsTo('App\Models\Testbank\Testbank', 'testbank_id', 'id');
    }
    
    public function getTestbankItemValueAttribute($value)
    {
        if (!empty($this->attributes['fix'])) {
            return html_entity_decode($this->attributes['fix'], ENT_QUOTES, 'UTF-8');
        }
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    
    public function setTestbankItemValueAttribute($value)
    {
        $value                                   = str_replace('\u2018', '&#039;', $value);
        $this->attributes['testbank_item_value'] = str_replace('\u2019', '&#039;', $value);
    }
    
    public function setTestbankExtraValueAttribute($value)
    {
        $value                                    = str_replace('\u2018', '&#039;', $value);
        $this->attributes['testbank_extra_value'] = str_replace('\u2019', '&#039;', $value);
    }

}
