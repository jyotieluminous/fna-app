<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'account_type',
        'company_name',
        'company_city',
        'company_street',
        'company_code',
        'vat_number',
        'finance_email',
        'operation_email',
        'company_telephone',
        'company_cell',
        'bank',
        'bank_holder',
        'account_number',
        'branch_code',
        'user_id'
    ];

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function clients()
    {
        return $this->hasMany('App\Client');
    }

    public function bank_document()
    {
        return $this->hasOne('App\BankDocument');
    }

    public function payments()
    {
        return $this->hasMany('App\Payment');
    }

    public function paymentGatewayConfig()
    {
        return $this->hasOne('App\PaymentGatewayConfig');
    }

    public function emailCampaignConfiguration()
    {
        return $this->hasOne('App\EmailCampaignConfiguration');
    }

    public function smsCampaignConfiguration()
    {
        return $this->hasOne('App\SMSCampaignConfiguration');
    }
    
    public function coupon()
    {
        return $this->hasOne('App\Coupon');
    }
    
    public function getFirstAdmin()
    {
        $users = $this->users;

        $companyAdmins = $users->filter(function($user) {
            return $user->getRole()->name == 'Company Admin';
        });

        return $companyAdmins->first();
    }
}
