<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
       protected $fillable = [
        'side_code',
        'is_side_code_enabled',
        'merchant_ref',
        'is_merchant_enabled',
        'company_id'
    ];
    
    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
