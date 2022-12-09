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
use Carbon\Carbon;
use DB;

class ClientAssetController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    public function createClientAssets()
    {
        // $client_assets = DB::select("SELECT * FROM `client_assets`");
        // return view('fna.clientAssestList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        /*
            Define All Modules Names
        */
        $personalInfoModule = 'Personal Information';
        $dependantsModule = 'Dependants';
        $assetsModule = 'Assets';
        $liabilitiesModule = 'Liabilities';
        $personalBudgetModule = 'Personal budget'; 
        $riskModule = 'Risk Objectives';
        $retirementRiskModule = 'Retirement Risks Objectives';
        /*
            Get All Module Ids
        */
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");
        $dependantsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$dependantsModule'");
        $assetsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$assetsModule'");
        $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalBudgetModuleModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalBudgetModule'");
        $riskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$riskModule'");
        $retirementRiskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$retirementRiskModule'");
        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."'");
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
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
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
        
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$retirementRiskModuleModuleId[0]->id."'");
            if(!isset($getretirementRiskAclAccessId[0]->accessId))
            {
                $getretirementRiskAclAccess = "noAccess";
            }
            else
            {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '".@$getretirementRiskAclAccessId[0]->accessId."'");
                if(!isset($getretirementAccessName[0]->name))
                {
                    $getretirementRiskAclAccess = "noAccess";
                    
                }
                else
                {
                    $getretirementRiskAclAccess = "Access";
                }
            }
            
            /*  
                Get Risk Access For Menu
            */
            $getRiskModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$riskModuleModuleId[0]->id."'");
            if(!isset($getRiskModuleAclAccessId[0]->accessId))
            {
                $getRiskModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $getRiskModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$getRiskModuleAclAccessId[0]->accessId."'");
                if(!isset($getRiskModuleAccessName[0]->name))
                {
                    $getRiskModuleModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $getRiskModuleModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Dependant Access For Menu
            */
            $dependantsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$dependantsModuleModuleId[0]->id."'");
            if(!isset($dependantsModuleAclAccessId[0]->accessId))
            {
                $dependantsModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $dependantsModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$dependantsModuleAclAccessId[0]->accessId."'");
                if(!isset($dependantsModuleAccessName[0]->name))
                { 
                    $dependantsModuleModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $dependantsModuleModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
            if(!isset($assetsModuleAclAccessId[0]->accessId))
            {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$assetsModuleAclAccessId[0]->accessId."'");
                if(!isset($assetsModuleAccessName[0]->name))
                { 
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Liabilities Module Access For Menu
            */
            $liabilitiesModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$liabilitiesModuleModuleId[0]->id."'");
            if(!isset($liabilitiesModuleAclAccessId[0]->accessId))
            {
                $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $liabilitiesModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$liabilitiesModuleAclAccessId[0]->accessId."'");
                if(!isset($liabilitiesModuleAccessName[0]->name))
                { 
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalBudgetModuleModuleModuleId[0]->id."'");
            if(!isset($personalBudgetModuleAclAccessId[0]->accessId))
            {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {
                
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalBudgetModuleAclAccessId[0]->accessId."'");
                if(!isset($personalBudgetModuleAccessName[0]->name))
                { 
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            /*  
                Get Personal Information Module Access For Menu
            */
            $personalInfoModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalInfoModuleModuleId[0]->id."'");
            if(!isset($personalInfoModuleAclAccessId[0]->accessId))
            {
                $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {   
                $personalInfoModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalInfoModuleAclAccessId[0]->accessId."'");
                if(!isset($personalInfoModuleAccessName[0]->name))
                { 
                    $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    if($personalInfoModuleAccessName[0]->name == "no-access")
                    {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    }
                    else
                    {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }
            }
        }
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = 'fna0000001'");
        return view('fna.createClientAssets', ['getroleId' => $getroleId, 'client_owners' => $client_owners, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
    }

    public function clientAssetsList()
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        /*
            Define All Modules Names
        */
        $personalInfoModule = 'Personal Information';
        $dependantsModule = 'Dependants';
        $assetsModule = 'Assets';
        $liabilitiesModule = 'Liabilities';
        $personalBudgetModule = 'Personal budget'; 
        $riskModule = 'Risk Objectives';
        $retirementRiskModule = 'Retirement Risks Objectives';
        /*
            Get All Module Ids
        */
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");
        $dependantsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$dependantsModule'");
        $assetsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$assetsModule'");
        $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalBudgetModuleModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalBudgetModule'");
        $riskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$riskModule'");
        $retirementRiskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$retirementRiskModule'");
        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."'");
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
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
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
        
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$retirementRiskModuleModuleId[0]->id."'");
            if(!isset($getretirementRiskAclAccessId[0]->accessId))
            {
                $getretirementRiskAclAccess = "noAccess";
            }
            else
            {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '".@$getretirementRiskAclAccessId[0]->accessId."'");
                if(!isset($getretirementAccessName[0]->name))
                {
                    $getretirementRiskAclAccess = "noAccess";
                    
                }
                else
                {
                    $getretirementRiskAclAccess = "Access";
                }
            }
            
            /*  
                Get Risk Access For Menu
            */
            $getRiskModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$riskModuleModuleId[0]->id."'");
            if(!isset($getRiskModuleAclAccessId[0]->accessId))
            {
                $getRiskModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $getRiskModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$getRiskModuleAclAccessId[0]->accessId."'");
                if(!isset($getRiskModuleAccessName[0]->name))
                {
                    $getRiskModuleModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $getRiskModuleModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Dependant Access For Menu
            */
            $dependantsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$dependantsModuleModuleId[0]->id."'");
            if(!isset($dependantsModuleAclAccessId[0]->accessId))
            {
                $dependantsModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $dependantsModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$dependantsModuleAclAccessId[0]->accessId."'");
                if(!isset($dependantsModuleAccessName[0]->name))
                { 
                    $dependantsModuleModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $dependantsModuleModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
            if(!isset($assetsModuleAclAccessId[0]->accessId))
            {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$assetsModuleAclAccessId[0]->accessId."'");
                if(!isset($assetsModuleAccessName[0]->name))
                { 
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            
            /*  
                Get Liabilities Module Access For Menu
            */
            $liabilitiesModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$liabilitiesModuleModuleId[0]->id."'");
            if(!isset($liabilitiesModuleAclAccessId[0]->accessId))
            {
                $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $liabilitiesModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$liabilitiesModuleAclAccessId[0]->accessId."'");
                if(!isset($liabilitiesModuleAccessName[0]->name))
                { 
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalBudgetModuleModuleModuleId[0]->id."'");
            if(!isset($personalBudgetModuleAclAccessId[0]->accessId))
            {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {
                
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalBudgetModuleAclAccessId[0]->accessId."'");
                if(!isset($personalBudgetModuleAccessName[0]->name))
                { 
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            /*  
                Get Personal Information Module Access For Menu
            */
            $personalInfoModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalInfoModuleModuleId[0]->id."'");
            if(!isset($personalInfoModuleAclAccessId[0]->accessId))
            {
                $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
            }
            else
            {   
                $personalInfoModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalInfoModuleAclAccessId[0]->accessId."'");
                if(!isset($personalInfoModuleAccessName[0]->name))
                { 
                    $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    if($personalInfoModuleAccessName[0]->name == "no-access")
                    {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    }
                    else
                    {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }
            }
        }
        // $assets = DB::select("SELECT * FROM `assets`");
        // return view('fna.assetList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
        $client_assets = DB::select("SELECT * FROM `client_assets`");
        return view('fna.clientAssetsList', ['getroleId' => $getroleId, 'client_assets' => $client_assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
    }

    public function createClientAssetsForm(Request $request)
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $count = count($_POST["asset_type"]); 

        for($i = 0; $i < $count; $i++)
        {
            DB::select("INSERT into client_assets values (
                null,
                '".$userId."',
                'fna0000001',
                '".$_POST['asset_type'][$i]."',
                '".$_POST['asset_name'][$i]."',
                '".$_POST['asset_value'][$i]."',
                '".$_POST['date_purchased'][$i]."',
                '".$_POST['client_owners_id'][$i]."')");
        }
    	return redirect()->route('clientAssetsList');
    }

    public function updateClientAssets(Request $request)
    {
       // var_dump($_POST);die();
        DB::select("UPDATE `client_assets` set type = '".$_POST['lt']."', description = '".$_POST['desc']."', debt_amount = '".$_POST['oda']."', outstanding_amount = '".$_POST['coa']."', interest_rate = '".$_POST['ir']."', installment = '".$_POST['IR']."', date = '".$_POST['DP']."', owners =  '".$_POST['o']."' where id = '".$_POST['liability_id']."'"); 
    	return redirect()->route('clientAssetsList');
    }    
    
    public function deleteClientAssets($id) 
    {
        DB::select("DELETE FROM `client_assets` where id = '$id' ");
        return redirect()->route('clientAssetsList');
    }
    
    public function storeClientAssets(Request $request) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $request->validate([
            'client_type' => 'required',
            'asset_type'=> 'required',
            // 'asset_sub_type'=> 'required',            
            // 'asset_desc'=> 'required',
            // 'asset_amount'=> 'required|numeric',
            // 'apply_to_events'=> 'required',
            // 'asset_executor_value' => 'required',
            // 'subject_to_cgt'=> 'required',
            // 'bona_fide_farm'=> 'required',
            // 'cgt_acquisition_date'=> 'required',
            // 'initial_value'=> 'required|numeric',
            // 'expenditure'=> 'required|numeric',
            // 'disposal_cost'=> 'required|numeric',
        ]);
        
        $asset_id = DB::table('client_assets')->insertGetId(
        [
        	'client_type' => $_POST['client_type'],
        	'advisor_id' => $_POST['advisor_id'],
        	'capture_advisor_id' => $_POST['capture_advisor_id'],
        	'client_reference_id' => $_POST['client_reference_id'],
        	'asset_type' => $_POST['asset_type'],
            // 'asset_sub_type'=> $_POST['asset_sub_type'],         	
        	'asset_description' => $_POST['asset_desc'],
        	'asset_amount' => intval(preg_replace('/[^\d.]/', '', $_POST['asset_amount'])),
        	'apply_to_event' => $_POST['apply_to_events'],
        	// 'asset_executor_value' => intval(preg_replace('/[^\d.]/', '', $_POST['asset_executor_value'])),
        	'allocation_death' => $_POST['allocation_death'],
        	'allocation_disability' => $_POST['allocation_disability'],
        	'allocation_dread_disease' => $_POST['allocation_dread_disease'],
        	'allocation_impairment' => $_POST['allocation_impairment'],
        	'subject_to_cgt' => $_POST['subject_to_cgt'],
        	'cgt_asset_type' => $_POST['cgt_asset_type'],
        	'cgt_bona_fide_farm' => $_POST['bona_fide_farm'],
        	'cgt_acquisition_date' => $_POST['cgt_acquisition_date'] ? $_POST['cgt_acquisition_date'] : Carbon::now()->format('Y-m-d'),
        	'cgt_initial_value' => intval(preg_replace('/[^\d.]/', '', $_POST['initial_value'])),
        	'cgt_expenditure' => intval(preg_replace('/[^\d.]/', '', $_POST['expenditure'])),
        	'cgt_disposal_cost' => intval(preg_replace('/[^\d.]/', '', $_POST['disposal_cost'])),
            'vehicle_vin_number'=> $_POST['vehicle_vin_number'],
            'vehicle_vin_number_json'=> $_POST['main_vehicle_number_json']
        ]); 

        if(isset($_POST['beneficiary_owners_id'])) {
            $beneficiary_count = count($_POST['beneficiary_owners_id']);
            if($beneficiary_count>0){
                for($i = 0; $i < $beneficiary_count; $i++)
                {
                    $arr = explode("_",$_POST['beneficiary_owners_id'][$i]);
                    $saveclientAssetsBeneficiary = DB::table('client_assets_beneficiary')->insert([
                        'owner_id' => $arr[0],
                        'type' => $arr[1],
                        'client_reference_id' => $_POST['beneficiary_client_reference_id'][$i],
                        'asset_id' => $asset_id,
                        'percentage' => $_POST['beneficiary_allocation_owners'][$i]
                    ]);
                }
            }
        }
        $assetsLiabilitiesModule = 'Assets Module';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets Save page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]);

        $client_reference_id = $_POST['client_reference_id'];
        $query_asset = DB::select("SELECT * FROM client_assets WHERE client_reference_id = '".$client_reference_id."'");
    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id , 'client_type' => 'Main Client', 'type' => 'assets' ]);
    }
    public function storeClientAssetsNew(Request $request) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $asset_id = DB::table('client_assets')->insertGetId(
        [
            'client_type' => $_POST['client_type'],
            'advisor_id' => $_POST['advisor_id'],
            'capture_advisor_id' => $_POST['capture_advisor_id'],
            'client_reference_id' => $_POST['client_reference_id'],
            'asset_type' => $_POST['asset_type'],
            'asset_sub_type'=> $_POST['asset_sub_type'],            
            'asset_description' => $_POST['asset_desc'],
            'asset_amount' => intval(preg_replace('/[^\d.]/', '', $_POST['asset_amount'])),
            'apply_to_event' => $_POST['apply_to_events'],
            'asset_executor_value' => intval(preg_replace('/[^\d.]/', '', $_POST['asset_executor_value'])),
            
        ]); 
        return redirect()->route('whatamiworth',['client_reference_id' => $_POST['client_reference_id'], 'client_type' => 'Main Client']);
}
    public function createClientAssetsNew ($client_reference_id , $client_type) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $client_owners = DB::select("SELECT * FROM clients WHERE client_reference_id = '".$client_reference_id."'");
        
            
        // $client_owners_merge = DB::table('clients')
        //     ->where('client_reference_id', $client_reference_id)
        //     ->get()
        //     ->map(function($client) {
        //         $data['id'] = $client->id;
        //         $data['first_name'] = $client->first_name;
        //         $data['last_name'] = $client->last_name;
        //         $data['type'] = $client->client_type;
        //         $data['is_type'] = 'Client';
        //         return $data;
        //     });
        // $client_dependents = DB::table('dependants')
        //     ->where('client_reference_id', $client_reference_id)
        //     ->get()
        //     ->map(function($dependant) {
        //         $data['id'] = $dependant->id;
        //         $data['first_name'] = $dependant->first_name;
        //         $data['last_name'] = $dependant->last_name;
        //         $data['type'] = $dependant->dependant_type;
        //         $data['is_type'] = 'Dependant';
        //         return $data;
        // });
        // $client_beneficiary = collect($client_owners_merge)->merge(collect($client_dependents)); 
        $client_beneficiary = [];//$client_owners_merge;
        $asset_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 1)->orderBy('id','DESC')->get();
        
    	return view('fna.createClientAssetsNew',[ 'asset_types' => $asset_types, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type, 'client_owners' => $client_owners, 'client_beneficiary' => $client_beneficiary]); 
    }
    
    public function newAssetsList($client_reference_id) { 
        $query_asset = DB::select("SELECT * FROM client_assets WHERE client_reference_id = '".$client_reference_id."'");
    	return view('fna.clientAssetsListNew',['client_assets'=>$query_asset]); 
    }
    
    /*Show client asset data on edit page*/
    public function fetchClientAssetsList($client_reference_id, $id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $client_owners = DB::select("SELECT * FROM clients WHERE client_reference_id = '".$client_reference_id."'");
            
        $client_owners_merge = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->get()
            ->map(function($client) {
                $data['id'] = $client->id;
                $data['first_name'] = $client->first_name;
                $data['last_name'] = $client->last_name;
                $data['type'] = $client->client_type;
                $data['is_type'] = $client->client_type;
                return $data;
            });
        // $client_dependents = DB::table('dependants')
        //     ->where('client_reference_id', $client_reference_id)
        //     ->get()
        //     ->map(function($dependant) {
        //         $data['id'] = $dependant->id;
        //         $data['first_name'] = $dependant->first_name;
        //         $data['last_name'] = $dependant->last_name;
        //         $data['type'] = $dependant->dependant_type;
        //         $data['is_type'] = 'Dependant';
        //         return $data;
        // });
        // $client_beneficiary = collect($client_owners_merge)->merge(collect($client_dependents)); 
        $client_beneficiary = $client_owners_merge;
        $query = DB::select("SELECT * FROM client_assets WHERE client_reference_id = '".$client_reference_id."' AND id = '".$id."'");

        $query_owner = DB::select("SELECT * FROM client_assets_ownership WHERE client_reference_id = '".$client_reference_id."' AND asset_id = '".$id."'");
        
        $asset_owners = DB::select("SELECT `client_assets_ownership`.id, owner_id, asset_id, percentage, first_name, last_name , 
        client_type , type FROM `client_assets_ownership` 
        left join clients on clients.id = owner_id WHERE client_assets_ownership.`client_reference_id`  = '".$client_reference_id."' AND asset_id = '".$id."'");

        $query_beneficiary = DB::select("SELECT * FROM client_assets_beneficiary WHERE client_reference_id = '".$client_reference_id."' AND asset_id = '".$id."'");
        
        $asset_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 1)->orderBy('id','DESC')->get();

        $arrBeneficiary = array();
        for($i=0; $i<count($query_beneficiary); $i++) {
            $client_owner = DB::table('clients')
                            ->where('id', $query_beneficiary[$i]->owner_id)
                            ->get()
                            ->map(function($client) {
                                $data['id'] =  $client->id;
                                $data['first_name'] = $client->first_name;
                                $data['last_name'] = $client->last_name;
                                $data['type'] = $client->client_type;
                                return $data;
                            });
        array_push($arrBeneficiary,$client_owner);
        }

        
        return view('fna.updateClientAssetNew',
            [
                'client_assets' => $query[0], //it has asset details
                'client_reference_id' => $client_reference_id,
                'client_owners' => $client_owners, 
                'client_beneficiary' => $client_beneficiary, 
                'asset_owners' => $asset_owners,
                'asset_beneficiarys' => $query_beneficiary,
                'asset_id' => $id,
                'asset_types' => $asset_types
            ]); 
    }  
    
    /*update client asset data*/ //$client_reference_id, $id) {
    public function updateClientAssetsNew(Request $request) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        // $request->validate([
        //     'asset_type'=> 'required',
        //     'asset_sub_type'=> 'required',            
        //     'asset_desc'=> 'required',
        //     'asset_amount'=> 'required|numeric',
        //     'apply_to_events'=> 'required',
        //     'asset_executor_value' => 'required',
        //     'subject_to_cgt'=> 'required',
        //     'bona_fide_farm'=> 'required',
        //     'cgt_acquisition_date'=> 'required',
        //     'initial_value'=> 'required|numeric',
        //     'expenditure'=> 'required|numeric',
        //     'disposal_cost'=> 'required|numeric',
        //     ]);
                                        // advisor_id = '".$_POST['advisor_id']."',
                                // capture_advisor_id = '".$_POST['capture_advisor_id']."',
                                // asset_sub_type= '".$_POST['asset_sub_type']."',
                                // asset_executor_value = '".$_POST['asset_executor_value']."',    
                                $vehicle_vin_number_json = $_POST['main_vehicle_number_json'];
        $query = DB::select("UPDATE client_assets SET 
                                client_type = '".$_POST['client_type']."',
                                asset_type = '".$_POST['asset_type']."',
                                asset_description = '".$_POST['asset_desc']."',
                                asset_amount = '".$_POST['asset_amount']."',
                                apply_to_event = '".$_POST['apply_to_events']."',        	                    
                                allocation_death = '".$_POST['allocation_death']."',
                                allocation_disability = '".$_POST['allocation_disability']."',
                                allocation_dread_disease = '".$_POST['allocation_dread_disease']."',
                                allocation_impairment = '".$_POST['allocation_impairment']."',
                                subject_to_cgt = '".$_POST['subject_to_cgt']."',
                                cgt_asset_type = '".$_POST['cgt_asset_type']."',
                                cgt_bona_fide_farm = '".$_POST['bona_fide_farm']."',
                                cgt_acquisition_date = '".$_POST['cgt_acquisition_date']."',
                                cgt_initial_value = '".$_POST['initial_value']."',
                                cgt_expenditure = '".$_POST['expenditure']."',
                                cgt_disposal_cost = '".$_POST['disposal_cost']."',
                                vehicle_vin_number = '".$_POST['vehicle_vin_number']."',
                                vehicle_vin_number_json = '".$vehicle_vin_number_json."'
                            WHERE client_reference_id = '".$_POST['client_reference_id']."' AND id = '".$_POST['asset_id']."'");
       
        $asset_id = $_POST['asset_id'];
  
        if(isset($_POST['beneficiary_owners_id'])) {
            $assetOwnerDelete = DB::select("DELETE FROM client_assets_beneficiary WHERE client_reference_id = '".$_POST['client_reference_id']."'");                                    
            $beneficiary_count = count($_POST['beneficiary_owners_id']);
            if($beneficiary_count>0){
                for($i = 0; $i < $beneficiary_count; $i++)
                {
                    $arr = explode("_",$_POST['beneficiary_owners_id'][$i]);
                    $saveclientAssetsBeneficiary = DB::table('client_assets_beneficiary')->insert([
                        'owner_id' => $arr[0],
                        'type' => $arr[1],
                        'client_reference_id' => $_POST['beneficiary_client_reference_id'][$i],
                        'asset_id' => $asset_id,
                        'percentage' => $_POST['beneficiary_allocation_owners'][$i]
                    ]);
                }
            }
        }
        $assetsLiabilitiesModule = 'Assets Module';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets Update page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]);
        $client_reference_id = $_POST['client_reference_id'];
        $query_asset = DB::select("SELECT * FROM client_assets WHERE client_reference_id = '".$client_reference_id."'");
    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id , 'client_type' => 'Main Client', 'type' => 'assets' ]);    
    }   
   
    /*delete client_assets_beneficiary data*/
    public function deleteClientAssetsNew($client_reference_id, $id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $query1 = DB::select("DELETE FROM client_assets_ownership  WHERE client_reference_id = '".$client_reference_id."' AND asset_id = '".$id."'");
        $query2 = DB::select("DELETE FROM client_assets_beneficiary  WHERE client_reference_id= '".$client_reference_id."' AND asset_id = '".$id."'");
        $query = DB::select("DELETE FROM client_assets where client_reference_id  = '".$client_reference_id."' AND id = '".$id."'");      
   
    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id , 'client_type' => 'Main Client', 'type' => 'assets' ]);
    }
    
    public function createClientLiabilitiesNew() {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        die('in here') ;
        $liabilitiesId = DB::table('client_liabilities_new')->insertGetId(
		[
			'advisor_id' => $_POST['advisor_id'],
			'capture_advisor_id' => $_POST['capture_advisor_id'],
			'client_reference_id' => $_POST['client_reference_id'],
			'liability_type' => $_POST['liability_type'],
			'liability_name' => $_POST['liability_name'],
			'policy_number' => $_POST['policy_number'],
			'outstanding_balance' => $_POST['outstanding_balance'],
			'under_advice' => $_POST['under_advice'],
			'type_of_business' => $_POST['type_of_business'],
			'original_balance' => $_POST['original_balance'],
			'loan_application_amount' => $_POST['loan_application_amount'],
			'limit' => $_POST['limit'],
			'principal_repaid' => $_POST['principal_repaid'],
			'last_updated_by' => $_POST['last_updated_by'],
			'interest_rate_type' => $_POST['interest_rate_type'],
			'interest_rate_pa' => $_POST['interest_rate_pa'],
			'loan_term' => $_POST['loan_term'],
			'loan_term_value' => $_POST['loan_term_value'],
			'repayment_amount' => $_POST['repayment_amount'],
			'repayment_frequency' => $_POST['repayment_frequency'],
			'select_asset_type' => $_POST['select_asset_type']
		]);
        
        $type = count($_POST['type']);
        for($i = 0; $i < count($type); $i++)
        {
            $saveclientAssetsBeneficiary = DB::table('client_liabilities_ownership')->insert([
            	'owner_id' => $_POST['owner_id'][$i],
            	'type' => $_POST['type'][$i],
            	'client_reference_id' => $_POST['client_reference_id'][$i],
            	'liabilities_id' => $liabilitiesId,
            	'percentage' => $_POST['percentage'][$i]
            ]);
        }
              
    	return redirect()->route('clientLiabilitiesList'); 
    }
    
    public function updateClientLiabilitiesNew($client_reference_id, $id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $user_id = 1;
        $client_reference_id . '=' . $id;        
        $query = DB::select('UPDATE client_liabilities_new SET 
        	advisor_id="'.$_POST["advisor_id"].'", 
        	capture_advisor_id="'.$_POST["capture_advisor_id"].'", 
        	client_reference_id="'.$_POST["client_reference_id"].'", 
        	liability_type="'.$_POST["liability_type"].'", 
        	liability_name="'.$_POST["liability_name"].'", 
        	policy_number="'.$_POST["policy_number"].'", 
        	outstanding_balance="'.$_POST["outstanding_balance"].'", 
        	under_advice="'.$_POST["under_advice"].'", 
        	type_of_business="'.$_POST["type_of_business"].'", 
        	original_balance="'.$_POST["original_balance"].'", 
        	loan_application_amount="'.$_POST["loan_application_amount"].'", 
        	limit="'.$_POST["limit"].'", 
        	principal_repaid="'.$_POST["principal_repaid"].'", 
        	last_updated_by="'.$_POST["last_updated_by"].'", 
        	interest_rate_type="'.$_POST["interest_rate_type"].'", 
        	interest_rate_pa="'.$_POST["interest_rate_pa"].'", 
        	loan_term="'.$_POST["loan_term"].'", 
        	loan_term_value="'.$_POST["loan_term_value"].'", 
        	repayment_amount="'.$_POST["repayment_amount"].'", 
        	repayment_frequency="'.$_POST["repayment_frequency"].'", 
        	select_asset_type="'.$_POST["select_asset_type"].'",
            expense_type= "'.$_POST["expense_type"].'"
        	WHERE client_reference_id="'.$client_reference_id.'" AND id= "'.$id.'"'); 
        $asset_id = $id;
        $beneficiary_count = count($_POST['beneficiary_id']);
        for($i = 0; $i < count($beneficiary_count); $i++)
        {
            $updateclientLiabilitiesBeneficiary = DB::select("UPDATE client_liabilities_ownership SET
        	owner_id = '".$_POST['owner_id'][$i]."',
        	type = '".$_POST['type'][$i]."',
        	client_reference_id = '".$_POST['client_reference_id'][$i]."',
        	liabilities_id = '".$liabilitiesId."',
        	percentage = '".$_POST['percentage'][$i]."'
        	WHERE id = '".$_POST['benef_id'][$i]."'"
        );   
        }                          
    }
    
    public function deleteClientLiabilitiesNew($client_reference_id, $id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $query1 = DB::select("DELETE FROM client_liabilities_new  WHERE client_reference_id '".$client_reference_id."' AND id = '".$id."'");
        $query2 = DB::select("DELETE FROM client_liabilities_ownership  WHERE client_reference_id '".$client_reference_id."' AND liabilities_id = '".$id."'");      
    // 	return redirect()->route('clientLiabilitiesList'); 
    }   
    
    public function fetchClientLiabilitiesList($client_reference_id, $id) {
        var_dump('gets here..');
        die();
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $user_id = 1;
        $client_reference_id . '=' . $id;
        $query = DB::select("SELECT * FROM client_liabilities_new WHERE client_reference_id = '".$client_reference_id."' AND id = '".$id."'");        
        $query_owner = DB::select("SELECT * FROM client_liabilities_ownership WHERE client_reference_id = '".$client_reference_id."' AND id = '".$id."'");
         
    }
    
    
     public function insuranceFormView($client_reference_id){
          $clientReff = $client_reference_id; 

        $client_owners = DB::table('clients')
                            ->where('client_reference_id', $clientReff)
                            ->get()
                            ->map(function($client) {
                                $data['id'] = $client->id;
                                $data['first_name'] = $client->first_name;
                                $data['last_name'] = $client->last_name;
                                $data['type'] = $client->client_type;

                                return $data;
                            });
                       
        $client_dependents = DB::table('dependants')
                                ->where('client_reference_id', $clientReff)
                                ->get()
                                ->map(function($dependant) {

                                    $data['id'] = $dependant->id;
                                    $data['first_name'] = $dependant->first_name;
                                    $data['last_name'] = $dependant->last_name;
                                    $data['type'] = $dependant->dependant_type;
    
                                    return $data;
                                });
        $owners = collect($client_owners)->merge(collect($client_dependents));



        return view('fna.createInsurance',[
            'client_reference_id'=>$clientReff,
             'client_owners'=> $owners
            ]);
      
    }
    
    public function createClientInsuranceNew() {
        // var_dump($_POST);
        // die();
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
         
        $insuranceId = DB::table('client_insurances')->insertGetId(
		[
			'advisor_id' => $_POST['advisor_id'],
			'capture_advisor_id' => $_POST['capture_advisor_id'],
			'client_reference_id' => $_POST['client_reference_id'],
			'life_cover_description' => $_POST['life_cover_description'],
			'life_cover_policy_ref' => $_POST['life_cover_policy_ref'],
			'life_cover_owner' => $_POST['life_cover_owner'],
			'life_cover_assured' => $_POST['life_cover_assured'],
			'life_cover_cash_value' => $_POST['life_cover_cash_value'],
			'life_cover_death' => $_POST['life_cover_death'],
			'life_cover_disability' => $_POST['life_cover_disability'],
			'life_cover_dread_disease' => $_POST['life_cover_dread_disease'],
			'life_cover_impairment' => $_POST['life_cover_impairment'],
			'sick_description' => $_POST['sick_description'],
			'sick_monthly_amount' => $_POST['sick_monthly_amount'],
			'sick_frequency' => $_POST['sick_frequency'],
			'sick_waiting_period' => $_POST['sick_waiting_period'],
			'sick_term' => $_POST['sick_term'],
			'sick_claim_escalation' => $_POST['sick_claim_escalation'],
			'sick_esc' => $_POST['sick_esc'],
			'income_protect_description' => $_POST['income_protect_description'],
			'income_protect_monthly_amount' => $_POST['income_protect_monthly_amount'],
			'income_protect_frequency' => $_POST['income_protect_frequency'],
			'income_protect_waiting_period' => $_POST['income_protect_waiting_period'],
			'income_protect_term' => $_POST['income_protect_term'],
			'income_protect_claim_escalation' => $_POST['income_protect_claim_escalation'],
			'income_protect_esc' => $_POST['income_protect_esc'],
			'death_term_related_to' => $_POST['death_term_related_to'],
			'death_description' => $_POST['death_description'],
			'death_date_of_birth' => $_POST['death_date_of_birth'],
			'death_waiting_period' => $_POST['death_waiting_period'],
			'death_term' => $_POST['death_term'],
			'death_claim_escalation' => $_POST['death_claim_escalation'],
			'death_esc' => $_POST['death_esc'],
			'insurance_allocation_for_death' => $_POST['insurance_allocation_for_death'],
			'insurance_allocation_for_disability' => $_POST['insurance_allocation_for_disability'],
			'insurance_allocation_for_dread_disease' => $_POST['insurance_allocation_for_dread_disease'],
			'insurance_allocation_for_impairment' => $_POST['insurance_allocation_for_impairment'],
			'insurance_allocation_for_income_benefits' => $_POST['insurance_allocation_for_income_benefit']
			
		]);
		
		
        if(isset($_POST['beneficiary_owners_id'])){
        $type = count($_POST['beneficiary_owners_id']);
        if($type>0){
        for($i = 0; $i < $type; $i++)
        {
            $saveclientAssetsBeneficiary = DB::table('client_insurances_beneficiary')->insert([
            	'owner_id' => $_POST['beneficiary_owners_id'][$i],
            	'type' => $_POST['asset_item_type'][$i],
            	'client_reference_id' => $_POST['client_reference_id'],
            	'insurance_id' => $insuranceId,
            	'percentage' => $_POST['beneficiary_allocation_owners'][$i]
            ]);
        }
        }
        } 
        
        $client_reference_id =$_POST['client_reference_id'];
        
         $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."'");        
        
          return view('fna.insuranceListView',['client_insurance'=>$query]);
    //      var_dump('done');
    //      die();
              
    // 	return redirect()->route('clientInsuranceList'); 
    }
    
    public function insuranceListView($client_reference_id) {
    
        $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."'"); 
        return view('fna.insuranceListView',['client_insurance'=>$query]);
        //      var_dump('done');
        //      die();
                  
        // 	return redirect()->route('clientInsuranceList'); 
    }
    
    public function updateClientInsuranceNew($client_reference_id) {
        
        // var_dump($_POST);
        // die();
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $user_id = 1;
        $client_reference_id . '=' . $id;        
        $query = DB::select('UPDATE client_insurances SET 
        	advisor_id="'.$_POST["advisor_id"].'",
        	capture_advisor_id="'.$_POST["capture_advisor_id"].'",
        	client_reference_id="'.$_POST["client_reference_id"].'",
        	life_cover_description="'.$_POST["life_cover_description"].'",
        	life_cover_policy_ref="'.$_POST["life_cover_policy_ref"].'",
        	life_cover_owner="'.$_POST["life_cover_owner"].'", 
        	life_cover_assured="'.$_POST["life_cover_assured"].'", 
        	life_cover_cash_value="'.$_POST["life_cover_cash_value"].'",
        	life_cover_death="'.$_POST["life_cover_death"].'", 
        	life_cover_disability="'.$_POST["life_cover_disability"].'", 
        	life_cover_dread_disease="'.$_POST["life_cover_dread_disease"].'", 
        	life_cover_impairment="'.$_POST["life_cover_impairment"].'", 
        	sick_description="'.$_POST["sick_description"].'" ,
        	sick_monthly_amount="'.$_POST["sick_monthly_amount"].'" ,
        	sick_frequency="'.$_POST["sick_frequency"].'" ,
        	sick_waiting_period="'.$_POST["sick_waiting_period"].'", 
        	sick_term="'.$_POST["sick_term"].'" ,
        	sick_claim_escalation="'.$_POST["sick_claim_escalation"].'" ,
        	sick_esc="'.$_POST["sick_esc"].'", 
        	income_protect_description="'.$_POST["income_protect_description"].'" ,
        	income_protect_monthly_amount="'.$_POST["income_protect_monthly_amount"].'" ,
        	income_protect_frequency="'.$_POST["income_protect_frequency"].'" , 
        	income_protect_waiting_period="'.$_POST["income_protect_waiting_period"].'" , 
        	income_protect_term="'.$_POST["income_protect_term"].'" ,        	    
        	income_protect_claim_escalation="'.$_POST["income_protect_claim_escalation"].'" , 
        	income_protect_esc="'.$_POST["income_protect_esc"].'" , 
        	death_term_related_to="'.$_POST["death_term_related_to"].'" ,
        	death_description="'.$_POST["death_description"].'" ,
        	death_date_of_birth="'.$_POST["death_date_of_birth"].'" ,
        	death_waiting_period="'.$_POST["death_waiting_period"].'" ,
        	death_term="'.$_POST["death_term"].'" ,
        	death_claim_escalation="'.$_POST["death_claim_escalation"].'" ,
        	death_esc="'.$_POST["death_esc"].'" , 
        	insurance_allocation_for_death="'.$_POST["insurance_allocation_for_death"].'" ,
        	insurance_allocation_for_disability="'.$_POST["insurance_allocation_for_disability"].'" ,
        	insurance_allocation_for_dread_disease="'.$_POST["insurance_allocation_for_dread_disease"].'" , 
        	insurance_allocation_for_impairment="'.$_POST["insurance_allocation_for_impairment"].'" , 
			insurance_allocation_for_income_benefits="'.$_POST["insurance_allocation_for_income_benefits"].'" 
        	 
        	WHERE client_reference_id="'.$_POST["client_reference_id"].'"');
        	
     if(isset($_POST['beneficiary_owners_id'])){
        
        $beneficiary_count = count($_POST['beneficiary_owners_id']);
    
        for($i = 0; $i < $beneficiary_count; $i++)
        {
            $updateclientLiabilitiesBeneficiary = DB::select("UPDATE client_insurances_beneficiary SET
        	owner_id = '".$_POST['beneficiary_owners_id'][$i]."',
        	type = '".$_POST['asset_item_type'][$i]."',
        	client_reference_id = '".$_POST['client_reference_id'][$i]."',
        	percentage = '".$_POST['beneficiary_allocation_owners'][$i]."'
        	WHERE id = '".$_POST['beneficiary_item_type'][$i]."'"
        );   
        } 

        
     }
        
        
        $query_owner = DB::select("SELECT * FROM client_insurances_beneficiary WHERE client_reference_id = '".$client_reference_id."'");
         
         $arrOwers = array();
        
        for($i = 0;$i<count($query_owner);$i++){
            
        $client_owner = DB::table('clients')
                            ->where('id', $query_owner[$i]->owner_id)
                            ->get()
                            ->map(function($client) {
                                $data['id'] =  $client->id;
                                $data['first_name'] = $client->first_name;
                                $data['last_name'] = $client->last_name;
                                $data['type'] = $client->client_type;

                                return $data;
                            });
        array_push($arrOwers,$client_owner);
        }
          $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."'"); 
        return view('fna.InsuranceUpdateView',['client_insurance'=>$query[0],'client_owners'=>$arrOwers[0], 'client_reference_id'=>$client_reference_id]);
        
    }
    
    public function deleteClientInsuranceNew($client_reference_id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $query1 = DB::select("DELETE FROM client_insurances  WHERE client_reference_id = '".$client_reference_id."'");
        $query2 = DB::select("DELETE FROM client_insurances_beneficiary  WHERE client_reference_id= '".$client_reference_id."'");      
    // 	return redirect()->route('clientLiabilitiesList'); 
    
        
         $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."'");        
        
          return view('fna.insuranceListView',['client_insurance'=>$query]);
        
    }
    
    public function fetchClientInsuranceList($client_reference_id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $user_id = 1;
        $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."'");        
        $query_owner = DB::select("SELECT * FROM client_insurances_beneficiary WHERE client_reference_id = '".$client_reference_id."'");
         
         $arrOwers = array();
        
        for($i = 0;$i<count($query_owner);$i++){
            
        $client_owner = DB::table('clients')
                            ->where('id', $query_owner[$i]->owner_id)
                            ->get()
                            ->map(function($client) {
                                $data['id'] =  $client->id;
                                $data['first_name'] = $client->first_name;
                                $data['last_name'] = $client->last_name;
                                $data['type'] = $client->client_type;

                                return $data;
                            });
        array_push($arrOwers,$client_owner);
        }
        
         
        return view('fna.InsuranceUpdateView',['client_insurance'=>$query[0],'client_owners'=>$arrOwers[0], 'client_reference_id'=>$client_reference_id]);
        
    } 
   
}