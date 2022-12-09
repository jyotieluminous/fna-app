<?php

namespace App\Http\Controllers;

use PDF;
use App\Astute;
use App\User;
use App\Client;
use App\Invoice;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\PaymentGatewayConfig;
use App\Exports\PaymentExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StorePaymentConfig;
// use DB;
use DataTables;

use DB;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use URL;

use DateTime;

class InsuranceController extends Controller
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
        $query = DB::select("SELECT * FROM client_insurances"); 
        return view('insurance.insuranceList',['client_insurance'=>$query,'clients'=>$clients]);
    }

    public function indexList($client_reference_id, $client_type)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."' AND client_type = '".$client_type."'"); 
        $providers = DB::table('providers')->get();

        /*$client_bank_name = DB::select("SELECT * FROM `user_bank` WHERE `user_id` = '".$client_reference_id."' ");*/
        $client_bank_name = DB::select("SELECT * FROM `user_bank`");
        $bank_name = $client_bank_name[0]->bank_name  ?? "0";
        /**
         * Fetch the Additional buy details data for Vehicle, property
         * and CPB assets and CPB liabilities to show the data in the popup
         */
        $features_details = DB::table('extra_purchase')     
                                ->where('p_id','7') // ['Astute Insurance'])
                                ->get();
        
        /**
         * Below statements will check for orders table vehicle count 
         * with the api hit details for count of api_id =2 and api_title = Vehicle
         * if orders_count_of_vehicles == count_of_vehicles then
         * vehicle_count  = 1 , Client has to buy new vehicle import from lightstone
         * else
         * vehicle_count = 0 , Client can do the import from lightstone
         */
        $orders_count = DB::table('orders')     
                                ->selectRaw('package_id, order_astute_count, expiry_date_time')   
                                ->where('user_id', $client_reference_id)
                                ->orderBy('id','DESC')
                                ->first();
        // dd($orders_count);
        $package_purchased = 'no';
        $orders_astute_count = 0;
        $astute_count = 0;
        $package_id = 0;
        if(isset($orders_count)) {
            $package_purchased = 'yes';
            $orders_astute_count = $orders_count->order_astute_count;

            $package_id = $orders_count->package_id;
            $current_date_time= date("Y-m-d h:i");
            $expiry_date_time = date('Y-m-d h:i', strtotime($orders_count->expiry_date_time));
            if($current_date_time >= $expiry_date_time)
            {   
                $package_purchased = "expire";
            }
        } else {
            $package_purchased = 'no';
        }

        $form_data_astute = [];        
        /**
         * Common variables for form
         * Preparing variables with values to be passed in array for paymentgateway purchase
         */
        $main_client = DB::table('clients')
                ->where('client_reference_id', $client_reference_id)
                ->where('client_type', $client_type)
                ->first(['first_name', 'last_name', 'email']);

        $first_name = isset($main_client->first_name) ? $main_client->first_name : '-';
        $last_name = isset($main_client->last_name) ? $main_client->last_name : '-';
        $clientName = $first_name." ".$last_name;
        $email = isset($main_client->email) ? $main_client->email : '-';
        /**
         * Payfast related variables
         */
        $testingMode = true;
        $pfHost = '';
        $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $passphrase = 'dMBhsDvcn2Q0oRd';//jt7NOE43FZPn
 
        /**
         * Below code will check whether to chow buy additional purchase of vehicles for the client or not
         * If orders_count_of_vehicles < count(api_hit_details where id = 7 and type = 'Astute Insurance' and client_reference_id = 'current_user client_reference_id')
         * Show buy new Additional purchase
         * else
         * he can import from Lightstone vehicle
         * end
         */
        $count_of_astutes = DB::table('api_hit_details')
                                ->where('client_reference_id', $client_reference_id)
                                ->where('api_name','Astute Insurance')
                                ->where('api_id','7')
                                ->count();   
            if($orders_astute_count <= $count_of_astutes) {
            $astute_count = 1; //Buy new vehicle import
            if(isset($features_details)) {
                // print_r($features_details);
                foreach($features_details as $feature) {
                    if($feature->title == 'Astute Insurance') {
                        $feature_name = 'Astute Insurance';
                        $feature_id = $feature->p_id;
                        $feature_price = ($feature->markup *  $feature->count) ;

                        $cartTotal = $feature_price; // This amount needs to be sourced from your application
                        // use within single line code
                        $orders = DB::table('order_features')->count();
                        $orderNum = IdGenerator::generate(['table' => 'order_features', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
                        $orderNumer = $orderNum.$orders;
                        $payment_id = $package_id.'_'.$feature_id; //0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
                        $form_data_astute = array(
                            // Merchant details
                            'merchant_id' => '10027202',
                            'merchant_key' => 'zbn6mb2wba5ik',
                            'return_url' => URL::to('/successAstutePayment'),
                            'cancel_url' => URL::to('/cancelAstutePayment'),
                            'notify_url' => URL::to('/notifyAstutePayment'),
                            // Buyer details
                            'name_first' => $first_name,
                            'name_last'  => $last_name,
                            'email_address'=> $email,
                            // Transaction details
                            'm_payment_id' => $payment_id, //Unique payment ID to pass through to notify_url
                            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
                            'item_name' => 'Order#'.$orderNumer,
                            'item_description' => $feature_name,
                        );
                        $signature = $this->generateSignature($form_data_astute, $passphrase);
                        $form_data_astute['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za

                        // dd($form_data_vehicle);
                        Session::put('productAstuteData', $form_data_astute);
                        Session::put('productAstuteData', $form_data_astute);
                        session()->put('productAstuteData.client_reference_id', $client_reference_id);
                        session()->put('productAstuteData.client_type', $client_type);
                        session()->put('productAstuteData.clientName', $clientName);
                        session()->put('productAstuteData.featureName', $feature_name);
                        session()->put('productAstuteData.featureId', $feature_id);
                    }
                }
            }
        } elseif($orders_astute_count > $count_of_astutes) {
            $astute_count = 0; //do the vehicle import
        } 
        /**end here */        
        

        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        return view('insurance.insuranceListView',[
            'features_details' => $features_details,
            'astute_count' => $astute_count,
            'pfHost' => $pfHost,
            'form_data_astute' => $form_data_astute,
            'client_owners' => $client_owners, 'package_purchased' => $package_purchased, 'providers' => $providers, 'client_insurance'=>$query,'client_reference_id'=>$client_reference_id,'client_type'=>$client_type,'bank_name'=>$bank_name]);
    }
    
    public function generateSignature($data, $passPhrase = null) {
        // Create parameter string
        $pfOutput = '';
        foreach( $data as $key => $val ) {
            if($val !== '') {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        }
        // Remove last ampersand
        $getString = substr( $pfOutput, 0, -1 );
        if( $passPhrase !== null ) {
            $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
        }
        return md5( $getString );
    }

    public function createAstuteInsuranceReadFromXMLFile($client_reference_id,$client_type)  
    {
        ini_set('memory_limit', '256M');
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        /*fetching Client id*/
        $getClientId = DB::select("SELECT id  FROM `clients` WHERE `client_type` = '".$client_type."' AND `client_reference_id` = '".$client_reference_id."'");  
        if(isset($getClientId))
        {
                $clientID = $getClientId[0]->id;
        }

           //echo $path = storage_path('public/xml/' . '1-p.xml');
        //   echo public_path('storage/xml/d4eca59a-b568-47c4-b929-d989a13f846e.xml');die;
        echo $path = public_path("/storage/xml/2.xml");
        $fileData = file_get_contents($path);
        // $fileData = file_get_contents("https://fna2.phpapplord.co.za/public/storage/xml/1.xml");
        $tata=simplexml_load_string($fileData) or die("Error: Cannot create object");
        // die;
        $providerNames = array(
                'LIBL' => 'Liberty Group Limited',
                'OMUL' => 'Old Mutual Limited',
                'ABSAL' => 'Old Mutual Limited',
                'AGL' => 'Old Mutual Limited',
                'ALTL' => 'Old Mutual Limited',
                'CHTL' => 'Old Mutual Limited',
                'DSIL' => 'Old Mutual Limited',
                'DSLL' => 'Old Mutual Limited',
                'METL' => 'Old Mutual Limited',
                'MOML' => 'Old Mutual Limited',
                'MOMWL' => 'Old Mutual Limited',
                'NGLL' => 'Old Mutual Limited',
                'OMGPL' => 'Old Mutual Limited',
                'OUTL' => 'Old Mutual Limited',
                'PPSL' => 'Old Mutual Limited',
                'SETL' => 'Old Mutual Limited',
                'SLML' => 'Old Mutual Limited',
                'SLMNAL' => 'Old Mutual Limited',
                'STLBL' => 'Old Mutual Limited',
                'FMIL' => 'Old Mutual Limited',
                'MOMEL' => 'Old Mutual Limited'
            );
        echo"<pre>";
        //  print_r($tata->WebMessages);die;
        $counterStatic = 0;
        $insuranceInfoStatic = array();
        $message = "";
        $errorCount = 0;
        foreach($tata->WebMessages->Content as $key => $value)
                {
                      print_r($value);
                    echo "<br/>". $key. "=";
                    $oLife = "<OLifE>";
                    $insuranceInfoStatic[$counterStatic]['ContentSchemaRef'] =  (string) $value['ContentSchemaRef'];
                    $insuranceInfoStatic[$counterStatic]['ProviderCode'] =  (string) $value['ProviderCode'];
                    $insuranceValues = $value->Value;
                    // echo "<br/>ProviderName = ". (string) $value['ProviderName'];
                    echo "<br/>ProviderCode = ". (string) $value['ProviderCode'];
                    // echo "<br/>MessageID = ". (string) $value['MessageID'];

                    foreach($value as $olife){
                        // echo "<br/> olifeset = ";
                        // echo print_r($olife->Holding);
                        $counterRowData = 0;
                        foreach($olife->Holding as $xmlHolding)
                	    {
                	         $xmlProductTypeArray = (array) $xmlHolding->Policy->ProductType;
                	        //var_dump($xmlHolding);
                	       // dd();
                	       if($xmlHolding->Policy->PolicyStatus =="Active")
                	       {
                            echo "yes i m active";
                                $plan_description_Type_Array = (array) $xmlHolding->Policy->PlanName;
                    		    $plan_payMode_Array = (array) $xmlHolding->Policy->PaymentMode;
                    		    $xmlPolNumbergArray = (array) $xmlHolding->Policy->PolNumber;
                    		    $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
                    		    if(isset($xmlHolding->Policy->Life->CashValueAmt)) {
                    		        $xmlCashValueAmt = (array) $xmlHolding->Policy->Life->CashValueAmt;
                    		    } else if(isset($xmlHolding->Investment->AccountValue)) {
                    		         $xmlCashValueAmt = (array) $xmlHolding->Investment->AccountValue;
                    		    }
                    		    else {
                    		        $xmlCashValueAmt = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->Life->DeathBenefitAmt)) {
                    		        $xmlDeathBenefitAmt = (array) $xmlHolding->Policy->Life->DeathBenefitAmt;
                    	        } else {
                    		        $xmlDeathBenefitAmt = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->ProductType)) {
                    		        $xmlProductType = (array) $xmlHolding->Policy->ProductType;
                    		    } else {
                    		        $xmlProductType = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->PaymentAmt)) {
                    		        $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
                    		    } else {
                    		        $xmlPaymentAmt = [];
                    		    }
                    		    
                    		    // Added on 15-nov
                    		    if(isset($xmlHolding->Policy->EffDate)) {
                    		        $xmlEffDate = (array) $xmlHolding->Policy->EffDate;
                    		    } else {
                    		        $xmlEffDate = [];
                    		    }
                    		    // Added on 15-nov
                    		    if(isset($xmlHolding->Policy->TermDate)) {
                    		        $xmlTermDate = (array) $xmlHolding->Policy->TermDate;
                    		    } else {
                    		        $xmlTermDate = [];
                    		    }                    		    
                    		    
                    		    if(isset($xmlHolding->Policy->Life->Coverage)) {
                    		        $xmlPaymentCoverage = (array) $xmlHolding->Policy->Life->Coverage;
                    		    } else {
                    		        $xmlPaymentCoverage = [];
                    		    }
                                // [AnnualIndexType] => CPI
                                // [CoverIndexRate] => 10.2
                                if(isset($xmlHolding->Policy->Life->AnnualIndexType)) {
                    		        $xmlAnnualIndexType = (array) $xmlHolding->Policy->Life->AnnualIndexType;
                    		    } else {
                    		        $xmlAnnualIndexType = [];
                    		    }

                                if(isset($xmlHolding->Policy->Life->CoverIndexRate)) {
                    		        $xmlCoverIndexRate = (array) $xmlHolding->Policy->Life->CoverIndexRate;
                    		    } else {
                    		        $xmlCoverIndexRate = [];
                    		    }                                


                                //DeathBenefitAmt
                                //Life--Coverage--DeathBenefitAmt
                               
                                //DisabilityAmt  -- 24
                               //OLifE -- Holding -- Policy-- Life --Coverage
                                //DreadDiseaseAmt  --16
                            // echo "<pre>";
                            //  print_r($xmlHolding->Policy->Life->Coverage);

                            if(isset($xmlHolding->Policy->Life->Coverage)) {
                                foreach($xmlHolding->Policy->Life->Coverage as $diabilityLoop) {
                                   echo "<pre><br /> life code ==". $diabilityLoop->LifeCovTypeCode['tc'];//die;
                                   if(isset($LifeCovTypeCode)) {
                                    $LifeCovTypeCode = DB::select("SELECT * FROM `coveragetype` where  `Code Value` = '".$diabilityLoop->LifeCovTypeCode['tc']."' ");
                                    //    dd($LifeCovTypeCode);
                                           if(isset( $LifeCovTypeCode))
                                           {
                                            echo $LifeCovTypeCode[0]->Description;//die;
                                            print_r($LifeCovTypeCode);
                                               if (str_contains(strtoupper($LifeCovTypeCode[0]->Description), 'DISABILITY')) {
                                                    $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DisabilityAmt'] = (string) $diabilityLoop->CurrentAmt;
                                                    $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DisabilityDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                                    $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DisabilityEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                                    $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DisabilityPaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                                    $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DisabilityPaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';
                                               }
                                               elseif (str_contains($LifeCovTypeCode[0]->Description, 'Dread')) 
                                               {
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DreadDiseaseAmt'] = (string)  $diabilityLoop->CurrentAmt;
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DreadDiseaseDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DreadDiseaseEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DreadDiseasePaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DreadDiseasePaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';                                            
                                                   
                                               }
                                                 elseif (str_contains($LifeCovTypeCode[0]->Description, 'illness')) 
                                               {
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['IllnessAmt'] = (string)  $diabilityLoop->CurrentAmt;
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['IllnessDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['IllnessEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['IllnessPaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                                   $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['IllnessPaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';                                             
                                               }
       
                                           }
                                   }

                                   }
                               }
                                                               
                                        // echo "<pre>";
// print_r($insuranceInfoStatic[$counter]);
                        //         $subItems = DB::select("SELECT income_expense_id,item_name,income_expense_type_items.id as id,income_expense_name FROM `income_expense_type_items` 
                    		  //  INNER JOIN income_expense_type ON income_expense_type.id = income_expense_type_items.income_expense_id 
                    		  //  WHERE`income_expense_type_items`.`item_name` LIKE '".$xmlProductTypeArray[0]."'");
                              $subItems = DB::select("SELECT id,income_expense_type,income_expense_name  FROM `income_expense_type` WHERE `income_expense_name` LIKE 'Life Insurance'");       		    
                    		    
                              if(count($subItems) > 0) { 
                                  $income_expense_id =  $subItems[0]->id;
                                  $item_name = $subItems[0]->income_expense_name;//$subItems[0]->item_name;
                                  $id = "0";//$subItems[0]->id;
                                  $income_expense_name =  isset($plan_description_Type_Array[0]) ? $plan_description_Type_Array[0]  : "";
                              }
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['ProductType'] = isset($xmlProductTypeArray[0]) ? $xmlProductTypeArray[0] : "";
                              if(isset($plan_description_Type_Array[0])) {
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['PlanName'] = $plan_description_Type_Array[0];
                              }
                              if(isset($plan_payMode_Array[0])) {
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['PaymentMode'] = $plan_payMode_Array[0];
                              }
                              if(isset($xmlPolNumbergArray[0])) {
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['PolNumber'] = $xmlPolNumbergArray[0];
                              }
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['PaymentAmt'] = isset($xmlPaymentAmt[0]) ? (string) $xmlPaymentAmt[0]  : "";
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['CashValueAmt'] = isset($xmlCashValueAmt[0]) ? (string) $xmlCashValueAmt[0]  : "";
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['DeathBenifitAmt'] = isset($xmlDeathBenefitAmt[0]) ? (string) $xmlDeathBenefitAmt[0]  : "";
                                                            
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['AnnualIndexType'] = isset($xmlAnnualIndexType[0]) ? (string) $xmlAnnualIndexType[0]  : "";
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['CoverIndexRate'] = isset($xmlCoverIndexRate[0]) ? (string) $xmlCoverIndexRate[0]  : "";

                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['EffDate'] = isset($xmlEffDate[0]) ? (string) $xmlEffDate[0]  : "";
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['TermDate'] = isset($xmlTermDate[0]) ? (string) $xmlTermDate[0]  : "";                    		    
                              if($insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['EffDate'] != '' && $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['TermDate'] != '' ) {
                                  $bday = new DateTime($insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['EffDate']); // Your date of birth
                                  $today = new Datetime($insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['TermDate']);
                                  $diff = $today->diff($bday);
                                  // printf(' Your age : %d years, %d month, %d days', $diff->y, $diff->m, $diff->d);
                                  // printf("\n");
                                  if($diff->y > 0) {
                                      $termsData = $diff->y . ' years';
                                  } elseif($diff->m > 0) {
                                      $termsData = $diff->m . ' months';
                                  } elseif($diff->m > 0) {
                                      $termsData = $diff->d . ' days';
                                  }
                                  $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['term'] = $termsData;
 
                              } else {
                                  $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['term'] = '';
                              }

                              
                              
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['income_expense_id'] = $income_expense_id;
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['item_name'] = $item_name;
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['id'] = $id;
                              $insuranceInfoStatic[$counterStatic]['Astute'.$counterRowData]['income_expense_name'] = $income_expense_name;
                              $counterRowData++;
                            }
                           else
                           {
                            echo "i m not active";
                           }
                        }
                    }
                    $counterStatic++;
    }
 
// print_r($insuranceInfoStatic);
        // die;
        if (!empty($insuranceInfoStatic) && sizeof($insuranceInfoStatic) > 0) {
            foreach ($insuranceInfoStatic as $key => $insuranceData) {
               
              foreach($insuranceData as $newKey => $insurData)
               {
                   
                   if(isset($insuranceData))
                   {
                    //   print_r($insurData);
                   }
                    $PaymentAmt = 0.00;
                    $ProductType = isset($insurData['ProductType']) ? $insurData['ProductType']  : "";
                    $PlanName = isset($insurData['PlanName']) ? $insurData['PlanName']  : $ProductType;
                    $PaymentMode = isset($insurData['PaymentMode']) ? $insurData['PaymentMode']  : "";
                    $PolNumber = isset($insurData['PolNumber']) ? $insurData['PolNumber']  : "";
                    $PaymentAmt = isset($insurData['PaymentAmt']) && !empty($insurData['PaymentAmt']) ? $insurData['PaymentAmt']  : 0.00;
                    $term = isset($insurData['term']) ? $insurData['term']  : "";
                    $EffDate = isset($insurData['EffDate']) ? $insurData['EffDate']  : "";
                    $CashValueAmt = isset($insurData['CashValueAmt']) ? $insurData['CashValueAmt']  : 0;
                    $DeathBenifitAmt = isset($insurData['DeathBenifitAmt']) ? $insurData['DeathBenifitAmt']  : 0;
                    
                    $AnnualIndexType = isset($insurData['AnnualIndexType']) ? $insurData['AnnualIndexType']  : '';
                    $CoverIndexRate = isset($insurData['CoverIndexRate']) ? $insurData['CoverIndexRate']  : 0;


                    $DisabilityAmt = isset($insurData['DisabilityAmt']) ? $insurData['DisabilityAmt']  : 0;
                    $DreadDiseaseAmt = isset($insurData['DreadDiseaseAmt']) ? $insurData['DreadDiseaseAmt']  : 0;
                    $IllnessAmt= isset($insurData['IllnessAmt']) ? $insurData['IllnessAmt']  : 0;
                    
                    
                    $DisabilityDesc = isset($insurData['DisabilityDesc']) ? $insurData['DisabilityDesc']  : '';
                    $DreadDiseaseDesc = isset($insurData['DreadDiseaseDesc']) ? $insurData['DreadDiseaseDesc']  : ''; 
                    $IllnessDesc = isset($insurData['IllnessDesc']) ? $insurData['IllnessDesc']  : '';
                    
                    $DisabilityEffDate = isset($insurData['DisabilityEffDate']) ? $insurData['DisabilityEffDate']  : '';
                    $DreadDiseaseEffDate = isset($insurData['DreadDiseaseEffDate']) ? $insurData['DreadDiseaseEffDate']  : ''; 
                    $IllnessEffDate = isset($insurData['IllnessEffDate']) ? $insurData['IllnessEffDate']  : '';
                    
                    $DisabilityPaymentMode = isset($insurData['DisabilityPaymentMode']) ? $insurData['DisabilityPaymentMode']  : '';
                    $DreadDiseasePaymentMode = isset($insurData['DreadDiseasePaymentMode']) ? $insurData['DreadDiseasePaymentMode']  : ''; 
                    $IllnessPaymentMode = isset($insurData['IllnessPaymentMode']) ? $insurData['IllnessPaymentMode']  : '';
                    
                    $DisabilityPaymentAmt = isset($insurData['DisabilityPaymentAmt']) ? $insurData['DisabilityPaymentAmt']  : '';
                    $DreadDiseasePaymentAmt = isset($insurData['DreadDiseasePaymentAmt']) ? $insurData['DreadDiseasePaymentAmt']  : ''; 
                    $IllnessPaymentAmt = isset($insurData['IllnessPaymentAmt']) ? $insurData['IllnessPaymentAmt']  : '';
                    
                    
                    
                   $income_expense_id = isset($insurData['income_expense_id']) ? $insurData['income_expense_id'] : 0;
                   $item_name = isset($insurData['item_name']) ? $insurData['item_name'] : "";
                   $id = isset($insurData['id']) ? $insurData['id'] : 0;
                   $income_expense_name = isset($insurData['income_expense_name']) ? $insurData['income_expense_name'] : " ";
                   if(  $PlanName != "Term")
                   {
                        if(!empty($PolNumber))
                        {
                            $insurance_count = DB::table('client_insurances')
                                ->where('life_cover_description', $PlanName)
                                ->where('life_cover_policy_ref', $PolNumber)
                                ->where('client_reference_id', $client_reference_id)
                                ->count();
                            if($insurance_count == 0) {
                                    $insuranceId = DB::table('client_insurances')->insert(
                                    [
                                        'advisor_id' => $userId,
                                        'capture_advisor_id' => $userId,
                                        'client_type'=>$client_type,
                                        'client_reference_id' => $client_reference_id,
                                        'life_cover_description' => $PlanName,
                                        'life_cover_policy_ref' => $PolNumber,
                                        'life_cover_owner' => '',
                                        'life_cover_assured' =>$PaymentAmt,
                                        'life_cover_cash_value' => $CashValueAmt,
                                        'life_cover_death' => $DeathBenifitAmt,
                                        'life_cover_disability' => $DisabilityAmt,
                                        'life_cover_dread_disease' => $DreadDiseaseAmt,
                                        'life_cover_impairment' => 0,
                                        'sick_description' => (isset($IllnessDesc) && !empty($IllnessDesc)) ? $IllnessDesc : $PlanName,
                                        'sick_monthly_amount' => (isset($IllnessAmt) && $IllnessAmt != 0)  ? $IllnessAmt : $PaymentAmt,
                                        'sick_frequency' => (isset($IllnessPaymentMode) && $IllnessPaymentMode != 0)  ? $IllnessPaymentMode : $PaymentMode,
                                        'sick_waiting_period' => '',
                                        'sick_term' => $term,
                                    'sick_claim_escalation' => (isset($AnnualIndexType) && $AnnualIndexType  != 0)  ? $AnnualIndexType  : '',
                                    'sick_esc' => (isset($CoverIndexRate) && $CoverIndexRate != 0)  ? $CoverIndexRate : 0,
                                    'income_protect_description' => $PlanName,
                                    'income_protect_monthly_amount' => $PaymentAmt,
                                    'income_protect_frequency' => $PaymentMode,
                                    'income_protect_waiting_period' => '',
                                    'income_protect_term' => $term,
                                    'income_protect_claim_escalation' => '',
                                    'income_protect_esc' => 0,
                                    'death_term_related_to' => (isset($DreadDiseaseDesc) && !empty($DreadDiseaseDesc)) ? $DreadDiseaseDesc : $PlanName,
                                    'death_description' => (isset($DreadDiseaseDesc) && !empty($DreadDiseaseDesc))  ? $DreadDiseaseDesc : $PlanName,
                                    'death_date_of_birth' => isset($DreadDiseaseEffDate) ? $DreadDiseaseEffDate : $EffDate,
                                    'death_waiting_period' => '',
                                    'death_term' => $term,
                                    'death_claim_escalation' => (isset($AnnualIndexType) && $AnnualIndexType  != 0)  ? $AnnualIndexType  : '',
                                    'death_esc' => (isset($CoverIndexRate) && $CoverIndexRate != 0)  ? $CoverIndexRate : 0,
                                    'insurance_allocation_for_death' => 0,
                                    'insurance_allocation_for_disability' => 0,
                                    'insurance_allocation_for_dread_disease' => 0,
                                    'insurance_allocation_for_impairment' => 0,
                                    'insurance_allocation_for_income_benefits' => 0
                                    ]);
                                /*insert into the Item_types and */
                                $budgetId = DB::table('budget')->insertGetId(
                                            [
                                                'client_reference_id' => $client_reference_id,
                                                'advisor_id' => $userId,
                                                'client_id' => $clientID,
                                                'client_type' => $client_type,
                                                'advisor_capture_id' => '1',
                                                'income_expenses_type' => '2',
                                                'item_type_id' => $income_expense_id,
                                                'item_type' => $item_name,
                                                'item_id' => $id,
                                                'item_name' => $income_expense_name,
                                                'item_value' => $PaymentAmt,
                                                'capture_date' => now()
                                            ]);
                                $currentYear = date("Y");
                                $budgetId = 0;
                                for($month=1; $month<=12 ; $month++) {
                                    DB::table('yearly_budget')->insert([
                                        'client_reference_id' => $client_reference_id,
                                        'advisor_id' => $userId,
                                        'advisor_capture_id' => $userId,
                                        'budget_id' => $budgetId,
                                        'client_id' => $clientID,
                                        'client_type' => $client_type,
                                        'income_expenses_type' => '2',
                                        'item_type_id' => $income_expense_id,
                                        'item_type' => $item_name,
                                        'item_id' => $id,
                                        'item_name' => $income_expense_name,
                                        'item_value' => $PaymentAmt,
                                        'capture_date' => now(),
                                        'month' => $month,
                                        'year' => $currentYear
                                    ]);
                                }
                                $message =  "Insurance populated via Astute!";// for '". $PlanName ."'!";
                            } else {
                                $message = "Insurance already populated via Astute!"; // for '". $PlanName ."'! '";
                            }
                        } else {
                            //$message = 'Insurance policy number is empty! ';
                        }
                   }
               }
            }
        } else {
            $message = 'No data is returned by Astute! ';
        }
        // die($message);
        session()->flash('success', $message);
        return \Redirect::route('insuranceList', [$client_reference_id, $client_type ]);
    }

    
    public function  createAstuteInsurance($client_reference_id,$client_type)  
    {
        ini_set('memory_limit', '256M');
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        /*fetching Client id*/
        $getClientId = DB::select("SELECT id  FROM `clients` WHERE `client_type` = '".$client_type."' AND `client_reference_id` = '".$client_reference_id."'");  
        if(isset($getClientId))
        {
                $clientID = $getClientId[0]->id;
        }
        /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 7)
                            ->where('title', 'Astute Insurance')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'Astute Insurance';
        }   
  

        $newAstute = new Astute("Abrie89", "Applord@1223!!!!!", "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC");
        
        // $MessageGuidId = "bc77d8ee-c532-4fa5-bcb1-02a972ee0ded"; //"5dcb8815-cb83-44ff-bf05-ec7001a14c63";  
        
        
        $getWebCCPRequest = $newAstute->CCPRequest($client_reference_id,$client_type);
        $CCPRequest = json_decode($getWebCCPRequest);
        // var_dump($getWebCCPRequest);
        // echo "<pre> GUIDID created ";
        // print_r($CCPRequest);
        // die;
        $message = '';
        if( !is_string($CCPRequest) ) {
            $message = 'Guid id is not getting generated.';
            session()->flash('success', $message);
            return \Redirect::route('insuranceList', [$client_reference_id, $client_type ]);  
        }
        DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($CCPRequest),
            'Type' => 'Astute'
            ]);
    

        $getWebMessageContent = $newAstute->MessageContent($CCPRequest);

        // print_r($getWebMessageContent); die();
        
        $tata = json_decode($getWebMessageContent);
        
        //  var_dump($tata);
        //  die;
        DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($CCPRequest) . ',' .json_encode($getWebMessageContent),
            'Type' => 'Astute'
            ]);
        // var_dump($tata); die();
        DB::table('api_hit_details')->insert([
            'id' => null,
            'client_id' => $clientID,
            'client_reference_id' => $client_reference_id,
            'client_type' => $client_type,
            'api_name' => $api_title,
            'api_id' => $api_id,
            'consumed_date' => DB::raw('now()'),
             'jsonobject' => json_encode($CCPRequest) . ',' .json_encode($getWebMessageContent)
        ]); 
         
        $providerNames = array(
                'LIBL' => 'Liberty Group Limited',
                'OMUL' => 'Old Mutual Limited',
                'ABSAL' => 'Old Mutual Limited',
                'AGL' => 'Old Mutual Limited',
                'ALTL' => 'Old Mutual Limited',
                'CHTL' => 'Old Mutual Limited',
                'DSIL' => 'Old Mutual Limited',
                'DSLL' => 'Old Mutual Limited',
                'METL' => 'Old Mutual Limited',
                'MOML' => 'Old Mutual Limited',
                'MOMWL' => 'Old Mutual Limited',
                'NGLL' => 'Old Mutual Limited',
                'OMGPL' => 'Old Mutual Limited',
                'OUTL' => 'Old Mutual Limited',
                'PPSL' => 'Old Mutual Limited',
                'SETL' => 'Old Mutual Limited',
                'SLML' => 'Old Mutual Limited',
                'SLMNAL' => 'Old Mutual Limited',
                'STLBL' => 'Old Mutual Limited',
                'FMIL' => 'Old Mutual Limited',
                'MOMEL' => 'Old Mutual Limited'
            );
            
        $counter = 0;
        $insuranceInfo = array();
        $message = '';
        $errorCount = 0;
        if (is_array($tata->Result->Data) || is_object($tata->Result->Data)) {
            foreach($tata->Result->Data as $Dataprovider)
             {
                 
                foreach($Dataprovider->MessageBody as $provider)
                {
                      
                    $oLife = "<OLifE>";
                    $insuranceInfo[$counter]['ContentSchemaRef'] = $provider->ContentSchemaRef;
                    $insuranceInfo[$counter]['providerCode'] = $provider->ProviderCode;
                    $insuranceValues = $provider->Value;
                    $checkProvider = (strpos($insuranceValues, $oLife) !== false) ? true : false;
                    $insuranceInfo[$counter]['providerCode'] = $checkProvider;
                    if(!$checkProvider)
                    {
                        $errorCount ++;

                        $insuranceInfo[$counter]['Value']['ProductType'] = '';
                        $insuranceInfo[$counter]['Value']['PlanName'] = '';
                        $insuranceInfo[$counter]['Value']['PaymentMode'] =  '';

                        
                        
                        $insuranceInfo[$counter]['Value']['PolNumber'] = '';
                        $insuranceInfo[$counter]['Value']['PaymentAmt'] = '';
                        $insuranceInfo[$counter]['Value']['CashValueAmt']  = '';
                        
                        $insuranceInfo[$counter]['Value']['DeathBenifitDesc'] = '';
                        $insuranceInfo[$counter]['Value']['DeathBenifitAmt']  = '';
                        $insuranceInfo[$counter]['Value']['DeathBenefitEffDate'] = '';
                        $insuranceInfo[$counter]['Value']['DeathBenefitPaymentMode'] = '';
                        $insuranceInfo[$counter]['Value']['DeathBenefitPaymentAmt'] = '';
                        
                        
                        $insuranceInfo[$counter]['Value']['DreadDiseaseDesc']  = '';
                        $insuranceInfo[$counter]['Value']['DreadDiseaseAmt']  = 0;
                        $insuranceInfo[$counter]['Value']['DreadDiseaseEffDate'] = '';
                        $insuranceInfo[$counter]['Value']['DreadDiseasePaymentMode'] = '';
                        $insuranceInfo[$counter]['Value']['DreadDiseasePaymentAmt'] = '';                        
                        
                        $insuranceInfo[$counter]['Value']['DisabilityDesc']  = '';                        
                        $insuranceInfo[$counter]['Value']['DisabilityAmt']  = 0;
                        $insuranceInfo[$counter]['Value']['DisabilityEffDate'] = '';
                        $insuranceInfo[$counter]['Value']['DisabilityPaymentMode'] = '';
                        $insuranceInfo[$counter]['Value']['DisabilityPaymentAmt'] = '';                        
                    }
                    else
                    {
                        $counterRowData = 0;
                        $termsData = '';
                        $xml=simplexml_load_string($insuranceValues);
                        //dd($xml);
                        foreach($xml->Holding as $xmlHolding)
                	    {
                	         $xmlProductTypeArray = (array) $xmlHolding->Policy->ProductType;
                	        //var_dump($xmlHolding);
                	       // dd();
                	       if($xmlHolding->Policy->PolicyStatus =="Active")
                	       {
                    		    $plan_description_Type_Array = (array) $xmlHolding->Policy->PlanName;
                    		    $plan_payMode_Array = (array) $xmlHolding->Policy->PaymentMode;
                    		    $xmlPolNumbergArray = (array) $xmlHolding->Policy->PolNumber;
                    		    $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
                    		    if(isset($xmlHolding->Policy->Life->CashValueAmt)) {
                    		        $xmlCashValueAmt = (array) $xmlHolding->Policy->Life->CashValueAmt;
                    		    } else if(isset($xmlHolding->Investment->AccountValue)) {
                    		         $xmlCashValueAmt = (array) $xmlHolding->Investment->AccountValue;
                    		    }
                    		    else {
                    		        $xmlCashValueAmt = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->Life->DeathBenefitAmt)) {
                    		        $xmlDeathBenefitAmt = (array) $xmlHolding->Policy->Life->DeathBenefitAmt;
                    	        } else {
                    		        $xmlDeathBenefitAmt = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->ProductType)) {
                    		        $xmlProductType = (array) $xmlHolding->Policy->ProductType;
                    		    } else {
                    		        $xmlProductType = [];
                    		    }
                    		    if(isset($xmlHolding->Policy->PaymentAmt)) {
                    		        $xmlPaymentAmt = (array) $xmlHolding->Policy->PaymentAmt;
                    		    } else {
                    		        $xmlPaymentAmt = [];
                    		    }
                    		    
                    		    // Added on 15-nov
                    		    if(isset($xmlHolding->Policy->EffDate)) {
                    		        $xmlEffDate = (array) $xmlHolding->Policy->EffDate;
                    		    } else {
                    		        $xmlEffDate = [];
                    		    }
                    		    // Added on 15-nov
                    		    if(isset($xmlHolding->Policy->TermDate)) {
                    		        $xmlTermDate = (array) $xmlHolding->Policy->TermDate;
                    		    } else {
                    		        $xmlTermDate = [];
                    		    }                    		    
                    		    
                    		    if(isset($xmlHolding->Policy->Life->Coverage)) {
                    		        $xmlPaymentCoverage = (array) $xmlHolding->Policy->Life->Coverage;
                    		    } else {
                    		        $xmlPaymentCoverage = [];
                    		    }
                    		    
                                //DeathBenefitAmt
                                //Life--Coverage--DeathBenefitAmt
                               
                                //DisabilityAmt  -- 24
                               //OLifE -- Holding -- Policy-- Life --Coverage
                                //DreadDiseaseAmt  --16
                            // echo "<pre>";
                            //  print_r($xmlHolding->Policy->Life->Coverage);

                            if(isset($xmlHolding->Policy->Life->Coverage)) {
                    		 foreach($xmlHolding->Policy->Life->Coverage as $diabilityLoop) {
                                // echo "<pre><br /> life code ==". $diabilityLoop->LifeCovTypeCode['tc'];
                                $LifeCovTypeCode = DB::select("SELECT * FROM `coveragetype` where  `Code Value` = '".$diabilityLoop->LifeCovTypeCode['tc']."' ");
                                    if(isset( $LifeCovTypeCode))
                                    {
                                        if (str_contains(strtoupper($LifeCovTypeCode[0]->Description), 'DISABILITY')) {
                                             $insuranceInfo[$counter]['Astute'.$counterRowData]['DisabilityAmt'] = (string) $diabilityLoop->CurrentAmt;
                                             $insuranceInfo[$counter]['Astute'.$counterRowData]['DisabilityDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                             $insuranceInfo[$counter]['Astute'.$counterRowData]['DisabilityEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                             $insuranceInfo[$counter]['Astute'.$counterRowData]['DisabilityPaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                             $insuranceInfo[$counter]['Astute'.$counterRowData]['DisabilityPaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';
                                        }
                                        elseif (str_contains($LifeCovTypeCode[0]->Description, 'Dread')) 
                                        {
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['DreadDiseaseAmt'] = (string)  $diabilityLoop->CurrentAmt;
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['DreadDiseaseDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['DreadDiseaseEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['DreadDiseasePaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['DreadDiseasePaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';                                            
                                            
                                        }
                                          elseif (str_contains($LifeCovTypeCode[0]->Description, 'illness')) 
                                        {
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['IllnessAmt'] = (string)  $diabilityLoop->CurrentAmt;
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['IllnessDesc'] = (string) $LifeCovTypeCode[0]->Description;
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['IllnessEffDate'] = isset($LifeCovTypeCode[0]->EffDate) ? (string) $LifeCovTypeCode[0]->EffDate : '';
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['IllnessPaymentMode'] = isset($LifeCovTypeCode[0]->PaymentMode) ? (string) $LifeCovTypeCode[0]->PaymentMode : '';
                                            $insuranceInfo[$counter]['Astute'.$counterRowData]['IllnessPaymentAmt'] = isset($LifeCovTypeCode[0]->CurrentAmt) ? (string) $LifeCovTypeCode[0]->CurrentAmt : '';                                             
                                        }

                                    }
                                }
                            }
                                        // echo "<pre>";
// print_r($insuranceInfo[$counter]);
                        //         $subItems = DB::select("SELECT income_expense_id,item_name,income_expense_type_items.id as id,income_expense_name FROM `income_expense_type_items` 
                    		  //  INNER JOIN income_expense_type ON income_expense_type.id = income_expense_type_items.income_expense_id 
                    		  //  WHERE`income_expense_type_items`.`item_name` LIKE '".$xmlProductTypeArray[0]."'");
                                $subItems = DB::select("SELECT id,income_expense_type,income_expense_name  FROM `income_expense_type` WHERE `income_expense_name` LIKE 'Life Insurance'");       		    
                    		    
                                if(count($subItems) > 0) { 
                                    $income_expense_id =  $subItems[0]->id;
                                    $item_name = $subItems[0]->income_expense_name;//$subItems[0]->item_name;
                                    $id = "0";//$subItems[0]->id;
                                    $income_expense_name =  isset($plan_description_Type_Array[0]) ? $plan_description_Type_Array[0]  : "";
                                }
                    	        $insuranceInfo[$counter]['Astute'.$counterRowData]['ProductType'] = isset($xmlProductTypeArray[0]) ? $xmlProductTypeArray[0] : "";
                    	        if(isset($plan_description_Type_Array[0])) {
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['PlanName'] = $plan_description_Type_Array[0];
                    	        }
                    	        if(isset($plan_payMode_Array[0])) {
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['PaymentMode'] = $plan_payMode_Array[0];
                    	        }
                    	        if(isset($xmlPolNumbergArray[0])) {
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['PolNumber'] = $xmlPolNumbergArray[0];
                    	        }
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['PaymentAmt'] = isset($xmlPaymentAmt[0]) ? (string) $xmlPaymentAmt[0]  : "";
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['CashValueAmt'] = isset($xmlCashValueAmt[0]) ? (string) $xmlCashValueAmt[0]  : "";
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['DeathBenifitAmt'] = isset($xmlDeathBenefitAmt[0]) ? (string) $xmlDeathBenefitAmt[0]  : "";
                                
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['EffDate'] = isset($xmlEffDate[0]) ? (string) $xmlEffDate[0]  : "";
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['TermDate'] = isset($xmlTermDate[0]) ? (string) $xmlTermDate[0]  : "";                    		    
                                if($insuranceInfo[$counter]['Astute'.$counterRowData]['EffDate'] != '' && $insuranceInfo[$counter]['Astute'.$counterRowData]['TermDate'] != '' ) {
                                    $bday = new DateTime($insuranceInfo[$counter]['Astute'.$counterRowData]['EffDate']); // Your date of birth
                                    $today = new Datetime($insuranceInfo[$counter]['Astute'.$counterRowData]['TermDate']);
                                    $diff = $today->diff($bday);
                                    // printf(' Your age : %d years, %d month, %d days', $diff->y, $diff->m, $diff->d);
                                    // printf("\n");
                                    if($diff->y > 0) {
                                        $termsData = $diff->y . ' years';
                                    } elseif($diff->m > 0) {
                                        $termsData = $diff->m . ' months';
                                    } elseif($diff->m > 0) {
                                        $termsData = $diff->d . ' days';
                                    }
                                    $insuranceInfo[$counter]['Astute'.$counterRowData]['term'] = $termsData;
   
                                } else {
                                    $insuranceInfo[$counter]['Astute'.$counterRowData]['term'] = '';
                                }

                                
                                
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['income_expense_id'] = $income_expense_id;
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['item_name'] = $item_name;
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['id'] = $id;
                                $insuranceInfo[$counter]['Astute'.$counterRowData]['income_expense_name'] = $income_expense_name;
                                $counterRowData++;
                	       }
                	    }
                    }
                    
                    $counter++;
                }
             } 
             if($errorCount>0){
                $message = 'Valid data not found in the Response returned by Astute!';
                        session()->flash('success', $message);
                        // session()->flash('message', 'loop doenot work. '); 
             }
        }
        else 
        {
          $message = 'No data is returned by Astute API! ';
          session()->flash('success', 'No data is returned by Astute API! ');
        }
        // echo "<pre>";
        // print_r($insuranceInfo);
// dd('hererrrrr');
        // $message = '';
        if (!empty($insuranceInfo) && sizeof($insuranceInfo) > 0) {
            foreach ($insuranceInfo as $key => $insuranceData) {
               
              foreach($insuranceData as $newKey => $insurData)
               {
                   
                   if(isset($insuranceData))
                   {
                    //   print_r($insurData);
                   }
                    $PaymentAmt = 0.00;
                    $ProductType = isset($insurData['ProductType']) ? $insurData['ProductType']  : "";
                    $PlanName = isset($insurData['PlanName']) ? $insurData['PlanName']  : $ProductType;
                    $PaymentMode = isset($insurData['PaymentMode']) ? $insurData['PaymentMode']  : "";
                    $PolNumber = isset($insurData['PolNumber']) ? $insurData['PolNumber']  : "";
                    $PaymentAmt = isset($insurData['PaymentAmt']) && !empty($insurData['PaymentAmt']) ? $insurData['PaymentAmt']  : 0.00;
                    $term = isset($insurData['term']) ? $insurData['term']  : "";
                    $EffDate = isset($insurData['EffDate']) ? $insurData['EffDate']  : "";
                    $CashValueAmt = isset($insurData['CashValueAmt']) ? $insurData['CashValueAmt']  : 0;
                    $DeathBenifitAmt = isset($insurData['DeathBenifitAmt']) ? $insurData['DeathBenifitAmt']  : 0;
                    
                    $DisabilityAmt = isset($insurData['DisabilityAmt']) ? $insurData['DisabilityAmt']  : 0;
                    $DreadDiseaseAmt = isset($insurData['DreadDiseaseAmt']) ? $insurData['DreadDiseaseAmt']  : 0;
                    $IllnessAmt= isset($insurData['IllnessAmt']) ? $insurData['IllnessAmt']  : 0;
                    
                    
                    $DisabilityDesc = isset($insurData['DisabilityDesc']) ? $insurData['DisabilityDesc']  : '';
                    $DreadDiseaseDesc = isset($insurData['DreadDiseaseDesc']) ? $insurData['DreadDiseaseDesc']  : ''; 
                    $IllnessDesc = isset($insurData['IllnessDesc']) ? $insurData['IllnessDesc']  : '';
                    
                    $DisabilityEffDate = isset($insurData['DisabilityEffDate']) ? $insurData['DisabilityEffDate']  : '';
                    $DreadDiseaseEffDate = isset($insurData['DreadDiseaseEffDate']) ? $insurData['DreadDiseaseEffDate']  : ''; 
                    $IllnessEffDate = isset($insurData['IllnessEffDate']) ? $insurData['IllnessEffDate']  : '';
                    
                    $DisabilityPaymentMode = isset($insurData['DisabilityPaymentMode']) ? $insurData['DisabilityPaymentMode']  : '';
                    $DreadDiseasePaymentMode = isset($insurData['DreadDiseasePaymentMode']) ? $insurData['DreadDiseasePaymentMode']  : ''; 
                    $IllnessPaymentMode = isset($insurData['IllnessPaymentMode']) ? $insurData['IllnessPaymentMode']  : '';
                    
                    $DisabilityPaymentAmt = isset($insurData['DisabilityPaymentAmt']) ? $insurData['DisabilityPaymentAmt']  : '';
                    $DreadDiseasePaymentAmt = isset($insurData['DreadDiseasePaymentAmt']) ? $insurData['DreadDiseasePaymentAmt']  : ''; 
                    $IllnessPaymentAmt = isset($insurData['IllnessPaymentAmt']) ? $insurData['IllnessPaymentAmt']  : '';
                    
                    
                    
                   $income_expense_id = isset($insurData['income_expense_id']) ? $insurData['income_expense_id'] : 0;
                   $item_name = isset($insurData['item_name']) ? $insurData['item_name'] : "";
                   $id = isset($insurData['id']) ? $insurData['id'] : 0;
                   $income_expense_name = isset($insurData['income_expense_name']) ? $insurData['income_expense_name'] : " ";
                   if(  $PlanName != "Term")
                   {
                        if(!empty($PolNumber))
                        {
                            $insurance_count = DB::table('client_insurances')
                                ->where('life_cover_description', $PlanName)
                                ->where('life_cover_policy_ref', $PolNumber)
                                ->where('client_reference_id', $client_reference_id)
                                ->count();
                            if($insurance_count == 0) {

                                    $insuranceId = DB::table('client_insurances')->insert(
                                    [
                                        'advisor_id' => $userId,
                                        'capture_advisor_id' => $userId,
                                        'client_type'=>$client_type,
                                        'client_reference_id' => $client_reference_id,
                                        'life_cover_description' => $PlanName,
                                        'life_cover_policy_ref' => $PolNumber,
                                        'life_cover_owner' => '',
                                        'life_cover_assured' =>$PaymentAmt,
                                        'life_cover_cash_value' => $CashValueAmt,
                                        'life_cover_death' => $DeathBenifitAmt,
                                        'life_cover_disability' => $DisabilityAmt,
                                        'life_cover_dread_disease' => $DreadDiseaseAmt,
                                        'life_cover_impairment' => 0,
                                        'sick_description' => (isset($IllnessDesc) && !empty($IllnessDesc)) ? $IllnessDesc : $PlanName,
                                        'sick_monthly_amount' => (isset($IllnessAmt) && $IllnessAmt != 0)  ? $IllnessAmt : $PaymentAmt,
                                        'sick_frequency' => (isset($IllnessPaymentMode) && $IllnessPaymentMode != 0)  ? $IllnessPaymentMode : $PaymentMode,
                                        'sick_waiting_period' => '',
                                        'sick_term' => $term,
                                        'sick_claim_escalation' => '',
                                        'sick_esc' => 0,
                                        'income_protect_description' => $PlanName,
                                        'income_protect_monthly_amount' => $PaymentAmt,
                                        'income_protect_frequency' => $PaymentMode,
                                        'income_protect_waiting_period' => '',
                                        'income_protect_term' => $term,
                                        'income_protect_claim_escalation' => '',
                                        'income_protect_esc' => 0,
                                        'death_term_related_to' => (isset($DreadDiseaseDesc) && !empty($DreadDiseaseDesc)) ? $DreadDiseaseDesc : $PlanName,
                                        'death_description' => (isset($DreadDiseaseDesc) && !empty($DreadDiseaseDesc))  ? $DreadDiseaseDesc : $PlanName,
                                        'death_date_of_birth' => isset($DreadDiseaseEffDate) ? $DreadDiseaseEffDate : $EffDate,
                                        'death_waiting_period' => '',
                                        'death_term' => $term,
                                        'death_claim_escalation' => 0,
                                        'death_esc' => 0,
                                        'insurance_allocation_for_death' => 0,
                                        'insurance_allocation_for_disability' => 0,
                                        'insurance_allocation_for_dread_disease' => 0,
                                        'insurance_allocation_for_impairment' => 0,
                                        'insurance_allocation_for_income_benefits' => 0
                                    ]);
                                /*insert into the Item_types and */
                                $budgetId = DB::table('budget')->insertGetId(
                                            [
                                                'client_reference_id' => $client_reference_id,
                                                'advisor_id' => $userId,
                                                'client_id' => $clientID,
                                                'client_type' => $client_type,
                                                'advisor_capture_id' => '1',
                                                'income_expenses_type' => '2',
                                                'item_type_id' => $income_expense_id,
                                                'item_type' => $item_name,
                                                'item_id' => $id,
                                                'item_name' => $income_expense_name,
                                                'item_value' => $PaymentAmt,
                                                'capture_date' => now()
                                            ]);
                                $currentYear = date("Y");
                                for($month=1; $month<=12 ; $month++) {
    
                                    DB::table('yearly_budget')->insert([
                                        'client_reference_id' => $client_reference_id,
                                        'advisor_id' => $userId,
                                        'advisor_capture_id' => $userId,
                                        'budget_id' => $budgetId,
                                        'client_id' => $clientID,
                                        'client_type' => $client_type,
                                        'income_expenses_type' => '2',
                                        'item_type_id' => $income_expense_id,
                                        'item_type' => $item_name,
                                        'item_id' => $id,
                                        'item_name' => $income_expense_name,
                                        'item_value' => $PaymentAmt,
                                        'capture_date' => now(),
                                        'month' => $month,
                                        'year' => $currentYear
                                    ]);
                                }
                                $message =  "Insurance populated via Astute!";// for '". $PlanName ."'!";
                            } else {
                                $message = "Insurance already populated via Astute!"; // for '". $PlanName ."'! '";
                            }
                        } else {
                            //$message = 'Insurance policy number is empty! ';
                        }
                   }
               }
            }
        } else {
            $message = 'No data is returned by Astute! ';
        }
        // die($message);
        session()->flash('success', $message);
        return \Redirect::route('insuranceList', [$client_reference_id, $client_type ]);
    }
    

    
    public function createInsurance($client_reference_id, $client_type){
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $clientReff = $client_reference_id; 
        $client_owners = DB::table('clients')->where('client_reference_id', $clientReff)->get()->map(function($client) {
            $data['id'] = $client->id.'_Client';
            $data['first_name'] = $client->first_name;
            $data['last_name'] = $client->last_name;
            $data['type'] = $client->client_type;
            return $data;
        });
                       
        $client_dependents = DB::table('dependants')->where('client_reference_id', $clientReff)->get()->map(function($dependant) {
            $data['id'] = $dependant->id.'_Dependant';
            $data['first_name'] = $dependant->first_name;
            $data['last_name'] = $dependant->last_name;
            $data['type'] = $dependant->dependant_type;
            return $data;
        });
        $owners = collect($client_owners)->merge(collect($client_dependents));
        $client_owners = DB::table('clients')->where('client_reference_id', $clientReff)->get()->map(function($client) {
            $data['id'] = $client->id.'_Client';
            $data['first_name'] = $client->first_name;
            $data['last_name'] = $client->last_name;
            $data['type'] = $client->client_type;
            return $data;
        });
        $client_owners_all = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");

        return view('insurance.createInsurance',['client_reference_id'=>$clientReff,'client_owners'=> $owners, 'client_owners_all' => $client_owners_all, 'client_type' => $client_type]);
    }
    
    public function saveInsurance(Request $request,$client_reference_id){
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
            'client_type' => $_POST['client_type'],
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
        $client_type = $_POST['client_type'];
        
        if(isset($_POST['owner_id'])){
            $owner_count = count($_POST['owner_id']); 
            if($owner_count >0) {
                    for($i = 0; $i < $owner_count; $i++)
                    {
                        $owner_id_type = explode('_',$_POST['owner_id'][$i]);
                        $saveclientAssetsBeneficiary = DB::table('client_insurances_beneficiary')->insert([
                            'owner_id' =>  $owner_id_type[0],
                            'type' =>  $owner_id_type[1],
                            'client_reference_id' => $_POST['client_reference_id'],
                            'insurance_id' => $insuranceId,
                            'percentage' => $_POST['percentage'][$i]
                        ]);                
                    }
             }
        }
        $insuranceModule = 'Insurance Module';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $insuranceModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Insurance Save page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]);
        return \Redirect::route('insuranceList', [$client_reference_id,$client_type]);
        
    }
    
    public function fetchUpdateInsuranceList(Request $request,$client_reference_id,$id){
            session_start();
            if(empty($_SESSION['login']))
            {
                header("location: https://fna2.phpapplord.co.za/public/");
                exit;
            }
            $userId = $_SESSION['userId'];
            $query = DB::select("SELECT * FROM client_insurances WHERE client_reference_id = '".$client_reference_id."' AND id='".$id."' ");
            // dd($query);
            $InsurancesBeneficiary = DB::select("SELECT * FROM client_insurances_beneficiary WHERE client_reference_id = '".$client_reference_id."' AND insurance_id='".$id."'");
            
            $clientReff = $client_reference_id; 
            $client_owners = DB::table('clients')->where('client_reference_id', $clientReff)->get()->map(function($client) {
                $data['id'] = $client->id;
                $data['first_name'] = $client->first_name;
                $data['last_name'] = $client->last_name;
                $data['type'] = $client->client_type;
                $data['reletion_type'] = 'Client';
                return $data;
            });
            $client_dependents = DB::table('dependants')->where('client_reference_id', $clientReff)->get()->map(function($dependant) {
                $data['id'] = $dependant->id;
                $data['first_name'] = $dependant->first_name;
                $data['last_name'] = $dependant->last_name;
                $data['type'] = $dependant->dependant_type;
                $data['reletion_type'] = 'Dependant';
                return $data;
            });
            $owners = collect($client_owners)->merge(collect($client_dependents));
            $client_bank_name = DB::select("SELECT * FROM `user_bank`");
            $bank_name = $client_bank_name[0]->bank_name  ?? "0";
            $client_owners_all = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");

            return view('insurance.InsuranceUpdateView',[
                'client_insurance'=>$query,
                'client_owners'=>$owners, 
                'client_reference_id'=>$client_reference_id,
                'InsurancesBeneficiary'=>$InsurancesBeneficiary,
                'client_type'=>'Main Client',
                'bank_name'=>$bank_name,
                'client_owners_all' => $client_owners_all
                ]);
    }
    
    public function UpdateInsuranceList(Request $request,$client_reference_id,$id){
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $client_type = $_POST["client_type"];
        // -- advisor_id="'.$_POST["advisor_id"].'",
        // -- capture_advisor_id="'.$_POST["capture_advisor_id"].'",
        $query = DB::select('UPDATE client_insurances SET 

            client_reference_id="'.$client_reference_id.'",
            client_type ="'.$_POST["client_type"].'",
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
             
            WHERE client_reference_id="'.$client_reference_id.'" AND id="'.$id.'" ');
            
            DB::select("DELETE FROM `client_insurances_beneficiary` where client_reference_id = '$client_reference_id' AND  insurance_id = '$id'");              
            if(isset($_POST['owner_id'])){
            $owner_count = count($_POST['owner_id']); 
            
            if($owner_count >0) {
                    for($i = 0; $i < $owner_count; $i++)
                    {
                        $owner_id_type = explode('_',$_POST['owner_id'][$i]);
                        $saveclientAssetsBeneficiary = DB::table('client_insurances_beneficiary')->insert([
                            'owner_id' => $owner_id_type[0],
                            'type' => $owner_id_type[1],
                            'client_reference_id' => $client_reference_id,
                            'insurance_id' => $id,
                            'percentage' => $_POST['percentage'][$i]
                        ]);                
                    }
             }
        }
        $insuranceModule = 'Insurance Module';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $insuranceModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Insurance Update page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]);
        return \Redirect::route('insuranceList', [$client_reference_id, $client_type]);        
    }
    
        public function deleteInsuranceList($client_reference_id, $client_type, $id){
        DB::select("DELETE FROM `client_insurances` where client_reference_id = '$client_reference_id' AND  id = '$id'");
        DB::select("DELETE FROM `client_insurances_beneficiary` where client_reference_id = '$client_reference_id' AND  insurance_id = '$id'");
        
        return redirect()->route('insuranceList', ['client_reference_id' => $client_reference_id, 'client_type' => $client_type]);
        // return \Redirect::route('insuranceList', [$client_reference_id]);   
    }
}