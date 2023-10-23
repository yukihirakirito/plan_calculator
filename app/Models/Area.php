<?php

namespace App\Models\Fu;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'fu_area';
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        $this->connection = session('campus_db');
        parent::__construct($attributes);
    }
    public function rooms()
    {
        return $this->hasMany('App\Models\Fu\Room','area_id','id');
    }
}
