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

class IncomeExpenseController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
     public function userIncomeExpense()
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
        
        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
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
        
        $incomeExpensesModule = 'IncomeExpensesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
       DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]); 
        
        $clients = DB::table('clients')
        ->where('client_type','Main Client')
        ->get()
        ->map(function($client) {
            $clientData['id'] = $client->id;
            $clientData['first_name'] = $client->first_name;
            $clientData['last_name'] = $client->last_name;
            $clientData['client_reference_id'] = $client->client_reference_id;
            return $clientData;
        });
        
        
        return view('incomeExpense.userIncomeExpense',['getAccessName'=>$getAccessName,'clients'=>$clients]); 
    }
     public function index(Request $request,$client_reference_id,$id)
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
        
        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
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
        
        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]); 
        
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        return view('incomeExpense.incomeExpense',[
            'getAccessName'=>$getAccessName,
            'client_owners'=>$client_owners,
            'id' => $id,
            'client_reference_id' => $client_reference_id
        ]); 
        
    }
    public function fetchSingleIncomeExpense(Request $request, $id,$client_reference_id,$type) {
        $incomeExpense_list = DB::table('income_expense_yearly')->where('client_reference_id', '=', $client_reference_id)->where('id','=', $id)->first();
        //dd($incomeExpense_list);
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        return view('incomeExpense.updateIncomeExpense', ['client_owners'=>$client_owners,'incomeExpense_list' => $incomeExpense_list, 'id' => $id,'client_reference_id' => $client_reference_id, 'type' => $type]);
        
    }
    
    public function listIncomeExpense($client_reference_id,$id)
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
        
        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
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
        
        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]);
        $client_incomes = DB::select("SELECT income_expense_capture.client_id,clients.client_reference_id, income_expenses_type,client_type,first_name,last_name,SUM(item_value) as income_total 
            FROM `income_expense_capture` INNER JOIN clients ON income_expense_capture.client_id = clients.id
            INNER JOIN income_expense_yearly ON income_expense_yearly.income_expense_capture_id = income_expense_capture.id where income_expense_yearly.income_expenses_type = 1 AND income_expense_capture.client_id ='$id' GROUP BY income_expense_capture.client_id");
        $client_expenes = DB::select("SELECT income_expense_capture.client_id, clients.client_reference_id,income_expenses_type,client_type,first_name,last_name,SUM(item_value) as expenes_total 
            FROM `income_expense_capture` INNER JOIN clients ON income_expense_capture.client_id = clients.id
            INNER JOIN income_expense_yearly ON income_expense_yearly.income_expense_capture_id = income_expense_capture.id where income_expense_yearly.income_expenses_type = 2 AND income_expense_capture.client_id ='$id' GROUP BY income_expense_capture.client_id");
        $client_income = array_merge($client_incomes, $client_expenes);
       
        return view('incomeExpense.listIncomeExpense',['getAccessName'=>$getAccessName,'client_income'=>$client_income,'client_expenes'=>$client_expenes,'client_reference_id'=>$client_reference_id,'id'=>$id]); 
    }
    public function addIncomeExpense($client_reference_id,$id){
        
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';
        
        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
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
        
        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]); 
        
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        return view('incomeExpense.incomeExpense',[
            'getAccessName'=>$getAccessName,
            'client_owners'=>$client_owners,
            'id' => $id,
            'client_reference_id' => $client_reference_id
        ]); 
    }
    
    public function store(Request $request)
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        $incomeExpensesModule = 'IncomeExpensesModule';
        $currentYear = date("Y");
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);


        $income_expense_id = DB::table('income_expense_capture')->insertGetId(
                                    [
                                        'id' => null,
                                        'client_id' => $_POST['client_id'],
                                        'advisor_id' => $userId,
                                        'capture_advisor_id' => '1',
                                        'client_reference_id' => $_POST['client_reference_id'],
                                        'capture_date' => date('Y-m-d'),
                                        'updated_date' => NOW()
                                    ]); 

        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses Store page, $userId created id=$income_expense_id row",
            'date' => DB::raw('now()')
        ]); 
        $count = count($_POST["income_item_type"]); 
        for($j =1; $j <= 12; $j++)
        {
           // echo $j . 'in loop = $j';
            for($i = 0; $i < $count; $i++)
            { 
                DB::select("INSERT into income_expense_yearly values (
                    null,
                    $income_expense_id,
                    '".$_POST['client_reference_id']."', 
                    '".$_POST['income_item_type'][$i]."', 
                    '".$_POST['income_type'][$i]."', 
                    '".$_POST['income_name'][$i]."', 
                    '".$_POST['income_value'][$i]."',
                    '$j',
                    '".$currentYear."',
                    NOW(),
                    '".$_POST['income_client_owners_id'][$i]."'
                    )");
            }
        }
        
        $count = count($_POST["income_item_type"]); 
        for($j =1; $j <= 12; $j++)
        {
            for($i = 0; $i < $count; $i++)
            {
                DB::select("INSERT into income_expense_yearly values (
                    null, 
                    $income_expense_id,
                    '".$_POST['client_reference_id']."', 
                    '".$_POST['expenses_item_type'][$i]."', 
                    '".$_POST['expenses_type'][$i]."', 
                    '".$_POST['expenses_name'][$i]."', 
                    '".$_POST['expenses_value'][$i]."', 
                    '$j',
                    '".$currentYear."',
                    NOW(),
                    '".$_POST['expense_client_owners_id'][$i]."'
                    )");
               }
        }  
        return \Redirect::route('listIncomeExpense', [$_POST['client_reference_id'],$_POST['client_id']]);
    } 
    public function viewIncomeExpense(Request $request,$id,$client_reference_id,$type)
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
        
        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
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
        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses View page",
            'date' => DB::raw('now()')
        ]);
        $income_list = DB::select("SELECT MONTHNAME(concat(year,'-',month,'-',0)) as month_name, income_expense_yearly.* FROM `income_expense_capture` INNER JOIN income_expense_yearly ON income_expense_capture.id=income_expense_yearly.income_expense_capture_id where income_expense_capture.client_reference_id ='$client_reference_id' and income_expense_yearly.income_expenses_type = '1'");
        $expense_list = DB::select("SELECT MONTHNAME(concat(year,'-',month,'-',0)) as month_name, income_expense_yearly.* FROM `income_expense_capture` INNER JOIN income_expense_yearly ON income_expense_capture.id=income_expense_yearly.income_expense_capture_id where income_expense_capture.client_reference_id ='$client_reference_id' and income_expense_yearly.income_expenses_type = '2'");        
        return view('incomeExpense.viewIncomeExpense',[
            'getAccessName'=>$getAccessName,
            'income_list' => $income_list,
            'expense_list' => $expense_list,
            'id' => $id,
            'client_reference_id' => $client_reference_id,
            'type' => $type
            ]);
    }
    public function update(Request $request)
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        $incomeExpensesModule = 'IncomeExpensesModule';
        $currentYear = date("Y");
        $query = DB::select('UPDATE income_expense_yearly SET 
            client_reference_id="'.$_POST["client_reference_id"].'",
            income_expenses_type="'.$_POST["type"].'",
            month="'.$_POST["income_month"].'",
            income_expenses_type="'.$_POST["income_item_type"].'",
            item_type="'.$_POST["income_type"].'", 
            item_name="'.$_POST["income_name"].'", 
            item_value="'.$_POST["income_value"].'", 
            owners_id="'.$_POST["client_owners_id"].'"
             
            WHERE client_reference_id="'.$_POST["client_reference_id"].'" AND id="'.$_POST["id"].'" ');
       
        return \Redirect::route('listIncomeExpense', [$_POST['client_reference_id'],$_POST["client_owners_id"]]);
    }
    public function deleteSingleIncome($id,$client_reference_id,$type,$client_id) {
        DB::select("DELETE FROM `income_expense_yearly` where client_reference_id = '$client_reference_id' AND  id = '$id' AND income_expenses_type = '$type'");
        return \Redirect::route('listIncomeExpense', [$client_reference_id,$client_id]);
    }
     public function deleteSingleExpense($id,$client_reference_id,$type,$client_id) {
        DB::select("DELETE FROM `income_expense_yearly` where client_reference_id = '$client_reference_id' AND  id = '$id' AND income_expenses_type = '$type'");
        return \Redirect::route('listIncomeExpense', [$client_reference_id,$client_id]);
    }
     
}