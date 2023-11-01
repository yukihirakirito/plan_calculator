<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'fu_area';
    public $timestamps = false;

    public function rooms()
    {
        return $this->hasMany('App\Models\Fu\Room','area_id','id');
    }
}
