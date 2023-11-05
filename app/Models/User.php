<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use App\Models\Sms\Account;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    const ROLE_ADMIN = 43;
    const LEGAL_ENTITY_UNIT = 0;
    const LEGAL_ENTITY_UNIVERSITY = 1;
    const LEGAL_ENTITY = [
        0 => 'Khối',
        1 => 'Trường'
    ];

    protected $table = 'fu_user';
    public $timestamps = false;
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'feid',
        'user_surname',
        'user_middlename',
        'user_givenname',
        'user_login',
        'user_code',
        'old_user_code',
        'user_DOB',
        'user_email',
        'user_address',
        'user_telephone',
        'user_level',
        'study_status',
        'study_status_code',
        'google2fa_secret',
        'dantoc',
        'kithu',
        'curriculum_id',
        'cmt',
        'ngaycap',
        'noicap',
        'created_date',
        'brand_name',
        'gender',
        'grade',
        'Legal_entity',
        'learn_start_day',
        'current_period',
        'people_id',
        'total_debt_subject',
        'last_term_id',
        'campus_id',
        'grade_create',
        'alternative_email',
        'feid'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->connection = session('campus_db');
        parent::__construct($attributes);
    }

    public function fullname()
    {
        $first = $this->user_surname;
        $middle = $this->user_middlename;
        $last = $this->user_givenname;
        $full_name = '';
        if ($first != '') {
            $full_name .= $first;
        }
        if ($middle != '') {
            $full_name .= " $middle";
        }
        if ($last != '') {
            $full_name .= " $last";
        }
        return $full_name;
    }

}
