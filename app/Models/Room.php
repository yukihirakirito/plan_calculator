<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'fu_room';
    protected $fillable = ['area_id','room_name','capacity','room_type','is_internal','valid_from','description'];

    public $timestamps = false;


    public function activities()
    {
        return $this->hasMany(Activity::class, 'room_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
