<?php

namespace App\Http\Controllers;

use PDF;
use App\Astute;
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
use DataTables;

class AuditTrailController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    
    public function index(Request $request) {
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
        return view('auditTrail.listAuditTrail',['getAccessName'=>$getAccessName,'client_type'=>'Main Client','bank_name'=>'0']);
    }
    
    public function listAuditAjax(Request $request) {
        session_start();
        $userId = $_SESSION['userId'];
        if ($request->ajax()) {
            $clients = "SELECT 
                        audit.id, 
                        concat(users.name , ' ' , users.surname) as user, 
                        module, 
                        role, 
                        action as action_by, 
                        DATE_FORMAT(date,'%d-%m-%Y') as date FROM `audit` Left JOIN users on users.id = audit.user 
                        WHERE
                        ";
            if(!empty($request->from_date) and !empty($request->module_name)) {
                    $clients .= "  
                    (date(date) BETWEEN date('". $request->from_date ."') AND date('". $request->to_date ."') )
                    AND module LIKE '%" . $request->module_name . "%'
                    ";  
            } elseif (!empty($request->module_name)) {
                $clients .= " module LIKE '%" . $request->module_name . "%'";
            } elseif (!empty($request->from_date)) {
                $clients .= " (date(date) >= date('". $request->from_date ."') )";  
            }
            $clients .= " audit.user = '".$_SESSION['userId']."' ";
            // echo $clients; die;
            $clients  = DB::select($clients);

            return Datatables::of($clients)
                    ->addIndexColumn()
                    ->make(true);
        }
        return view('auditTrail.listAuditTrail');
    }
    
    public function store(Request $request)
    {
        die('store');
     
    }	

    public function exportCsv(Request $request) {
    $fileName = 'Audittrail.csv';
    $clients = DB::select("SELECT audit.id, concat(users.name , ' ' , users.surname) as user, module, role, action as action_by, date FROM `audit` Left JOIN users on users.id = audit.user");  

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Id', 'Date', 'Changes', 'User', 'Details', 'Role');

        $callback = function() use($clients, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($clients as $client) {
                $row['Id']  = $client->id;
                $row['Date']    = $client->date;
                $row['Changes']    = $client->module;
                $row['User']  = $client->user;
                $row['Details']  = $client->action_by;
                $row['Role']  = $client->role;
                fputcsv($file, array($row['Id'], $row['Date'], $row['Changes'], $row['User'], $row['Details'], $row['Role']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    public function exportCsvFilter(Request $request) {
        // dd($_POST);
            $clients = "SELECT 
                        audit.id, 
                        concat(users.name , ' ' , users.surname) as user, 
                        module, 
                        role, 
                        action as action_by, 
                        date FROM `audit` Left JOIN users on users.id = audit.user 
                        ";        
            if(!empty($request->from_date) and !empty($request->module_name) and !empty($request->search_keyword)) {
                    $clients .= " WHERE 
                    (date(date) BETWEEN date('". $request->from_date ."') AND date('". $request->to_date ."') )
                    AND module LIKE '%" . $request->module_name . "%'
                    AND action LIKE '%" . $request->search_keyword . "%'
                    ";  
            } elseif(!empty($request->from_date) and !empty($request->module_name)) {
                    $clients .= " WHERE 
                    (date(date) BETWEEN date('". $request->from_date ."') AND date('". $request->to_date ."'))
                    AND module LIKE '%" . $request->module_name . "%'
                    ";  
            } elseif (!empty($request->module_name)) {
                $clients .= "WHERE module LIKE '%" . $request->module_name . "%'";
            } elseif (!empty($request->from_date)) {
                $clients .= "WHERE (date(date) BETWEEN date('". $request->from_date ."') AND date('". $request->to_date ."') )";  
            } elseif (!empty($request->search_keyword)) {
                $clients .= "WHERE module LIKE '%" . $request->search_keyword . "%' OR  action LIKE '%" . $request->search_keyword . "%' ";  
            }

        $fileName = 'Audittrailfilter.csv';
        $clients  = DB::select($clients . ' ORDER BY audit.id DESC');
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Id', 'Date', 'Changes', 'User', 'Details', 'Role');

        $callback = function() use($clients, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($clients as $client) {
                $row['Id']  = $client->id;
                $row['Date']    = $client->date;
                $row['Changes']    = $client->module;
                $row['User']  = $client->user;
                $row['Details']  = $client->action_by;
                $row['Role']  = $client->role;
                fputcsv($file, array($row['Id'], $row['Date'], $row['Changes'], $row['User'], $row['Details'], $row['Role']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    public function testloop()
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $client_reference_id = 'fna000000001';
        $client_type = 'Main Client';
        $userId = $_SESSION['userId'];
        $newAstute = new Astute("Abrie89", "Kawasaki@1234567", "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC");
        $MessageGuidId = "bc77d8ee-c532-4fa5-bcb1-02a972ee0ded"; // "5dcb8815-cb83-44ff-bf05-ec7001a14c63"; //
        // var_dump($newAstute->CCPRequest()); die();
        $getWebMessageContent = $newAstute->MessageContent($MessageGuidId);
        // print_r("<pre>"); var_dump($getWebMessageContent); die();
        $tata = json_decode($getWebMessageContent);
        $providerNames = array(
                'LIBL' => 'Liberty Group Limited',
                'OMUL' => 'Old Mutual Limited',
            );
        $counter = 0;
        $insuranceInfo = array();
        foreach($tata->Result->Data->MessageBody as $provider)
        {
            
            $oLife = "<OLifE>";
            $insuranceInfo[$counter]['ContentSchemaRef'] = $provider->ContentSchemaRef;
            $insuranceInfo[$counter]['providerCode'] = $provider->ProviderCode;
            $insuranceValues = $provider->Value;
            $checkProvider = (strpos($insuranceValues, $oLife) !== false) ? true : false;
            $insuranceInfo[$counter]['providerCode'] = $checkProvider;
            if(!$checkProvider)
            {
                $insuranceInfo[$counter]['Value']['ProductType'] = '';
                $insuranceInfo[$counter]['Value']['PlanName'] = '';
                $insuranceInfo[$counter]['Value']['PaymentMode'] =  '';
                $insuranceInfo[$counter]['Value']['PolNumber'] = '';
                $insuranceInfo[$counter]['Value']['PaymentAmt'] = '';
                $insuranceInfo[$counter]['Value']['CashValueAmt']  = '';
                $insuranceInfo[$counter]['Value']['DeathBenifitAmt']  = '';
            }
            else
            {
                $counterRowData = 0;
                $xml=simplexml_load_string($insuranceValues);
                foreach($xml->Holding as $xmlHolding)
        	    {
        	        
        	        $xmlProductTypeArray = (array) $xmlHolding->Policy->ProductType;
        		    $plan_description_Type_Array = (array) $xmlHolding->Policy->PlanName;
        		    $plan_payMode_Array = (array) $xmlHolding->Policy->PaymentMode;
        		    $xmlPolNumbergArray = (array) $xmlHolding->Policy->PolNumber;
        		    $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
        		    $xmlCashValueAmt = (array) $xmlHolding->Policy->Life->CashValueAmt;
        		    $xmlDeathBenefitAmt = (array) $xmlHolding->Policy->Life->DeathBenefitAmt;
        		    $xmlProductType = (array) $xmlHolding->Policy->ProductType;
        		    $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
                    
                    $subItems = DB::select("SELECT income_expense_id,item_name,income_expense_type_items.id as id,income_expense_name FROM `income_expense_type_items` 
        		    INNER JOIN income_expense_type ON income_expense_type.id = income_expense_type_items.income_expense_id 
        		    WHERE`income_expense_type_items`.`item_name` LIKE '".$xmlProductTypeArray[0]."'");
                     
                    $income_expense_id =  $subItems[0]->income_expense_id;
                    $item_name =  $subItems[0]->item_name;
                    $id =  $subItems[0]->id;
                    $income_expense_name =  $subItems[0]->income_expense_name;
                    
        	        $insuranceInfo[$counter][$counterRowData]['Value']['ProductType'] = $xmlProductTypeArray[0];
                    $insuranceInfo[$counter][$counterRowData]['Value']['PlanName'] = $plan_description_Type_Array[0];
                    $insuranceInfo[$counter][$counterRowData]['Value']['PaymentMode'] = $plan_payMode_Array[0];
                    $insuranceInfo[$counter][$counterRowData]['Value']['PolNumber'] = $xmlPolNumbergArray[0];
                    $insuranceInfo[$counter][$counterRowData]['Value']['PaymentAmt'] = isset($xmlPaymentAmt[0]) ? (string) $xmlPaymentAmt[0]  : "";
                    $insuranceInfo[$counter][$counterRowData]['Value']['CashValueAmt'] = isset($xmlCashValueAmt[0]) ? (string) $xmlCashValueAmt[0]  : "";
                    $insuranceInfo[$counter][$counterRowData]['Value']['DeathBenifitAmt'] = isset($xmlDeathBenefitAmt[0]) ? (string) $xmlDeathBenefitAmt[0]  : "";
                    $insuranceInfo[$counter][$counterRowData]['Value']['income_expense_id'] = $income_expense_id;
                    $insuranceInfo[$counter][$counterRowData]['Value']['item_name'] = $item_name;
                    $insuranceInfo[$counter][$counterRowData]['Value']['id'] = $id;
                    $insuranceInfo[$counter][$counterRowData]['Value']['income_expense_name'] = $income_expense_name;
                    $counterRowData++;
        	    }
            }
            
            $counter++;
        }
         print_r($insuranceInfo);
        if (!empty($insuranceInfo) && sizeof($insuranceInfo) > 0) {
            foreach ($insuranceInfo as $key => $insuranceData) {
                 foreach($insuranceData as $newKey => $insurData)
               {
                   
                  $ProductType = isset($insurData['ProductType']) ? $insurData['ProductType']  : "";
                   $PlanName = isset($insurData['PlanName']) ? $insurData['PlanName']  : "";
                   $PaymentMode = isset($insurData['PaymentMode']) ? $insurData['PaymentMode']  : "";
                   $PolNumber = isset($insurData['PolNumber']) ? $insurData['PolNumber']  : "";
                   $PaymentAmt = isset($insurData['PaymentAmt']) ? $insurData['PaymentAmt']  : 0;
                   $CashValueAmt = isset($insurData['CashValueAmt']) ? $insurData['CashValueAmt']  : 0;
                   $DeathBenifitAmt = isset($insurData['DeathBenifitAmt']) ? $insurData['DeathBenifitAmt']  : 0;
                    if(!empty($PolNumber))
                    {
                        $insuranceId = DB::table('client_insurances')->insert(
                    [
                        'advisor_id' => $userId,
                        'capture_advisor_id' => $userId,
                        'client_type'=>$client_type,
                        'client_reference_id' => $client_reference_id,
                        'life_cover_description' => $PlanName,
                        'life_cover_policy_ref' => $PolNumber,
                        'life_cover_owner' => '',
                        'life_cover_assured' =>'',
                        'life_cover_cash_value' => $CashValueAmt,
                        'life_cover_death' => $DeathBenifitAmt,
                        'life_cover_disability' => 0,
                        'life_cover_dread_disease' => 0,
                        'life_cover_impairment' => 0,
                        'sick_description' => '',
                        'sick_monthly_amount' => 0,
                        'sick_frequency' => '',
                        'sick_waiting_period' => '',
                        'sick_term' => '',
                        'sick_claim_escalation' => '',
                        'sick_esc' => 0,
                        'income_protect_description' => '',
                        'income_protect_monthly_amount' => 0,
                        'income_protect_frequency' => '',
                        'income_protect_waiting_period' => '',
                        'income_protect_term' => '',
                        'income_protect_claim_escalation' => '',
                        'income_protect_esc' => 0,
                        'death_term_related_to' => '',
                        'death_description' => '',
                        'death_date_of_birth' => '',
                        'death_waiting_period' => '',
                        'death_term' => '',
                        'death_claim_escalation' => 0,
                        'death_esc' => 0,
                        'insurance_allocation_for_death' => 0,
                        'insurance_allocation_for_disability' => 0,
                        'insurance_allocation_for_dread_disease' => 0,
                        'insurance_allocation_for_impairment' => 0,
                        'insurance_allocation_for_income_benefits' => 0
                        
                    ]);
                    
                    /*insert into the Item_types and */
                    //                     $sqlInsert = DB::insert("INSERT INTO `budget`(
                    //     `id`,
                    //     `client_reference_id`,
                    //     `advisor_id`,
                    //     `client_id`,
                    //     `client_type`,
                    //     `advisor_capture_id`,
                    //     `income_expenses_type`,
                    //     `item_type_id`,
                    //     `item_type`,
                    //     `item_id`,
                    //     `item_name`,
                    //     `item_value`,
                    //     `capture_date`
                    // )
                    // VALUES(
                    //     NULL,
                    //     'fna000000001',
                    //     '1',
                    //     '1',
                    //     'Main Client',
                    //     '1',
                    //     '1',
                    //     '1',
                    //     'Life Insurance',
                    //     '1',
                    //     'Whole Life',
                    //     '455675',
                    //     '2022-08-08 14:52:12'
                    // )");
                    
                    
                    
                    }
                    
               }
            }
        }
       //print_r($insuranceInfo);
       die;
        return \Redirect::route('insuranceList', [$client_reference_id]);
    }
}