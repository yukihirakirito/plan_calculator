<?php

namespace App\Repositories;

use App\Repositories\Admin\RetestHelperClasses\Order;

class Plan
{
    protected $id = 0;
    protected $date;
    protected $slot;
    protected $retest_subjects;
    protected $list_order = [];
    protected $list_order_id = [];
    protected $min = 1;
    protected $max;
    protected $supervisor1 = "";
    protected $supervisor2 = "";
    protected $area_id = "";
    protected $rom_type = 0;
    protected $room_id = "";
    protected $retest_skill_codes;
    protected $organize_type = null;
    protected $url = "";

    public function __construct($id, $date, $slot, $retest_subjects, $min, $max, $supervisor1, $supervisor2, $area_id, $room_id, $retest_skill_codes, $organize_type, $url = "")
    {
        $this->id = $id;
        $this->date = $date;
        $this->slot = $slot;
        $this->retest_subjects = $retest_subjects;
        $this->min = $min;
        $this->max = $max;
        $this->supervisor1 = $supervisor1;
        $this->supervisor2 = $supervisor2;
        $this->area_id = $area_id;
        $this->room_id = $room_id;
        $this->retest_skill_codes = $retest_skill_codes;
        $this->organize_type = $organize_type;
        $this->url = $url;
    }

    public function getTimeData()
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'slot' => $this->slot
        ];
    }

    public function getSubjectCodes()
    {
        return $this->retest_subjects;
    }

    public function getSameSubjectPoint()
    {
        $totalPoint = 0;
        foreach ($this->list_order as $order) {
            if ($order->isSameSubject($this->retest_subjects)) {
                $totalPoint++;
            }
        }
        return $totalPoint;
    }

    public function setOrderIntoPlan(Order &$order)
    {
        $this->list_order[] = $order;
        $this->list_order_id[] = $order->getId();
        $order->setAssignPlan($this);
    }

    public function getOrderInPlan()
    {
        return $this->list_order;
    }

    public function getListAssignedOrderId()
    {
        return $this->list_order_id;
    }

    public function removeOrderFromPlan(Order &$order)
    {
        foreach ($this->list_order as $key => $_order) {
            if ($order->getId() == $_order->getId()) {
                unset($this->list_order[$key]);
                break;
            }
        }
        foreach ($this->list_order_id as $key => $_order_id) {
            if ($order->getId() == $_order_id) {
                unset($this->list_order_id[$key]);
                break;
            }
        }

        $order->removeAssignedPlan($this);
    }

    public function getPointNumberOrder()
    {
        if ((count($this->list_order) - $this->max) >= 2) {
            return 6;
        }
        $result = count($this->list_order) / $this->max;
        if ($result >= 1) {
            return 1 + $result;
        } else {
            return $result;
        }
    }

    public function isFull()
    {
        return count($this->list_order) >= $this->max;
    }

    public function getSlot()
    {
        return $this->slot;
    }
    public function getDate()
    {
        return $this->date;
    }
}
