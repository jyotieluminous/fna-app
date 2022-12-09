<?php

namespace App\Http\View\Composers;

use App\User;
use App\Service;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

Class ServiceComposer{


    public function compose(View $view)
    {
        if(Auth::user()->getRole()->name == 'Super Admin')
        {
          
            $services = Service::paginate(8);
        }else{
            $data = User::where('company_id', Auth::user()->company_id)->with('services')->get()->pluck('services')->flatten();
            $services = $this->paginate($data);
        }
        


        $view->with('services', $services);
    }
    
     public function paginate($items, $perPage = 8, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, ['path' => \Request::url(), 'query' => ['page' => $page]]);
    }
}