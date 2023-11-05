<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Area;
use App\Models\AvailableService;
use App\Models\Fu\RetestPlan;
use App\Models\RetestTempPlan;
use App\Models\Room;
use App\Models\OnlineService;
use App\Models\Slot;
use App\Models\Subject;
use App\Models\User;
use App\Repositories\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class RetestController
{
    const DAT = 1;
    const KHONG_DAT = 0;
    const TRUOT_DIEM_DANH = -1;
    const CO_DI_THI = 1;
    const CO_DI_THI_2 = 2;
    const KHONG_DI_THI = 0;

    const CHO_XEP_LOP = 80;

    const SUBJECT_ONLINE = ['VIE1015','VIE1016','VIE1025','VIE1026','ENT1125','ENT1225','ENT2125','COM107','BUS1024','WEB3023','ENT2225','HOS105','HOS3031','PSY1011','TOU2031','SOF2041','SOF205','MAR2051','MOB2041','MUL219','SOF307','WEB2041','NET306','SOF306','NET106','NET106','DOM1081','MAR2071','MUL3191'];
    public function getModel()
    {
        return OnlineService::class;
    }

    /**
     * API xếp lớp theo lịch được tạo
     *
     * @param  mixed $request
     * @return mixed $response
     */

    public function postCreateRetestPlan(Request $request)
    {
        try {
            return PlanCalculatorController::CreateRetestPlan($request);
        } catch (\Throwable $th) {
            return response("ERR", 500);
        }
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
                ->leftJoin('relearn_online_calendar_users', 'relearn_online_calendar_users.online_service_id', '=', 'fu_online_services.id')
                ->leftJoin('fu_retest_plan', 'relearn_online_calendar.retest_plan_id', '=', 'fu_retest_plan.id')
                ->leftJoin('fu_slot', 'fu_retest_plan.slot', '=', 'fu_slot.id')
                ->leftJoin('fu_area', 'fu_retest_plan.area_id', '=', 'fu_area.id');
            $list_query->where('fu_online_services.status', $data->status);
            $list_query->select([
                'relearn_online_calendar.id as relearn_online_calendar_id',
                'relearn_online_calendar.subject_id as subject_id',
                'relearn_online_calendar_users.point as point',
                'relearn_online_calendar_users.is_skip_exam as is_skip_exam',
                'relearn_onlines.term_name_pass as term_name_pass',
                'relearn_onlines.status as status',
                'fu_user.user_code as student_user_code',
                DB::raw('CONCAT(fu_user.user_surname," ",fu_user.user_middlename," ",fu_user.user_givenname) as student_name'),
                'fu_user.user_email as student_email',
                'fu_user.user_login as student_user_login',
                'fu_service_register.subject_code as subject_code',
                'fu_service_register.subject_name as subject_name',
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
                'fu_retest_plan.room as retest_plan_room',
                'fu_retest_plan.slot as retest_plan_slot',
                'fu_retest_plan.date as retest_plan_date',
                'fu_area.area_name as area_name',
                'fu_slot.slot_start as slot_start',
                'fu_slot.slot_end as slot_end',
                'fu_online_services.updated_at as last_update_time',
                'fu_online_services.id as id',
                'fu_online_services.created_at as created_at',
                // 'relearn_online_calendar_users.sync_point as sync_point',
                // 'relearn_online_calendar_users.is_skip_exam as is_skip_exam',
                // 'relearn_online_calendar_users.note as relearn_online_calendar_user_note',
                // 'registed_area.area_name as registed_area_name'
            ]);
            $list = $list_query->get();
            return $list;
        } catch (\Throwable $th) {
            Log::error("------------start err getListRetestStudent ----------");
            Log::error(json_encode($th));
            Log::error("------------end err getListRetestStudent ----------");
            return [];
        }
    }

    public function searchUserLogin(Request $request)
    {
        $list_user_login = [];
        try {
            $list_user_login = User::where('user_login', 'LIKE', '%' . $request->key . '%')
                ->whereNotIn('user_level', [3])
                ->orderBy('id', 'desc')
                ->take(10)
                ->pluck('user_login');
            return response($list_user_login, 200);
        }
        catch (\Throwable $th) {
            Log::error("----------- start err searchUserLogin --------------");
            Log::error($th);
            Log::error("----------- end err searchUserLogin -----------------");
            return response([], 200);
        }
    }

    public function getPlanCreatorInformation()
    {
        try {
            $slot_list = Slot::all();
            $date = Carbon::now()->format('Y-m-d');
            $time = Carbon::now()->toTimeString();
            $available_service_id_list = AvailableService::where(function ($query) use ($date, $time) {
                    $query->where(function ($query) use ($date, $time) {
                        $query->where(function ($query) use ($date, $time) {
                            $query->whereDate('datetime_from', '<', $date)
                                ->orWhere(function ($query) use ($date, $time) {
                                    $query->whereDate('datetime_from', '=', $date)
                                        ->whereTime('datetime_from', '<=', $time);
                                });
                            })
                            ->where(function ($query) use ($date, $time) {
                                $query->whereDate('datetime_to', '>', $date)
                                    ->orWhere(function ($query) use ($date, $time) {
                                        $query->whereDate('datetime_to', '=', $date)
                                            ->whereTime('datetime_to', '>=', $time);
                                    });
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('datetime_from', '=', null)
                            ->where('datetime_to', '=', null);
                    })
                    ->orWhere('display','=',1);
            })->where('service_code', '=', 'dang-ky-thi-lai')
                ->join('fu_term', 'fu_term.id', '=', 'fu_available_services.term_id')
                ->orderBy('fu_available_services.id', 'DESC')->get([
                    'fu_available_services.id as available_services_id',
                    'fu_available_services.display as available_services_display',
                    'fu_available_services.name as available_services_name',
                    'fu_available_services.datetime_from as available_services_datetime_from',
                    'fu_available_services.datetime_to as available_services_datetime_to',
                    'fu_term.term_name as term_name'
                ]);

            $area_list = Area::all();
            $room_list = Room::where('is_deleted', '=', 0)->get();
            $online_subjects = Subject::getListSubjectOnline(true);
            $list_order_object = $this->getOrders();
            $list_order = [];
            foreach ($list_order_object as $order) {
                $list_order[] = $order->getData();
            }
            $orders_statistic = $this->statisticOrder($list_order);
            return response([
                'slot_list' => $slot_list,
                'available_service_id_list' => $available_service_id_list,
                'room_list' => $room_list,
                'area_list' => $area_list,
                'subject_list' => $online_subjects,
                'orders_statistic' => $orders_statistic
            ], 200);
        } catch (\Throwable $th) {
            Log::error("----------------getPlanCreatorInformation-------------- \n " . $th->getLine() . " - " . $th->getMessage());
        }
    }

    /**
     * storeTempPlanData Tạm thời lưu dữ liệu kế hoạch trong quá trình lập kế hoạch thi
     *
     * @param  mixed $request
     * @return mixed response 0 là thất bại, 1 là thành công
     */
    public function storeTempPlanData(Request $request)
    {
        try {
            RetestTempPlan::truncate();
            RetestTempPlan::updateOrCreate([
                'id' => 1,
                'data' => $request->data
            ]);
            return response(1, 200);
        } catch (\Throwable $th) {
            return response(0, 200);
        }
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
        $list_raw_order = self::getListRetestStudent($data, true);
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
     * Tạo thống kê order đợi xếp lớp
     *
     * @param  mixed $list_order
     * @return mixed
     */
    private function statisticOrder($list_order)
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
                    $this->checkSubjectCodeOrder($eos_orders, $order['retest_subject']);
                    break;
                case 2:
                    $oral_orders->Total++;
                    $this->checkSubjectCodeOrder($oral_orders, $order['retest_subject']);
                    break;
                case 3:
                    $practice_orders->Total++;
                    $this->checkSubjectCodeOrder($practice_orders, $order['retest_subject']);
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
    /**
     * lấy toàn bộ kế hoạch thi
     *
     * @param  mixed $request
     * @return void
     */
    public function getAllPlan(Request $request)
    {
        try {
            $slot_list = $slot_list = Slot::all();
            $available_service_retest = AvailableService::where('service_code', '=', 'dang-ky-thi-lai')->orderBy('id', 'desc')->get();
            $online_subjects = Subject::getListSubjectOnline(true);;
            $subject_list = Subject::whereIn('subject_code', $online_subjects)->groupBy('skill_code')->get();
            $plan_list_query = RetestPlan::query();
            $plan_list_query->whereIn('fu_retest_plan.status', [config('status')->retest_plan_status_id['ready'], config('status')->retest_plan_status_id['tested'], config('status')->retest_plan_status_id['sync_point']]);
            $plan_list_query->with('calendars');
            $plan_list_query->with('relearn_online_calendar_users');
            $plan_list_query->Join('fu_available_services', 'fu_available_services.id', '=', 'fu_retest_plan.available_service_id')
            ->leftJoin('fu_room', 'fu_room.id', '=', 'fu_retest_plan.room_id');
            $plan_list_query->select([
                'fu_available_services.name as available_service_name',
                'fu_retest_plan.id',
                'fu_retest_plan.status',
                'fu_retest_plan.date',
                'fu_retest_plan.slot',
                'fu_retest_plan.area_id',
                'fu_retest_plan.test_type as test_type_id',
                'fu_retest_plan.room as room_value',
                'fu_retest_plan.supervisor1',
                'fu_retest_plan.supervisor2',
                'fu_retest_plan.min_number',
                'fu_retest_plan.max_number',
                'fu_retest_plan.name',
                'fu_retest_plan.room_id',
                'fu_retest_plan.url',
                'fu_room.room_name',
            ])->groupBy('fu_retest_plan.id');
            $plan_list_query->leftJoin('relearn_online_calendar', 'relearn_online_calendar.retest_plan_id', '=', 'fu_retest_plan.id')
                ->orderBy('fu_retest_plan.id', 'desc');
            if (isset($request->avalable_service_id)) {
                if ($request->avalable_service_id > 0) {
                    $plan_list_query->where('fu_retest_plan.available_service_id', '=', $request->avalable_service_id);
                }
            }

            if (isset($request->test_type_id)) {
                if ($request->test_type_id > 0) {
                    $plan_list_query->where('fu_retest_plan.test_type', '=', $request->test_type_id);
                }
            }
            if (isset($request->id)) {
                if ($request->id > 0) {
                    $plan_list_query->where('fu_retest_plan.id', '=', $request->id);
                }
            }
            if (isset($request->skill_code)) {
                if (sizeof($request->skill_code) > 0) {
                    $plan_list_query->whereHas('calendars', function (Builder $query) use ($request) {
                        $query->whereIn('relearn_online_calendar.skill_code', $request->skill_code);
                    });
                }
            }

            if (isset($request->date_from)) {
                if ($request->date_from != 0 && $request->date_from != null && $request->date_from != "") {
                    $plan_list_query->whereDate('fu_retest_plan.date', '>=', $request->date_from);
                }
            }

            if (isset($request->date_to)) {
                if ($request->date_to != 0 && $request->date_to != null && $request->date_to != "") {
                    $plan_list_query->whereDate('fu_retest_plan.date', '<=', $request->date_to);
                }
            }

            if (isset($request->slot)) {
                if ($request->slot > 0) {
                    $plan_list_query->where('fu_retest_plan.slot', '=', $request->slot);
                }
            }

            $number_paginate = 8;
            if (isset($request->number_paginate)) {
                $number_paginate = $request->number_paginate;
            }
            $area_room = Area::with('rooms')->get();

            $plan_list = $plan_list_query->paginate($number_paginate);
            return response([
                'plan_list' => $plan_list,
                'slot_list' => $slot_list,
                'subject_list' => $subject_list,
                'available_service_list' => $available_service_retest,
                'area_room' => $area_room

            ], 200);
        } catch (\Throwable $th) {
            Log::error(`-----------------getAllPlan----------------error: $th->getLine() - $th->getMessage()`);
            return response([], 500);
        }
    }    
    public function getTempPlanData(Request $request)
    {
        try {
            $data = RetestTempPlan::firstOrFail()->data;
            return response([
                'data' => $data
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'data' => 0
            ], 200);
        }
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
}
