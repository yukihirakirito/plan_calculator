<?php
namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AvailableService;
use App\Models\Room;
use App\Models\OnlineService;
use App\Models\Slot;
use App\Models\Subject;
use App\Repositories\Admin\RetestHelperClasses\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use stdClass;

class RetestController extends PlanCalculatorController
{
    const DAT = 1;
    const KHONG_DAT = 0;
    const TRUOT_DIEM_DANH = -1;
    const CO_DI_THI = 1;
    const CO_DI_THI_2 = 2;
    const KHONG_DI_THI = 0;

    const CHO_XEP_LOP = 80;

    const SUBJECT_ONLINE = [
        'VIE1015',
        'VIE1016',
        'VIE1025',
        'VIE1026',
        'ENT1125',
        'ENT1225',
        'ENT2125',
        'COM107',
        'BUS1024',
        'WEB3023',
        'ENT2225',
        'HOS105',
        'HOS3031',
        'PSY1011',
        'TOU2031',
        'SOF2041',
        'SOF205',
        'MAR2051',
        'MOB2041',
        'MUL219',
        'SOF307',
        'WEB2041',
        'NET306',
        'SOF306',
        'NET106',
        // 'WEB204'
        'NET106',
        'DOM1081',
        'MAR2071',
        'MUL3191'
    ];

    public function getModel()
    {
        return OnlineService::class;
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
            ]);
        } catch (\Throwable $th) {
            Log::error("----------------getPlanCreatorInformation-------------- \n " . $th->getLine() . " - " . $th->getMessage());
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
}
