<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proof extends Model
{
     protected $fillable = [
        'path'
    ];

    public function Payment()
    {
        return $this->belongsTo('App\Payment');
    }
}
