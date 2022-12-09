<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailCampaignConfiguration extends Model
{
    public $table = 'mail_configurations';

    protected $fillable = [
        'is_1st_reminder_enabled',
        'first_rem_template',
        'first_rem_days',
        'is_2nd_reminder_enabled',
        'second_rem_template',
        'second_reminder_days',
        'is_handover_enabled',
        'handover_template',
        'handover_days',
        'is_letter_of_demand_enabled',
        'letter_of_demand_template',
        'letter_of_demand_days',
        'is_final_notice_enabled',
        'final_notice_template',
        'final_notice_days',
        'company_id'
    ];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
