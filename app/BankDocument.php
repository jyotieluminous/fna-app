<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankDocument extends Model
{
    protected $fillable = [
        'path'
    ];

    public function Company()
    {
        return $this->belongsTo('App\Company');
    }
}
