<?php

namespace App\Models\Fu;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'fu_room';
    protected $fillable = ['area_id','room_name','capacity','room_type','is_internal','valid_from','description'];

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        $this->connection = session('campus_db');
        parent::__construct($attributes);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'room_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
