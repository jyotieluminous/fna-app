<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AccountSuspend
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    { 
     
        if(!empty(auth()->user()->company))
        {
            if(auth()->check() && (auth()->user()->company->is_active == 0))
            {
                Auth::logout();
    
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')->with('error', 'Your account has been currently suspended, please contact ecolls administrator.');
            }  
        }
        
        return $next($request);
    }
}
