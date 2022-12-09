<?php

namespace App;

use DateTime;
use App\Payment;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'notes',
        'total_amount',
        'due_date',
        'companyId'
    ];


    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }

    public function services()
    {
        return $this->belongsToMany('App\Service')->withPivot('item_quantity');
    }


    public function payment()
    {
        return $this->hasOne('App\Payment');
    }

    public function calculateBalanceDue()
    {
        $balance_due = $this->total_amount * 0.15;

        return number_format($balance_due + $this->total_amount, 2);
    }

    public function getSubTotal()
    {
        return number_format($this->total_amount,2);
    }
    
    public function vatAmount()
    {
        return number_format(($this->total_amount * 0.15),2);
    }


     public function getInvoiceId()
    {
        $invoiceLength = strlen($this->id);

        $calculate = 6 - $invoiceLength;

        $myzeros = "";

        for($i = 0; $i < $calculate; $i++)
        {
           $myzeros .= "0";
        }

        return "Inv".$myzeros.$this->id;
    }
    
     public function getDueByDays()
    {

        $current_day = new DateTime(date('Y-m-d H:i:s'));

        $due = new DateTime($this->invoice_reminder->due_date);

        $interval = $current_day->diff($due);
          
           
        return $interval->invert != 0 ? $interval->format('%a') : 0;

    }

    public function isDueDatePassed()
    {
        $current_day = new DateTime(date('Y-m-d H:i:s'));

        $due = new DateTime($this->invoice_reminder->due_date);

        $interval = $current_day->diff($due);
        
        return $interval->invert == 0 ? false : true;
    }

    public function getPaymentStatus()
    {
        $payment = Payment::where('invoice_id', $this->id)->first();

        if($payment)
        {
            return $status = $payment->status;
        }
        
        return 'Not Paid';

    }

    public function invoice_reminder()
    {
        return $this->hasOne('App\InvoiceReminder');
    }
}
