<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    protected $table = 'fu_group_member';
    protected $fillable = ['groupid','member_login','date','note','attend_time','fee_status','fee_code','current_status','skill_code','period_id','period_fee_status','fee_due_date','fee_submit_date','lock_date','curriculum_id','subject_id','term_id','group_name','subject_code','period_ordering','full_name','study_status','group_member_status','IsCancelled','leader_login','loai','note_comment','time_evaluate','time_ evaluate','id','groupid','member_login','user_code','date','note','attend_time','fee_status','fee_code','current_status','skill_code','period_id','period_fee_status','fee_due_date','fee_submit_date','lock_date','curriculum_id','subject_id','term_id','group_name','subject_code','period_ordering','full_name','study_status','start_date','end_date','temp','is_hoc_lai','group_member_status','IsCancelled','leader_login','loai','note_comment','time_evaluate','time_ evaluate','id','groupid','member_login','user_code','date','note','attend_time','fee_status','fee_code','current_status','skill_code','period_id','period_fee_status'];
    public $timestamps = false;
    protected $attributes = [
        'attend_time' => 0,
        'fee_status' => 0,
        'fee_code' => '',
        'current_status' => 0,
        'skill_code' => '',
        'period_id' => '0',
        'period_fee_status' => '0',
        'fee_due_date' => '1970-01-01',
        'fee_submit_date' => '1970-01-01',
        'lock_date' => '1970-01-01',
        'curriculum_id' => '0',
        'subject_id' => '0',
        'term_id' => '0',
        'group_name' => '',
        'subject_code' => '',
        'period_ordering' => '0',
        'full_name' => '',
        'study_status' => '0',
        'start_date' => '1970-01-01',
        'end_date' => '1970-01-01',
        'temp' => '0',
        'is_hoc_lai' => '0',
        'group_member_status' => '0',
        'IsCancelled' => '0',
        'leader_login' => '',
        'loai' => '',
        'note_comment' => '',
        'time_evaluate' => '1970-01-01',
        'time_ evaluate' => '1970-01-01',
    ];

}
