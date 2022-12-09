<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $filable = [
        'is_dischem_enabled',
        'is_checkers_enabled',
        'company_id'
    ];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
