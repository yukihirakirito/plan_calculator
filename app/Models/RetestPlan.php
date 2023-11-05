<?php

namespace App\Models\Fu;

use Illuminate\Database\Eloquent\Model;

class RetestPlan extends Model
{
    protected $table = 'fu_retest_plan';    
    /**
     * fillable
     *
     *  organize_type: 0 - online | 1 - offline
     */
    protected $fillable = ['id','available_service_id','name','organize_type','date','slot','area_id','room','test_type','supervisor1','supervisor2','max_number','room_id','url'];
    public $timestamps = false;
    public function __construct(array $attributes = [])
    {
        $this->connection = session('campus_db');
        parent::__construct($attributes);
    }

    public function calendars()
    {
        return $this->hasMany('App\Models\RelearnOnlineCalendar','retest_plan_id','id');
    }
    public function relearn_online_calendar_users()
    {
        return $this->hasMany('App\Models\RelearnOnlineCalendarUser','retest_plan_id','id');
    }
    
}
