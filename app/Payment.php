<?php

namespace App;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'client_id',
        'payment_type',
        'invoice_id',
        'status',
        'amount_paid'
    ];


    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function client()
    {
        return $this->belongsTo('App\Client');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Invoice');
    }
    
    public function getInvoiceId($invoiceId)
    {
        $invoiceLength = strlen($invoiceId);

        $calculate = 6 - $invoiceLength;

        $myzeros = "";

        for($i = 0; $i < $calculate; $i++)
        {
           $myzeros .= "0";
        }

        return "Inv".$myzeros.$invoiceId;
    }

     public function proof()
    {
        return $this->hasOne('App\Proof');
    }

}
