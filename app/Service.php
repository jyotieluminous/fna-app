<?php

namespace App;

use App\Service;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'price',
        'description',
        'user_id'
    ];

    public function invoices()
    {
        return $this->belongsToMany('App\Invoice');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
    
     public function company_name()
    {
        return $this->user->company->company_name;
    }
    
    public static function boot()
    {
        parent::boot();

        static::deleting(function(Service $service) {
            $invoices = $service->invoices()->delete();
            $service->invoices()->detach();
        });


    }
}
