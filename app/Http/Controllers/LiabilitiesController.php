<?php

namespace App\Http\Controllers;

use PDF;
use App\User;
use App\Client;
use App\Invoice;
use App\Payment;
use Illuminate\Http\Request;
use App\PaymentGatewayConfig;
use App\Exports\PaymentExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\StorePaymentConfig;
use DB;

class LiabilitiesController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    
     public function index()
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';
        
        $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
         $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");
        
        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."'");
        //var_dump($getroleId); die();
        if(!isset($getroleId[0]->groupId))
        {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        }
        else
        {
             /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalInfoModuleModuleId[0]->id."'");
            if(!isset($getAclAccessId[0]->accessId))
            {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            $getAccessName = DB::select("SELECT * FROM `access` where id = '".@$getAclAccessId[0]->accessId."'");
            if(!isset($getAccessName[0]->name))
            {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            else
            {
                if($getAccessName[0]->name == "no-access")
                {
                    header("location: https://fna2.phpapplord.co.za/public/noAccess");
                    exit;
                }
            }
        }
            
        $client_liabilities = DB::select("SELECT * FROM `client_liabilities` INNER JOIN clients ON client_liabilities.owners_id = clients.id");
        return view('fna.listLiabilities',['getAccessName'=>$getAccessName,'clientLiabilities'=>$client_liabilities]);
        
    }
    
    public function createLiabilities()
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
            
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = 'fna0000001'");
        return view('fna.createLiabilities', ['client_owners' => $client_owners]);
        
    }
    public function saveLiabilities(Request $request)
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        $count = count($_POST["liabilities_type"]); 
    	for($i = 0; $i < $count; $i++)
    	{ 
    		DB::select("INSERT into client_liabilities values (
    			null, 
    			'".$userId."', 
    			'fna0000001', 
    			'".$_POST['liabilities_type'][$i]."', 
    			'".$_POST['liabilities_name'][$i]."', 
    			'".$_POST['liabilities_value'][$i]."', 
    			'".$_POST['date_purchased'][$i]."', 
    			'".$_POST['client_owners_id'][$i]."')");
    	}
    	return redirect()->route('LiabilitiesList');
    }
    
    public function updateClientAssets(Request $request,$id)
    {
        echo $id;
    }
    
}