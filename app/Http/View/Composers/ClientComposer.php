<?php

namespace App\Http\View\Composers;

use App\Client;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;


class ClientComposer {

    public function compose(View $view)
    {
         if(Auth::user()->getRole()->name == 'Super Admin')
        {
            $clients = Client::paginate(8);
            
        }else{
            $company = Auth::user()->company;

            $clients = $company->clients()->paginate(8);
        }


        $view->with('clients', $clients);
    }
}