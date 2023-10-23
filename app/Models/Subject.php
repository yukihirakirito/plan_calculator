<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'fu_subject';
    public $timestamps = false;
    public function __construct(array $attributes = [])
    {
        $this->connection = session('campus_db');
        parent::__construct($attributes);
    }

    /**
     * @author Giapnt
     * @since 01/10/2023
     *
     * Hàm dùng để lấy danh sách môn không check Ip điểm danh
     * bao gòm các môn thể chất và gd quốc phòng
     */
    public static function getListSubjectIgnoreIp()
    {
        // trả về mã chuyển đổi
        return ['PHE01', 'PHE02', 'PHE03', 'VIE104', 'VIE103', 'INE222', 'MEC214', 'AUT109', 'PRE106', 'MOB103', 'MOB401', 'MOB104', 'MUL217', 'MUL218', 'MUL220', 'WEB205', 'SOF303', 'WEB203', 'PRE202', 'PRE208', 'PRE201', 'DOM201', 'TOU103', 'TOU201', 'PRO105', 'HOS403', 'HOS203'];
    }

    public static function getListSubjectOnline($onlySubjectCode = false)
    {
        $res = [];
        $campus9Plus = ['th', 'ts', 'tc', 'td', 'tk', 'tt', 'tp', 'tg', 'tb'];
        if (in_array(session('campus_db'), $campus9Plus)) {
            return $res;
        }

        $listIgnoreSkillCode = [
            'COM107', 'MOB204', 'NET106', 'WEB204', 'WEB302', 'SOF204', 'SOF205', 'SOF306', 'SOF307', 'MUL219', 'MUL315', 'MUL319', 'DOM102', 'DOM108', 'MAR207', 'PRE105', 'PRE204', 'WEB206', 'MEC126'
        ];

        $listSubjectOnline = self::on('ho')
        ->select(['skill_code', 'subject_code'])
        ->where('subject_type', 'Online')
        ->get();

        foreach ($listSubjectOnline as $key => $value) {
            if (!in_array($value->skill_code, $listIgnoreSkillCode)) {
                $res[$value->skill_code][] = $value->subject_code;
            }
        }

        if ($onlySubjectCode == true) {
            $res = array_merge([], ...array_values($res));
        }

        return $res;
    }

    public function Department() {
        return $this->belongsTo('App\Models\Fu\Department', 'department_id', 'id');
    }

    public function GradeSyllabus() {
        return $this->hasMany('App\Models\T7\GradeSyllabus', 'subject_id', 'id');
    }

    public function SyllabusPlan() {
        return $this->hasMany('App\Models\T7\SyllabusPlan', 'subject_id', 'id');
    }

    /**
     * ALTER TABLE `fu_subject` 
     * ADD COLUMN `max_student` int NULL DEFAULT NULL DEFAULT 40 COMMENT 'Số lượng sinh viên tối đa của môn học' AFTER `subject_type`,
     * ADD COLUMN `min_student` int NULL DEFAULT NULL DEFAULT 30 COMMENT 'Số lượng sinh viên tối thiểu của môn học' AFTER `max_student`;
     * 
     * ALTER TABLE `fu_subject` 
     * MODIFY COLUMN `max_student` int NULL DEFAULT '40' COMMENT 'Số lượng sinh viên tối đa của môn học' AFTER `subject_type`,
     * MODIFY COLUMN `min_student` int NULL COMMENT 'Số lượng sinh viên tối thiểu của môn học' AFTER `max_student`;
     */

    /**
     * ALTER TABLE `fu_activity` 
     * DROP INDEX `groupid_2`,
     * ADD INDEX `check_conflict`(`day` ASC, `slot` ASC) USING BTREE;
    */
}
