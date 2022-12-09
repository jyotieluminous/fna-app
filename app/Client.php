<?php

namespace App;

use App\Client;
use App\Invoice;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email_address',
        'id_number',
        'title',
        'contact_number',
        'password',
        'street',
        'city_town',
        'code',
        'is_active'
    ];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function payments()
    {
        return $this->hasMany('App\Payment');
    }
    
    public static function boot()
    {
        parent::boot();

        static::deleting(function(Client $client) {
            $invoices = Invoice::where('client_id', '=', $client->id);
            $invoices->delete();
        });
    }
}
