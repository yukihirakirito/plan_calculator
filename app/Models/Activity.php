<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'fu_activity';
    protected $fillable = [
        'id', 
        'day', 
        'slot', 
        'room_id', 
        'groupid', 
        'groupid2', 
        'course_slot', 
        'leader_login2', 
        'leader_login', 
        'lastmodifier_login', 
        'repeat_id', 
        'done', 
        'create_time', 
        'lastmodified_time', 
        'description', 
        'room_name', 
        'psubject_name', 
        'short_subject_name', 
        'psubject_code', 
        'session_description', 
        'tutor_login', 
        'pterm_name', 
        'term_id', 
        'group_name', 
        'course_id', 
        'psubject_id', 
        'session_type', 
        'psyllabus_id', 
        'area_id', 
        'area_name', 
        'department_id', 
        'noi_dung', 
        'nv_sinh_vien', 
        'hoc_lieu_mon', 
        'nv_giang_vien', 
        'tai_lieu_tk', 
        'tu_hoc', 
        'tl_buoi_hoc', 
        'muc_tieu', 
        'custom_edit', 
        'start_time', 
        'end_time', 
        'is_online','url_room_online'
    ];
    public $timestamps = false;
    protected $attributes = [
        'groupid2' => 1,
        'repeat_id' => 0,
        'done' => 0,
        'tutor_login' => '',
        'noi_dung' => '',
        'nv_sinh_vien' => '',
        'hoc_lieu_mon' => '',
        'nv_giang_vien' => '',
        'tai_lieu_tk' => '',
        'tu_hoc' => '',
        'tl_buoi_hoc' => '',
        'muc_tieu' => '',
        'custom_edit' => 0,
    ];
}
