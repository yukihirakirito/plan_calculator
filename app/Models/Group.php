<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'fu_group';
    protected $fillable = ['group_fb_comment','lastmodifier_login', 'id', 'type', 'is_class_online', 'body_id', 'group_name', 'psubject_name', 'pterm_name', 'psubject_code', 'num_of_credit', 'slot', 'start_date', 'end_date', 'syllabus_id', 'attendance_required', 'number_student', 'finished', 'is_lock', 'lock_type', 'lock_deadline', 'grade_lock_list', 'approved', 'approved_grade_list', 'pterm_id', 'block_id', 'block_name', 'psubject_id', 'ptime_table', 'grade_hide_list', 'done_session', 'total_session', 'is_started', 'status', 'skill_code', 'teacher', 'room_id', 'hide_grade_for_student', 'is_locked', 'department_id', 'tong_bao_ve', 'id_baove', 'tong_du_dieu_kien', 'availability', 'spilit', 'max_sv', 'danh_gia','is_virtual'];
    public $timestamps = false;
    protected $attributes = [
        'type' => 1,
        'slot' => 0,
        'number_student' => 0,
        'finished' => 0,
        'is_lock' => 0,
        'lock_type' => 0,
        'lock_deadline' => 0,
        'grade_lock_list' => '',
        'approved' => 0,
        'approved_grade_list' => '',
        'block_id' => 0,
        'block_name' => '',
        'ptime_table' => '',
        'grade_hide_list' => '',
        'done_session' => 0,
        'total_session' => 0,
        'is_started' => 0,
        'status' => '',
        'hide_grade_for_student' => 0,
        'is_locked' => 0,
        'tong_bao_ve' => 0,
        'id_baove' => '',
        'tong_du_dieu_kien' => 0,
        'availability' => 0,
        'spilit' => 0,
        'max_sv' => 0,
        'danh_gia' => 0,
        'is_virtual' => 0, // lớp ảo dùng cho tổ chức thi lại
    ];

    public function groupMembers()
    {
        return $this->hasMany('App\Models\GroupMember', 'groupid', 'id');
    }

    
}
