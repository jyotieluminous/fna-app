<?php

namespace App\Http\View\Composers;

use App\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

Class UserComposer{


    public function compose(View $view)
    {

        if(Auth::user()->getRole()->name == 'Super Admin')
        {
            //$users = User::paginate(8);
            $users = User::paginate(8);
        }else {
            // $users = User::where('company_id', Auth::user()->company_id)->where('id', '!=', Auth::id())->get();
            $users = User::where('company_id', Auth::user()->company_id)->paginate(8);

        }

        $view->with('users', $users);
    }
}