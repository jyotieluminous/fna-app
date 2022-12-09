<?php

namespace App\Http\View\Composers;

use App\Role;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

Class RoleComposer{


    public function compose(View $view)
    {

        if(Auth::user()->getRole()->name == 'Super Admin')
        {
            $roles = Role::all();
        }else{
            $roles = Role::where('name', '!=', 'Super Admin')->get();
        }
        
        $view->with('roles', $roles);
    }
}