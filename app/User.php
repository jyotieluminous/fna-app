<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'id_number', 'cell_number', 'street', 'city_town', 'code'
    ];

    public function company()
    {
        return $this->belongsTo('App\Company');
    }

    public function services()
    {
        return $this->hasMany('App\Service');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', 'permissions');
    }

    public function authenticatedUserId()
    {
        return Auth::id();
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice');
    }

    public function getRole()
    {
        return $this->roles()->first();
    }
    
    public function getCompanyAdmins()
    {
        $company_users = $this->company()->first()->users;

        $company_admins = $company_users->filter(function($user) {
            return $user->getRole()->name == 'Company Admin';
        });


        return $company_admins;
    }

    public static function boot()
    {
        parent::boot();

        // static::deleting(function(User $user) {

        //     if($user->getRole()->name == 'Super Admin' && Auth::user()->getRole()->name != 'Super Admin')
        //     {
        //         abort(403, 'You are not authorized for this action');
        //     }
        // });

        static::updating(function(User $user) {

            if(Auth::check())
            {
                if($user->getRole()->name == 'Super Admin' && Auth::user()->getRole()->name != 'Super Admin')
                {
                    abort(403, 'You are not authorized for this action');
                }
            }
        });
    }


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
