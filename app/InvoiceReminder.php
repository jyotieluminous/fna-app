<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    protected $fillable = [
       'due_date',
        'payment_status',
        'invoice_id',
        'reminder_status',
        'is_1st_mailreminder_enabled',
        '1st_mailreminder_template',
        '1st_mailreminder_days',
        'is_2nd_mailreminder_enabled',
        '2nd_mailreminder_template',
        '2nd_mailreminder_days',
        'is_mailhandover_enabled',
        'mail_handover_template',
        'mail_handover_days',
        'is_1st_smsreminder_enabled',
        '1st_smsreminder_template',
        '1st_smsreminder_days',
        'is_2nd_smsreminder_enabled',
        '2nd_smsreminder_template',
        '2nd_smsreminder_days',
        'is_sms_handover_enabled',
        'sms_handover_template',
        'sms_handover_days',
        'is_letter_of_demand_enabled',
        'letter_of_demand_template',
        'letter_of_demand_days',
        'is_letter_of_demand_sent',
        'is_final_notice_enabled',
        'final_notice_template',
        'final_notice_days',
        'is_final_notice_sent',
        'is_first_template_sent',
        'is_second_template_sent',
        'is_handover_template_sent',
        'is_first_SMS_template_sent',
        'is_second_SMS_template_sent',
        'is_handover_SMS_template_sent',
        'is_SMS_letter_of_demand_enabled',
        'SMS_letter_of_demand_template',
        'SMS_letter_of_demand_days',
        'is_SMS_letter_of_demand_sent',
        'is_SMS_final_notice_enabled',
        'SMS_final_notice_template',
        'SMS_final_notice_days',
        'is_SMS_final_notice_sent'

    ];

    public function invoice() {
        return $this->belongsTo('App\Invoice');
    }
}
