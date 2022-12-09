<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $section = 'Logged'; 
        $action = 'Logged In';
        $userId = Auth::user()->id;
        $getRoles = DB::select("SELECT roles.name, users.name as fullName FROM users inner join permissions on permissions.user_id = users.id inner join roles on permissions.role_id = roles.id where users.id =  $userId");
        $getRolesName = $getRoles[0]->name;
        $getFullname = $getRoles[0]->fullName;
        DB::table('audit')->insert([
                'id' => null,
                'userId' => $userId,
                'name' => $getFullname,
                'role' => $getRolesName,
                'action' => $action,
                'section' => $section,
                'app' => 'Dashboard',
                'date' => DB::raw('now()')
            ]);

        return view('dashboard');
    }
    
    public function myLogout()
    {
        $section = 'Logged'; 
        $action = 'Logged out';
        $userId = Auth::user()->id;
        $getRoles = DB::select("SELECT roles.name, users.name as fullName FROM users inner join permissions on permissions.user_id = users.id inner join roles on permissions.role_id = roles.id where users.id =  $userId");
        $getRolesName = $getRoles[0]->name;
        $getFullname = $getRoles[0]->fullName;
        DB::table('audit')->insert([
                'id' => null,
                'userId' => $userId,
                'name' => $getFullname,
                'role' => $getRolesName,
                'action' => $action,
                'section' => $section,
                'app' => 'Dashboard',
                'date' => DB::raw('now()')
            ]);
        Auth::logout();
        return redirect()->route('login');
    }

    // public function getAuthenticatedUser()
    // {
    //     retur
    // }
}
