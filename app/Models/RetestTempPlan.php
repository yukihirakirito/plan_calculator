<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetestTempPlan extends Model
{
    protected $table = 'fu_retest_temp_plan';
    protected $fillable = ['id','data'];
    public $timestamps = false;
}
