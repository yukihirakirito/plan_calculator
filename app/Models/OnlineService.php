<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineService extends Model
{
    protected $table = 'fu_online_services';
    protected $fillable = ['id','is_refunded','close_time','student_user_login','staff_user_login','available_service_id','service_id','service_detail_id','status','note','student_note','user_role','paid_time','created_at','updated_at'];
    public $timestamps = true;
}
