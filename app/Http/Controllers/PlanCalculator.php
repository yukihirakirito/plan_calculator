<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\GroupMember;
use App\Models\OnlineService;
use App\Repositories\Admin\RetestHelperClasses\Order;
use App\Repositories\EOSPlan;
use App\Repositories\OralPlan;
use App\Repositories\PracticePlan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanCalculator extends Controller
{
    const DAT = 1;
    const KHONG_DAT = 0;
    const TRUOT_DIEM_DANH = -1;
    const CO_DI_THI = 1;
    const CO_DI_THI_2 = 2;
    const KHONG_DI_THI = 0;

    const CHO_XEP_LOP = 80;
    /**
     * Xếp lớp theo lịch được tạo
     *
     * @param  mixed $request
     * @return mixed $response
     */
    public function index(){
        return view('PlanCalculator');
    }
    static public function createRetestPlan(Request $request)
    {
        try {
            $choosen_area_id = null;
            if ($request->has('is_arrange_by_area')) {
                if ($request->is_arrange_by_area == 1) {
                    if ($request->has('choosen_area_id') && $request->choosen_area_id != null) {
                        $choosen_area_id = $request->choosen_area_id;
                    }
                }
            }
            $min_max_date = self::getMinMaxDate($request->plans);
            $list_order = self::getOrders($min_max_date['min'], $min_max_date['max'], $choosen_area_id);
            $list_eos_plan = [];
            $list_oral_plan = [];
            $list_practice_plan = [];
            foreach ($request->plans as $raw_plan) {
                switch (intval($raw_plan['retest_type_id'])) {
                    case 1:
                        $list_eos_plan[] = self::createEmptyPlan($raw_plan);
                        break;
                    case 2:
                        $list_oral_plan[] = self::createEmptyPlan($raw_plan);
                        break;
                    case 3:
                        $list_practice_plan[] = self::createEmptyPlan($raw_plan);
                        break;
                    default:
                        break;
                }
            }

            $list_order_by_students = [];

            foreach ($list_order as &$order) {
                $list_order_by_students[$order->getStudentUserLogin()][] = &$order;
            }

            foreach ($list_order_by_students as &$list_order_by_a_student) {
                foreach ($list_order_by_a_student as &$order) {
                    switch ($order->getRetestTypeId()) {
                        case 1:
                            if (count($list_eos_plan) > 0) {
                                self::optimizePlanByType($list_eos_plan, $order, $list_order_by_a_student);
                            }
                            break;
                        case 2:
                            if (count($list_oral_plan) > 0) {
                                self::optimizePlanByType($list_oral_plan, $order, $list_order_by_a_student);
                            }
                            break;
                        case 3:
                            if (count($list_practice_plan) > 0) {
                                self::optimizePlanByType($list_practice_plan, $order, $list_order_by_a_student);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            $list_unset_order = self::getUnsetOrders($list_order);
            $response_plan_list = [];
            foreach ($list_eos_plan as $plan) {
                $orders = $plan->getPlanInfo();
                self::lastCheckForDuplicate($orders, $list_unset_order);
                $orders['order_list_count'] = count($orders['list_order']);
                $response_plan_list[] = $orders;
            }
            foreach ($list_oral_plan as $plan) {
                $orders = $plan->getPlanInfo();
                self::lastCheckForDuplicate($orders, $list_unset_order);
                $orders['order_list_count'] = count($orders['list_order']);
                $response_plan_list[] = $orders;
            }
            foreach ($list_practice_plan as $plan) {
                $orders = $plan->getPlanInfo();
                self::lastCheckForDuplicate($orders, $list_unset_order);
                $orders['order_list_count'] = count($orders['list_order']);
                $response_plan_list[] = $orders;
            }
            // self::lastCheckForDuplicate($list_order, $list_unset_order);
            $orders_statistic = self::statisticOrder($list_unset_order);
            return response(
                [
                    'plans' => $response_plan_list,
                    'unset_orders' => $list_unset_order,
                    'orders_statistic' => $orders_statistic,
                ],
                200
            );
        } catch (\Throwable $th) {
            Log::error("CreateRetestPlan: \n" . $th->getMessage() . "\n" . $th->getLine() . "\n End CreateRetestPlan");
            return response(
                [
                    'plans' => [],
                    'orders' => [],
                    'unset_orders' => [],
                    'orders_statistic' => [],
                ],
                200
            );
        }
    }

    /**
     * lấy mốc thời gian bắt đầu tổ chức thi từ kế hoạch
     *
     * @param  mixed $raw_list
     * @return mixed [min,max]
     */
    static private function getMinMaxDate($raw_list)
    {
        $list_date = [];
        foreach ($raw_list as $object) {
            $list_date[] = Carbon::createFromFormat('Y-m-d', $object['date'])->format('Y-m-d');
        }
        usort($list_date, function ($a, $b) {
            $dateTimestamp1 = strtotime($a);
            $dateTimestamp2 = strtotime($b);

            return $dateTimestamp1 < $dateTimestamp2 ? -1 : 1;
        });
        return [
            'min' => $list_date[0],
            'max' => $list_date[count($list_date) - 1]
        ];
    }

    /**
     * khởi tạo danh sách đơn đăng ký theo class Order
     *
     * @param  string yyyy-mm-dd $from_date ngày bắt đầu tổ chức thi
     * @param  string yyyy-mm-dd $to_date ngày kết thúc tổ chức thi
     * @return array StdClass Order
     */
    private function getOrders($from_date = "2021-01-01", $to_date = "2021-01-02")
    {
        $data = (object)[
            'status' => self::CHO_XEP_LOP // chờ xếp lớp
        ];
        $list_raw_order = $this->getListRetestStudent($data, true);
        $list_student_user_login = [];
        foreach ($list_raw_order as $order) {
            if (!in_array($order->student_user_login, $list_student_user_login)) {
                $list_student_user_login[] = $order->student_user_login;
            }
        }
        $listStudentsWithCalendar = $this->listStudentsWithCalendar($from_date, $to_date, $list_student_user_login);
        $list_order = [];
        foreach ($list_raw_order as $order) {
            $studentCalendar = $this->getStudentCalendar($order->student_user_login, $listStudentsWithCalendar);

            $order_obj = new Order($order->id, $order->student_user_login, $order->retest_type_id, $order->subject_code, $studentCalendar, $order->student_user_code, $order->status_name, $order->student_note, $order->created_at, $order->skill_code);
            $list_order[] = $order_obj;
        }
        return $list_order;
    }

    /**
     * lấy dữ liệu lịch học list sinh viên theo khoảng ngày
     *
     * @param  string $date_from
     * @param  string $date_to
     * @param  array $list_student_user_login
     * @return mixed
     */
    static private function listStudentsWithCalendar($date_from, $date_to, $list_student_user_login)
    {
        $from = Carbon::createFromFormat('Y-m-d', $date_from)->toDateString();
        $to = Carbon::createFromFormat('Y-m-d', $date_to)->toDateString();
        $studentCalendar = [];
        foreach (array_chunk($list_student_user_login, 200) as $array)  {
            $list = Activity::join('fu_group', 'fu_group.id', '=', 'fu_activity.groupid')
                ->join('fu_group_member', 'fu_group_member.groupid', '=', 'fu_activity.groupid')
                ->whereIn('fu_group_member.member_login', $array)
                ->where('fu_activity.day', [$from, $to])
                ->get([
                    'fu_activity.slot as slot',
                    'fu_activity.day as date',
                    'fu_group_member.member_login as student_user_login'
                ])->toArray();
            $studentCalendar = array_merge($studentCalendar, $list);
        }
        return $studentCalendar;
    }

    /**
     * get danh sách đơn đăng ký thi lại
     *
     * @param  mixed $data
     * @param bool $export false
     * @return mixed
     */
    static private function getListRetestStudent($data, $export = false)
    {
        try {
            $list_query = OnlineService::query();
            $list_query->leftjoin('fu_service_register', 'fu_service_register.online_service_id', '=', 'fu_online_services.id')
                ->leftjoin('relearn_online_calendar', 'relearn_online_calendar.id', '=', 'fu_service_register.relearn_online_calendar_id')
                ->leftjoin('fu_user', 'fu_user.user_login', '=', 'fu_online_services.student_user_login')
                ->leftjoin('fu_available_services', 'fu_available_services.id', '=', 'fu_online_services.available_service_id')
                ->leftjoin('fu_service_status', 'fu_service_status.id', '=', 'fu_online_services.status')
                ->leftjoin('relearn_onlines', 'relearn_onlines.online_service_id', '=', 'fu_online_services.id')
                ->leftJoin('relearn_online_calendar_users', 'relearn_online_calendar_users.online_service_id', '=', 'fu_online_services.id');

            if ($data->choosen_area_id != null) {
                $list_query->where('fu_service_register.area_id','=', $data->choosen_area_id);
            }
            if (isset($data->retest_plan_id) && $data->retest_plan_id != null && $data->retest_plan_id != "" && $data->retest_plan_id != 'null') {
                $list_query->join('fu_retest_plan', 'relearn_online_calendar.retest_plan_id', '=', 'fu_retest_plan.id');
            } else {
                $list_query->leftJoin('fu_retest_plan', 'relearn_online_calendar.retest_plan_id', '=', 'fu_retest_plan.id');
            }
            $list_query->where('fu_available_services.service_code', '=', 'dang-ky-thi-lai');
            if (isset($data->student_code)) {
                if ($data->student_code != "" && $data->student_code != null && $data->student_code != 'null') {
                    $list_query->where(function ($query) use ($data) {
                        $query->where('fu_online_services.student_user_login', '=', $data->student_code)
                            ->orWhere('fu_service_register.user_code', '=', $data->student_code);
                    });
                }
            }

            if (isset($data->sync_point_status)) {
                if ($data->sync_point_status != "" && $data->sync_point_status != null && $data->sync_point_status != 'null' && $data->sync_point_status != 0) {
                    if ($data->sync_point_status == 1) {
                        $list_query->where('relearn_online_calendar_users.sync_point', '=', 1);
                    } else if ($data->sync_point_status == 2) {
                        $list_query->where('relearn_online_calendar_users.sync_point', '=', 0);
                    }
                }
            }

            if (isset($data->term)) {
                if ($data->term != "" && $data->term != null && $data->term != 'null') {
                    $list_query->where('fu_available_services.term_id', '=', $data->term);
                }
            }

            if (isset($data->retest_available_id)) {
                if ($data->retest_available_id != "" && $data->retest_available_id != null && $data->retest_available_id != 'null') {
                    $list_query->where('fu_online_services.available_service_id', '=', $data->retest_available_id);
                }
            }

            if (isset($data->retest_type_id)) {
                if ($data->retest_type_id != "" && $data->retest_type_id != null && $data->retest_type_id != 'null') {
                    $list_query->where('fu_service_register.relearn_type_id', '=', $data->retest_type_id);
                }
            }

            if (isset($data->subject_list)) {
                if (count($data->subject_list) > 0) {
                    $list_query->whereIn('fu_service_register.subject_code', $data->subject_list);
                }
            }

            if (isset($data->status)) {
                if ($data->status != "" && $data->status != null && $data->status != 'null') {
                    $list_query->where('fu_online_services.status', $data->status);
                }
            }

            if (isset($data->retest_plan_id)) {
                if ($data->retest_plan_id != "" && $data->retest_plan_id != null && $data->retest_plan_id != 'null') {
                    $list_query->where('relearn_online_calendar.retest_plan_id', '=', $data->retest_plan_id);
                }
            }


            $list_query->select([
                'relearn_online_calendar.id as relearn_online_calendar_id',
                'relearn_online_calendar.subject_id as subject_id',
                'relearn_online_calendar_users.point as point',
                'relearn_online_calendar_users.is_skip_exam as is_skip_exam',
                'relearn_onlines.term_name_pass as term_name_pass',
                'relearn_onlines.status as status',
                'fu_user.user_code as student_user_code',
                'fu_online_services.student_user_login as student_user_login',
                DB::raw('CONCAT(fu_user.user_surname," ",fu_user.user_middlename," ",fu_user.user_givenname) as student_name'),
                'fu_user.user_email as student_email',
                'fu_user.user_login as student_user_login',
                'fu_service_register.subject_code as subject_code',
                'fu_service_register.skill_code as skill_code',
                'fu_service_register.relearn_type as retest_type_name',
                'fu_service_register.relearn_type_id as retest_type_id',
                'fu_service_register.old_group_id as old_group_id',
                'fu_service_register.old_grade_id as old_grade_id',
                'fu_available_services.id as available_service_id',
                'fu_available_services.name as available_service_name',
                'fu_service_register.note as student_note',
                'fu_service_status.status_name as status_name',
                'fu_service_status.color_variant as status_color_variant',
                'fu_service_status.step as status_step',
                'fu_service_status.id as status_id',
                'fu_retest_plan.id as retest_plan_id',
                'fu_retest_plan.name as retest_plan_name',
                'fu_online_services.updated_at as last_update_time',
                'fu_online_services.id as id',
                'fu_online_services.created_at as created_at',
                'relearn_online_calendar_users.sync_point as sync_point'
            ]);

            $number_paginate = 8;
            if (isset($data->number_paginate)) {
                if ($data->number_paginate > 0) {
                    $number_paginate = $data->number_paginate;
                } else {
                    $number_paginate = 8;
                }
            } else {
                $number_paginate = 8;
            }

            if (isset($data->id)) {
                if ($data->id != "" && $data->id != null && $data->id != 'null') {
                    $list_query->where('fu_online_services.id', '=', $data->id);
                    $number_paginate = 8;
                    $data->page = 1;
                }
            }

            $list_query->orderBy('fu_online_services.id', 'DESC');
            if ($export == false) {
                $list = $list_query->paginate($number_paginate);
            } else {
                $list = $list_query->get();
            }
            return $list;
        } catch (\Throwable $th) {
            Log::error("------------start err getListRetestStudent ----------");
            Log::error(json_encode($th));
            Log::error("------------end err getListRetestStudent ----------");
            return [];
        }
    }

    /**
     * lấy lịch học theo từng sinh viên
     *
     * @param  string $user_login
     * @param  mixed $raw
     * @return mixed
     */
    static private function getStudentCalendar($user_login, $raw)
    {
        $list = [];
        foreach ($raw as $calendar) {
            if ($calendar['student_user_login'] == $user_login) {
                $list[] = $calendar;
            }
        }
        return $list;
    }

    /**
     * Sắp xếp lớp
     *
     * @param  mixed $request
     * @return mixed $response
     */
    /**
     * tạo Plan trống chưa gắn Order
     *
     * @param  mixed $raw kế hoạch được tạo
     * @return object Plan
     */
    static private function createEmptyPlan($raw)
    {
        switch (intval($raw['retest_type_id'])) {
            case 1:
                return new EOSPlan($raw['id'], $raw['date'], $raw['slot_id'], $raw['skill_code'], $raw['min_number'], $raw['max_number'], $raw['supervisor1'], $raw['supervisor2'], $raw['area_id'], $raw['room_id'], $raw['skill_code'], $raw['organize_type'], isset($raw['url']) ? $raw['url'] : "");
                break;
            case 2:
                return new OralPlan($raw['id'], $raw['date'], $raw['slot_id'], $raw['skill_code'], $raw['min_number'], $raw['max_number'], $raw['supervisor1'], $raw['supervisor2'], $raw['area_id'], $raw['room_id'], $raw['skill_code'], $raw['organize_type'], isset($raw['url']) ? $raw['url'] : "");
                break;
            case 3:
                return new PracticePlan($raw['id'], $raw['date'], $raw['slot_id'], $raw['skill_code'], $raw['min_number'], $raw['max_number'], $raw['supervisor1'], $raw['supervisor2'], $raw['area_id'], $raw['room_id'], $raw['skill_code'], $raw['organize_type'], isset($raw['url']) ? $raw['url'] : "");
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Đánh giá đơn đăng ký nếu nằm trong kế hoạch được tạo
     *
     * @param  mixed $order object Order
     * @param  mixed $plan  object Plan
     * @return int điểm đánh giá
     */
    static private function getPointValuationPlan(&$order, &$plan, &$list_order_by_a_student)
    {
        try {
            $subject_code_point = 0;
            $is_same_subject_code = $order->isSameSubject($plan->getSubjectCodes());
            $is_busy = $order->isBusy($plan->getTimeData());
            if ($is_same_subject_code) {
                $subject_code_point = 1;
                $dup_date = $plan->getDate();
                $dup_slot = $plan->getSlot();
                if($is_busy){
                    $order->setDuplicatedCalendar([
                        'id' => null,
                        'date' => $dup_date,
                        'slot' => $dup_slot
                    ]);
                }
            } else {
                $subject_code_point = -10;
            }
            $numberOrderPoint = $plan->getPointNumberOrder();
            
            $duplicated = 0;
            foreach ($list_order_by_a_student as &$order) {
                if ($order->getAssignPlan() != null) {
                    if ($plan->getDate() == $order->getAssignPlan()['date'] && $plan->getSlot() == $order->getAssignPlan()['slot']) {
                        $duplicated = 130; // hạ điểm đánh giá xuống -100 => đơn trùng do tool xếp lịch
                        break;
                    }
                }
                
            }
            return $subject_code_point - $numberOrderPoint - (100 * intval($is_busy)) - $duplicated;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
        
    }

    /**
     * lựa chọn kế hoạch phù hợp nhất dựa theo điểm đánh giá
     *
     * @param  mixed $order StdClass Order
     * @param  array $list_plan danh sách Plan
     * @return int index của plan phù hợp
     */
    static private function findMostSuitablePlan(&$order, &$list_plan, &$list_order_by_a_student)
    {
        $list_point_plan = array();
        foreach ($list_plan as $plan) {
            $point = self::getPointValuationPlan($order, $plan, $list_order_by_a_student);
            $list_point_plan[] = $point;
        }
        $max_index = array_keys($list_point_plan, max($list_point_plan));
        
        
        if ($list_point_plan[$max_index[0]] <= -5 && $list_point_plan[$max_index[0]] > -90) {
            return -1;
        } else if($list_point_plan[$max_index[0]] < -90){ // đơn bị trùng lịch trong tool
            return -100 - $max_index[0];
        }
        else {
            return $max_index[0];
        }
    }

    /**
     * Hàm xếp order vào kế hoạch thi theo đánh giá
     *
     * @param  mixed $plan_list
     * @param  mixed $order
     * @return void
     */
    static private function optimizePlanByType(&$plan_list, &$order, &$list_order_by_a_student)
    {
        $index  = self::findMostSuitablePlan($order, $plan_list, $list_order_by_a_student);
        if ($index === -1) {
        } else if($index <= -100)
        {
            // $order->setDuplicatedCalendar($plan_list[0 - ($index + 100)]->getTimeData());
        }
        else {
            $plan_list[$index]->setOrderIntoPlan($order);
        }
    }

        
    /**
     * lấy dữ liệu đơn chưa được xếp lớp
     *
     * @param  mixed $list_order
     * @return mixed
     */
    static private function getUnsetOrders($list_order)
    {
        $list_unset_order = [];
        foreach ($list_order as &$order) {
            if ($order->getAssignPlan() == null) {
                if (!count(array_filter($list_unset_order, function ($item) use ($order) {
                    return $item['id'] == $order->getData()['id'];
                }))) {
                    $list_unset_order[] = $order->getData();
                }
            }
        }
        return $list_unset_order;
    }
    
    /**
     * Đếm skill code theo loại thi và skill code
     *
     * @param  mixed $order
     * @param  mixed $subject_code
     * @return void
     */
    static private function checkSubjectCodeOrder(&$order, $subject_code)
    {
        if (isset($order->{$subject_code}) != null && isset($order->{$subject_code}) == true) {
            $order->{$subject_code}++;
        } else {
            $order->{$subject_code} = 1;
        }
    }

    /**
     * Tạo thống kê order đợi xếp lớp
     *
     * @param  mixed $list_order
     * @return mixed
     */
    static private function statisticOrder($list_order)
    {
        $eos_orders = (object)[
            'Total' => 0,
        ];
        $oral_orders = (object)[
            'Total' => 0,
        ];
        $practice_orders = (object)[
            'Total' => 0,
        ];
        foreach ($list_order as $order) {
            switch ($order['retest_type_id']) {
                case 1:
                    $eos_orders->Total++;
                    self::checkSubjectCodeOrder($eos_orders, $order['retest_subject']);
                    break;
                case 2:
                    $oral_orders->Total++;
                    self::checkSubjectCodeOrder($oral_orders, $order['retest_subject']);
                    break;
                case 3:
                    $practice_orders->Total++;
                    self::checkSubjectCodeOrder($practice_orders, $order['retest_subject']);
                    break;
                default:
                    break;
            }
        }
        return [
            1 => $eos_orders,
            2 => $oral_orders,
            3 => $practice_orders
        ];
    }
    
    private function lastCheckForDuplicate(&$orders, &$list_unset_order)
    {
        try {
            $user_order_list = [];
            foreach ($orders['list_order'] as $key => $data_order) {
                if(isset($data_order['assigned_plan']) && $data_order['assigned_plan'] != null){
                    $student_activity = GroupMember::where('fu_group_member.member_login', $data_order['student_user_login'])
                    ->leftJoin('fu_activity', 'fu_activity.groupid', '=', 'fu_group_member.groupid')
                    ->whereRaw('fu_activity.day >= CURRENT_DATE')
                    ->where('fu_activity.day',  $data_order['assigned_plan']['date'])
                    ->where('fu_activity.slot',  $data_order['assigned_plan']['slot'])
                    ->select('fu_activity.id as activity_id')->get()->toArray();
                    if (count($student_activity) > 0){
                        array_push($list_unset_order, $data_order);
                        $orders['list_order'][$key] = null;
                    } else {
                        if( isset($user_order_list[$data_order['student_user_login']]) 
                        && count($user_order_list[$data_order['student_user_login']]) > 0 ){
                            foreach ($user_order_list[$data_order['student_user_login']] as $key => $order_item){
                                if($order_item['assigned_plan']['date'] == $data_order['assigned_plan']['date'] 
                                && $order_item['assigned_plan']['slot'] == $data_order['assigned_plan']['slot']){
                                    array_push($list_unset_order, $data_order);
                                    $orders['list_order'][$key] = null;
                                }else{
                                    $user_order_list[$data_order['student_user_login']][] = $data_order;   
                                };
                            }
                        }else{
                            $user_order_list[$data_order['student_user_login']] = [];
                            $user_order_list[$data_order['student_user_login']][] = $data_order;   
                        }
                    }
                }
            }
            $orders['list_order'] = array_filter($orders['list_order'], function($order){
                return $order !== null;
            });
        } catch (\Throwable $th) {
            Log::error("lastCheckForDuplicate: \n" . $th->getMessage() . "\n" . $th->getLine() . "\n End CreateRetestPlan");
        }

    }
}
