<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableService extends Model
{
    protected $table = 'fu_available_services';
    protected $fillable = ['id','service_id','name','datetime_from','datetime_to','display', 'fee','note'];
    public $timestamps = true;

}
