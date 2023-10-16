<?php

namespace App\Repositories\Admin\RetestHelperClasses;

use App\Repositories\Plan;

class Order
{
    private $id;
    private $student_user_login;
    private $student_user_code;
    private $status_name;
    private $student_note;
    private $retest_type_id;
    private $retest_subject;
    private $black_list_calendar = [];
    private $busy_calendar;
    private $assigned_plan = null;
    private $isFinal = false;
    private $created_at = "";
    private $skill_code = "";
    private $duplicated_calendar = [];

    public function __construct($id, $student, $type, $subject, $busy, $student_user_code, $status_name, $student_note, $created_at, $skill_code)
    {
        $this->id = $id;
        $this->student_user_login = $student;
        $this->retest_subject = $subject;
        $this->retest_type_id = $type;
        $this->busy_calendar = $busy;
        $this->status_name = $status_name;
        $this->student_user_code = $student_user_code;
        $this->student_note = $student_note;
        $this->created_at = $created_at;
        $this->skill_code = $skill_code;
    }

    public function setFinal()
    {
        $this->isFinal = true;
    }

    public function isFinal()
    {
        return $this->isFinal;
    }

    public function getData()
    {
        return [
            'id' => $this->id,
            'student_user_login' => $this->student_user_login,
            'retest_type_id' => $this->retest_type_id,
            'retest_subject' => $this->retest_subject,
            'student_user_code' => $this->student_user_code,
            'status_name' => $this->status_name,
            'student_note' => $this->student_note,
            'busy_schedules' => (array) $this->busy_calendar,
            'created_at' => $this->created_at,
            'skill_code' => $this->skill_code,
            'assigned_plan' => $this->assigned_plan,
            'duplicated_calendar' => $this->duplicated_calendar
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRetestTypeId()
    {
        return intval($this->retest_type_id);
    }

    public function setAssignPlan(Plan $plan)
    {
        $this->assigned_plan = $plan->getTimeData();
        $this->black_list_calendar[] = [
            'slot' => $plan->getSlot(),
            'date' => $plan->getDate(),
            'student_user_login' => $this->student_user_login,
            'isAssignedPlan' => 1
        ];
    }

    public function removeAssignedPlan(Plan $plan)
    {
        foreach ($this->black_list_calendar as $key => $calendar) {
            if ($plan->getDate() == $calendar['date'] && $plan->getSlot() == $calendar['slot'] && $calendar['isAssignedPlan'] == 1) {
                unset($this->black_list_calendar[$key]);
                break;
            }
        }
        $this->assigned_plan = null;
    }

    public function getAssignPlan()
    {
        return $this->assigned_plan;
    }

    public function getStudentUserLogin()
    {
        return $this->student_user_login;
    }

    public function isSameSubject($list_subject_code)
    {
        return in_array($this->retest_subject, $list_subject_code);
    }

    public function isSameSkillCode($list_skill_code)
    {
        return in_array($this->skill_code, $list_skill_code);
    }

    public function setDuplicatedCalendar($calendar){
        $is_existed = false;
        foreach ($this->duplicated_calendar  as $value) {
            if ($calendar['date'] == $value['date'] && $calendar['slot'] == $value['slot']) {
                $is_existed = true;
                break;
            }
        }
        if (!$is_existed) {
            $this->duplicated_calendar[] = $calendar;
        }
        
    }

    public function isBusy($data)
    {
        $date = $data['date'];
        $slot = $data['slot'];
        $id = $data['id'];
        if (in_array($id, $this->black_list_calendar)) {
            return 1;
        }
        $busy = [];
        $busy = array_filter($this->busy_calendar, function ($e) use ($date, $slot) {
            return $e['slot'] == $slot && $e['date'] == $date;
        });
        if (count($busy) > 0) {
            $this->black_list_calendar[] = $id;
            return 1;
        } else {
            return 0;
        }
    }

    public function setBusyCalendar($date, $slot)
    {
        $this->busy_calendar[] = [
            'date' => $date,
            'slot' => $slot
        ];
    }
}