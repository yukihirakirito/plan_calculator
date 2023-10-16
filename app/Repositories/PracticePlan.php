<?php

namespace App\Repositories;

class PracticePlan extends Plan
{
    public function getPlanInfo()
    {
        $list_order = [];
        foreach ($this->list_order as $order) {
            $list_order[] = $order->getData();
        }
        return [
            'id' => $this->id,
            'date' => $this->date,
            'slot' => $this->slot,
            'retest_subjects' => $this->retest_subjects,
            'min' => $this->min,
            'max' => $this->max,
            'list_order' => $list_order,
            'retest_type_id' => 3,
            'retest_type' => 'Practice test',
            'supervisor1' => $this->supervisor1,
            'supervisor2' => $this->supervisor2,
            'area_id' => $this->area_id,
            'room_id' => $this->room_id,
            'skill_code' => $this->retest_skill_codes,
            'organize_type' => $this->organize_type,
            'url' => $this->url
        ];
    }
}
