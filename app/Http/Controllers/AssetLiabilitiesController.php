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
use Haruncpi\LaravelIdGenerator\IdGenerator;
use URL;
class AssetLiabilitiesController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    
    public function index($client_reference_id, $client_type) {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        
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
        
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Create Page",
            'date' => DB::raw('now()')
        ]); 
            
        // $client_owners = DB::select("SELECT * FROM `client_assets_liabilities` INNER JOIN clients ON client_assets_liabilities.owners_id = clients.id where client_assets_liabilities.client_reference_id = 'fna000000001'");
        // $client_liabilities = DB::select("SELECT * FROM `client_liabilities` INNER JOIN clients ON client_liabilities.owners_id = clients.id");
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '".$client_reference_id."'");
        return view('fna.listAssetLiabilities',['getAccessName'=>$getAccessName,'clientLiabilities'=>$client_owners,'client_owners'=>$client_owners,'client_reference_id'=>$client_reference_id,'client_type'=>$client_type]);
        
    }
    
       public function addAssetView($clientReff){
        
        $clientReff = 'fna000000000034';

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



        return view('fna.addAssetView2',[
            'client_reference_id'=>$clientReff,
             'client_owners'=> $owners
            ]);
    }
     public function updateView($client_reference_id, $client_type, $type) {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];
        
        // $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        // $liabilitiesModule = 'Liabilities';
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
        session()->forget('productPropertyData');
        session()->forget('productVehicleData');
        session()->forget('productCpbAssetData');
        session()->forget('productCpbLiabilityData');

        /**
         * Fetch the Additional buy details data for Vehicle, property
         * and CPB assets and CPB liabilities to show the data in the popup
         */
        $features_details = DB::table('extra_purchase')     
                                ->whereIn('p_id',[2, 3, 4, 5 , 6]) // ['Vehicle' , 'Property', 'Credit'])
                                ->orderBy('p_id','DESC')
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
                                ->selectRaw('package_id, order_vehicle_count, order_property_count, order_cpb_asset_count, order_cpb_liability_count, expiry_date_time')   
                                ->where('user_id', $client_reference_id)
                                ->orderBy('id','DESC')
                                ->first();
        // dd($orders_count);
        $package_purchased = 'no';
        $orders_count_of_vehicles = $orders_count_of_property = $orders_count_of_credit = $orders_count_of_cpbasset = $orders_count_of_cpbliability = 0;
        $vehicle_count = $cpb_count = $property_count = $cpb_asset_count  = $cpb_liability_count  = 0;
        $package_id = 0;
        if(isset($orders_count)) {
            $package_purchased = 'yes';
            $orders_count_of_vehicles = $orders_count->order_vehicle_count;
            $orders_count_of_property = $orders_count->order_property_count;

            $orders_count_of_cpbasset = $orders_count->order_cpb_asset_count;
            $orders_count_of_cpbliability = $orders_count->order_cpb_liability_count;

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

        $form_data_vehicle = $form_data_property = $form_data_asset  = $form_data_liability = [];
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
         * If orders_count_of_vehicles < count(api_hit_details where id = 2 and type = 'Vehilce' and client_reference_id = 'current_user client_reference_id')
         * Show buy new Additional purchase
         * else
         * he can import from Lightstone vehicle
         * end
         */
        $count_of_vehicles = DB::table('api_hit_details')
                                ->where('client_reference_id', $client_reference_id)
                                ->where('api_name','Vehicle')
                                ->where('api_id','2')
                                ->count();   
        if($orders_count_of_vehicles <= $count_of_vehicles) {
            $vehicle_count = 1; //Buy new vehicle import
            if(isset($features_details)) {
                // print_r($features_details);
                foreach($features_details as $feature) {
                    if($feature->title == 'Vehicle') {
                        $feature_name = 'Vehicle';
                        $feature_id = $feature->p_id;
                        $feature_price = ($feature->markup *  $feature->count) ;

                        $cartTotal = $feature_price; // This amount needs to be sourced from your application
                        // use within single line code
                        $orders = DB::table('order_features')->count();
                        $orderNum = IdGenerator::generate(['table' => 'order_features', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
                        $orderNumer = $orderNum.$orders;
                        $payment_id = $package_id.'_'.$feature_id; //0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
                        $form_data_vehicle = array(
                            // Merchant details
                            'merchant_id' => '10027202',
                            'merchant_key' => 'zbn6mb2wba5ik',
                            'return_url' => URL::to('/successVehiclePayment'),
                            'cancel_url' => URL::to('/cancelVehiclePayment'),
                            'notify_url' => URL::to('/notifyVehiclePayment'),
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
                        $signature = $this->generateSignature($form_data_vehicle, $passphrase);
                        $form_data_vehicle['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za

                        // dd($form_data_vehicle);
                        Session::put('productVehicleData', $form_data_vehicle);
                        Session::put('productVehicleData', $form_data_vehicle);
                        session()->put('productVehicleData.client_reference_id', $client_reference_id);
                        session()->put('productVehicleData.client_type', $client_type);
                        session()->put('productVehicleData.clientName', $clientName);
                        session()->put('productVehicleData.featureName', $feature_name);
                        session()->put('productVehicleData.featureId', $feature_id);
                    }
                }
            }
        } elseif($orders_count_of_vehicles > $count_of_vehicles) {
            $vehicle_count = 0; //do the vehicle import
        } 
        /**end here */

        /**
         * Property Additional form creation and validations starts from here
         * Below code will check whether to chow buy additional purchase of property for the client or not
         * If orders_count_of_propery < count(api_hit_details where id = 3 and type = 'Property' and client_reference_id = 'current_user client_reference_id')
         * Show buy new Additional purchase
         * else
         * he can import from Lightstone property
         * end
         */
        $count_of_property = DB::table('api_hit_details')
                                ->where('client_reference_id', $client_reference_id)
                                ->where('api_name','Property')
                                ->where('api_id','3')
                                ->count();   
        // echo $count_of_property.'====='.$orders_count_of_property;die;
        if($orders_count_of_property <= $count_of_property) {
            $property_count = 1; //Buy new property import
            if(isset($features_details)) {
                // print_r($features_details);
                foreach($features_details as $feature) {
                    if($feature->title == 'Property') {
                        $feature_name = $feature->title;
                        $feature_id = $feature->p_id;
                        $feature_price = ($feature->markup *  $feature->count) ;

                        $cartTotal = $feature_price; // This amount needs to be sourced from your application
                        // use within single line code
                        $orders = DB::table('order_features')->count();
                        $orderNum = IdGenerator::generate(['table' => 'order_features', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
                        $orderNumer = $orderNum.$orders;
                        $payment_id = $package_id.'_'.$feature_id; //0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
                        $form_data_property = array(
                        // Merchant details
                        'merchant_id' => '10027202',
                        'merchant_key' => 'zbn6mb2wba5ik',
                        'return_url' => URL::to('/successPropertyPayment'),
                        'cancel_url' => URL::to('/cancelPropertyPayment'),
                        'notify_url' => URL::to('/notifyPropertyPayment'),
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
                        $signature = $this->generateSignature($form_data_property, $passphrase);
                        $form_data_property['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za

                        // dd($form_data_property);
                        Session::put('productPropertyData', $form_data_property);
                        Session::put('productPropertyData', $form_data_property);
                        session()->put('productPropertyData.client_reference_id', $client_reference_id);
                        session()->put('productPropertyData.client_type', $client_type);
                        session()->put('productPropertyData.clientName', $clientName);
                        session()->put('productPropertyData.featureName', $feature_name);
                        session()->put('productPropertyData.featureId', $feature_id);
                    }
                }
            }
        } elseif($orders_count_of_property > $count_of_property) {
            $property_count = 0; //do the property import
        }
        // Property Additional form creation and validations ends here

        /**
         * Below code will check whether to chow buy additional purchase of CPB assets for the client or not
         * If orders_count_of_cpb_assets < count(api_hit_details where id = 5 and type = 'CPB assets' and client_reference_id = 'current_user client_reference_id')
         * Show buy new Additional purchase
         * else
         * he can import from CPB Assets
         * end
         */
        $count_of_cpbassets = DB::table('api_hit_details')
                                ->where('client_reference_id', $client_reference_id)
                                ->where('api_name','CPB assets')
                                ->where('api_id','5')
                                ->count();   
        // echo $orders_count_of_cpbasset .'=='. $count_of_cpbassets;
        if($orders_count_of_cpbasset == $count_of_cpbassets) {
            $cpb_asset_count = 1; //Buy new vehicle import
            if(isset($features_details)) {
                // print_r($features_details);
                foreach($features_details as $feature) {
                    if($feature->title == 'CPB assets') {
                        $feature_name = 'CPB assets';
                        $feature_id = $feature->p_id;
                        $feature_price = ($feature->markup *  $feature->count) ;

                        $cartTotal = $feature_price; // This amount needs to be sourced from your application
                        // use within single line code
                        $orders = DB::table('order_features')->count();
                        $orderNum = IdGenerator::generate(['table' => 'order_features', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
                        $orderNumer = $orderNum.$orders;
                        $payment_id = $package_id.'_'.$feature_id; //0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
                        $form_data_asset = array(
                            // Merchant details
                            'merchant_id' => '10027202',
                            'merchant_key' => 'zbn6mb2wba5ik',
                            'return_url' => URL::to('/successCpbAssetPayment'),
                            'cancel_url' => URL::to('/cancelCpbAssetPayment'),
                            'notify_url' => URL::to('/notifyCpbAssetPayment'),
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
                        $signature = $this->generateSignature($form_data_asset, $passphrase);
                        $form_data_asset['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za

                        // dd($form_data_asset);
                        Session::put('productCpbAssetData', $form_data_asset);
                        session()->put('productCpbAssetData.client_reference_id', $client_reference_id);
                        session()->put('productCpbAssetData.client_type', $client_type);
                        session()->put('productCpbAssetData.clientName', $clientName);
                        session()->put('productCpbAssetData.featureName', $feature_name);
                        session()->put('productCpbAssetData.featureId', $feature_id);
                    }
                }
            }
        } elseif($orders_count_of_cpbasset > $count_of_cpbassets) {
            $cpb_asset_count = 0; //do the vehicle import
        } 
        /**end here */

        /**
         * Below code will check whether to chow buy additional purchase of CPB Liabilities for the client or not
         * If orders_count_of_cpb_assets < count(api_hit_details where id = 6 and type = 'CPB Liabilities' and client_reference_id = 'current_user client_reference_id')
         * Show buy new Additional purchase
         * else
         * he can import from CPB Liabilities
         * end
         */
        $count_of_cpbliability = DB::table('api_hit_details')
                                ->where('client_reference_id', $client_reference_id)
                                ->where('api_name','CPB Liabilities')
                                ->where('api_id','6')
                                ->count();   
        // echo ($orders_count_of_cpbliability.' =='. $count_of_cpbliability);
        if($orders_count_of_cpbliability <= $count_of_cpbliability) {
            $cpb_liability_count = 1; //Buy new vehicle import
            if(isset($features_details)) {
                // print_r($features_details);
                foreach($features_details as $feature) {
                    if($feature->title == 'CPB Liabilities') {
                        $feature_name = 'CPB Liabilities';
                        $feature_id = $feature->p_id;
                        $feature_price = ($feature->markup *  $feature->count) ;

                        $cartTotal = $feature_price; // This amount needs to be sourced from your application
                        // use within single line code
                        $orders = DB::table('order_features')->count();
                        $orderNum = IdGenerator::generate(['table' => 'order_features', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
                        $orderNumer = $orderNum.$orders;
                        $payment_id = $package_id.'_'.$feature_id; //0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
                        $form_data_liability = array(
                            // Merchant details
                            'merchant_id' => '10027202',
                            'merchant_key' => 'zbn6mb2wba5ik',
                            'return_url' => URL::to('/successCpbLiabilityPayment'),
                            'cancel_url' => URL::to('/cancelCpbLiabilityPayment'),
                            'notify_url' => URL::to('/notifyCpbLiabilityPayment'),
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
                        $signature = $this->generateSignature($form_data_liability, $passphrase);
                        $form_data_liability['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za

                        // dd($form_data_liability);
                        Session::put('productCpbLiabilityData', $form_data_liability);
                        session()->put('productCpbLiabilityData.client_reference_id', $client_reference_id);
                        session()->put('productCpbLiabilityData.client_type', $client_type);
                        session()->put('productCpbLiabilityData.clientName', $clientName);
                        session()->put('productCpbLiabilityData.featureName', $feature_name);
                        session()->put('productCpbLiabilityData.featureId', $feature_id);
                    }
                }
            }
        } elseif($orders_count_of_cpbliability > $count_of_cpbliability) {
            $cpb_liability_count = 0; //do the vehicle import
        }
        /**end here */
        $client_vin_vehicles =  DB::table('client_assets')
        ->selectRaw('client_assets.id,
                client_assets.client_reference_id,
                client_assets.vehicle_vin_number')
        ->leftJoin('asset_liability_types', 'asset_type', '=', 'asset_liability_types.id')
        ->where('client_reference_id', $client_reference_id)
        ->where('vehicle_vin_number','!=',null)
        ->where('vehicle_vin_number','!=','')
        ->get();
        // dd($client_vin_vehicles);
        $result =  DB::table('client_assets')
                            ->selectRaw('client_assets.id,
                                    client_assets.asset_sub_type,
                                    client_assets.asset_description,
                                    client_assets.asset_amount,
                                    client_assets.apply_to_event,
                                    asset_liability_types.name,
                                    client_assets.client_type,
                                    client_assets.client_reference_id')
                            ->leftJoin('asset_liability_types', 'asset_type', '=', 'asset_liability_types.id')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', $client_type)
                            ->simplePaginate(10);
        $result2 =  DB::table('client_liabilities_new')
                            ->selectRaw('client_liabilities_new.id,
                                    client_liabilities_new.liability_sub_type,
                                    client_liabilities_new.policy_number,
                                    client_liabilities_new.outstanding_balance,
                                    client_liabilities_new.under_advice,
                                    asset_liability_types.name,
                                    client_liabilities_new.client_type,
                                    client_liabilities_new.client_reference_id')
                            ->leftJoin('asset_liability_types', 'liability_type', '=', 'asset_liability_types.id')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', $client_type)
                            ->simplePaginate(10);
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        
        $asset_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 1)->orderBy('id','DESC')->get();
        $liability_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 2)->orderBy('id','DESC')->get();

        // echo 'vehicle_count =' . $vehicle_count;
        // echo 'cpb_count =' . $cpb_count;
        // echo 'property_count =' . $property_count;
        // echo 'pfHost =' . $pfHost;
        // dd('=======in the line no 348');
        // $data = $form_data_vehicle;
        return view('fna.updateDeleteAssetsLiabilities',[
            'client_vin_vehicles' => $client_vin_vehicles,
            'package_purchased' => $package_purchased,
            'features_details' => $features_details,
            'type'=>$type,
            'property_count' => $property_count, 
            'cpb_count' => $cpb_count , 
            'vehicle_count' => $vehicle_count,
            'cpb_asset_count' => $cpb_asset_count,
            'cpb_liability_count' => $cpb_liability_count,
            'pfHost' => $pfHost,
            // 'data' => $data,
            'form_data_vehicle' => $form_data_vehicle,
            'form_data_property' => $form_data_property,
            'form_data_asset' => $form_data_asset,
            'form_data_liability' => $form_data_liability,
            'asset_types' => $asset_types,
            'liability_types' => $liability_types,
            'result' => $result,
            'result2' => $result2, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type , 'getAccessName'=>$getAccessName,'clientLiabilities'=>$client_owners,'client_owners'=>$client_owners]);
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
    public function updateView_old($client_reference_id, $client_type) {
          session_start(); 
        // if(empty($_SESSION['login']))
        // {
        //     header("location: https://fna2.phpapplord.co.za/public/");
        //     exit;
        // } 
        $userId = $_SESSION['userId'];
        
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
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
        
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Update page",
            'date' => DB::raw('now()')
        ]); 
        
        $result = DB::select("SELECT client_assets_liabilities.id, client_assets_liabilities.item_type, client_assets_liabilities.item_name, client_assets_liabilities.item_value, client_assets_liabilities.date_purchased, client_assets_liabilities.owners_id , concat(clients.first_name, ' ',clients.last_name) as owners_id FROM `client_assets_liabilities` INNER JOIN clients On owners_id = clients.id where client_assets_liabilities.asset_liability_type = '1' and client_assets_liabilities.client_reference_id = '".$client_reference_id."'");
        $result2 = DB::select("SELECT client_assets_liabilities.id, client_assets_liabilities.item_type, client_assets_liabilities.item_name, client_assets_liabilities.item_value, client_assets_liabilities.date_purchased, client_assets_liabilities.owners_id , concat(clients.first_name, ' ',clients.last_name) as owners_id FROM `client_assets_liabilities` INNER JOIN clients On owners_id = clients.id where client_assets_liabilities.asset_liability_type = '2' and client_assets_liabilities.client_reference_id = '".$client_reference_id."'");

        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        return view('fna.updateDeleteAssetsLiabilities',[ 'result' => $result,'result2' => $result2, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type , 'getAccessName'=>$getAccessName,'clientLiabilities'=>$client_owners,'client_owners'=>$client_owners])->with($result);
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
      
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Store Page",
            'date' => DB::raw('now()')
        ]); 
      
        $count = count($_POST["liabilities_type"]); 
        // var_dump($_POST);
        // die();
    	for($i = 0; $i < $count; $i++)
    	{ 
    		DB::select("INSERT into client_assets_liabilities values (
    			null,
    			'".$_POST['liabilities_item_type'][$i]."',
    			'".$userId."', 
    			'".$_POST['client_reference_id'][$i]."',
    			'".$_POST['liabilities_type'][$i]."', 
    			'".$_POST['liabilities_name'][$i]."', 
    			'".$_POST['liabilities_value'][$i]."', 
    			'".$_POST['date_purchased'][$i]."', 
    			'".$_POST['client_owners_id'][$i]."')");
    			
    	}

        $count = count($_POST["asset_type"]); 

        for($i = 0; $i < $count; $i++)
        {
            $client_reference_id = $_POST['client_reference_id'][$i];
            DB::select("INSERT into client_assets_liabilities values (
                null,
                '".$_POST['asset_item_type'][$i]."',
                '".$userId."',
                '".$_POST['client_reference_id'][$i]."',
                '".$_POST['asset_type'][$i]."',
                '".$_POST['asset_name'][$i]."',
                '".$_POST['asset_value'][$i]."',
                '".$_POST['date_purchased'][$i]."',
                '".$_POST['client_owners_id'][$i]."')");
        }
    	return redirect()->route('whatamiworth',['client_reference_id' => $client_reference_id, 'client_type' => 'Main Client']);
    	
    }
    
     public function update(Request $request)
    {
        var_dump('get here');
        die();
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        $userId = $_SESSION['userId'];

        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Update Page",
            'date' => DB::raw('now()')
        ]); 
        
        $count = count($_POST["liabilities_type"]); 
        // var_dump($_POST['client_owners_id']);
        // die();
    	for($i = 0; $i < $count; $i++)
    	{ 
    		DB::select("INSERT into client_assets_liabilities values (
    			null,
    			'".$_POST['liabilities_item_type'][$i]."',
    			'".$userId."', 
    			'fna000000001', 
    			'".$_POST['liabilities_type'][$i]."', 
    			'".$_POST['liabilities_name'][$i]."', 
    			'".$_POST['liabilities_value'][$i]."', 
    			'".$_POST['date_purchased'][$i]."', 
    			'".$_POST['client_owners_id'][$i]."')");
    			
    	}

        $count = count($_POST["asset_type"]); 

        for($i = 0; $i < $count; $i++)
        {
            DB::select("INSERT into client_assets_liabilities values (
                null,
                '".$_POST['asset_item_type'][$i]."',
                '".$userId."',
                'fna000000001',
                '".$_POST['asset_type'][$i]."',
                '".$_POST['asset_name'][$i]."',
                '".$_POST['asset_value'][$i]."',
                '".$_POST['date_purchased'][$i]."',
                '".$_POST['client_owners_id'][$i]."')");
        }
    	return redirect()->route('whatamiworth');
    	
    }
    
    public function assetDetailsView($id, $client_reference_id = '', $client_type = '') {
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
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
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

        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities AssetDetailsView Page",
            'date' => DB::raw('now()')
        ]); 
        
        //new
        $result = DB::select("SELECT * FROM `client_assets_liabilities` WHERE id = '$id'");
        // dd($result);
        // $referenceId = $result[0]->client_reference_id;
        $owners = DB::select("SELECT * FROM `personal_details`");
        $ownersNew = DB::select("SELECT id, first_name as fname, last_name as sname FROM `clients` where client_reference_id= '$client_reference_id'");
        // var_dump($owners);
        // die();
      
        return view('fna.updateSingleLiability',[ 'client_reference_id' => $client_reference_id, 'client_type' => $client_type, 'ownersNew'=>$ownersNew,'result'=>$result,'id'=>$id,'owners' => $owners, 'id' => $id, 'getroleId' => $getroleId,'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
    }
    
    public function updateLiability(Request $request)
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        
        $userId = $_SESSION['userId'];        
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Update Page",
            'date' => DB::raw('now()')
        ]); 
        // var_dump($_POST);die();
        DB::select("UPDATE client_assets_liabilities set item_type = '".$_POST['assetType']."', item_name = '".$_POST['assetName']."', item_value = '".$_POST['value']."', date_purchased = '".$_POST['DP']."', owners_id = '".$_POST['owner']."' where id ='".$_POST['asset_id']."' "); 

    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $_POST['client_reference_id'], 'client_type' => $_POST['client_type']]);
    }
    
    public function deleteLiability($id, $client_reference_id = '', $client_type = '')
    {

        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        
        $userId = $_SESSION['userId'];        
        $assetsLiabilitiesModule = 'AssetsLiabilitiesModule';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");

        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $assetsLiabilitiesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Delete Page",
            'date' => DB::raw('now()')
        ]);  

        DB::select("DELETE FROM client_assets_liabilities where id ='$id'"); 
    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id, 'client_type' => $client_type]);
    }

    public function overview() {
        session_start(); 
        if(empty($_SESSION['login']))
        {             
            header("location: https://fna2.phpapplord.co.za/public/");               
            exit;
        } 
        $userId = $_SESSION['userId'];
        $personalInfoModule = 'Personal Information';
        $dependantsModule = 'Dependants';
        $assetsModule = 'Assets';
        $liabilitiesModule = 'Liabilities';
        $personalBudgetModule = 'Personal budget'; 
        $riskModule = 'Risk Objectives';
        $retirementRiskModule = 'Retirement Risks Objectives'; 
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");
        $dependantsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$dependantsModule'");
        $assetsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$assetsModule'");
        $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalBudgetModuleModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalBudgetModule'");
        $riskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$riskModule'");
        $retirementRiskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$retirementRiskModule'");
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."'");
        if(!isset($getroleId[0]->groupId))
        {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        }
        else
        {
            //var_dump($getroleId[0]->groupId); die();
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$retirementRiskModuleModuleId[0]->id."'");
            if(!isset($getretirementRiskAclAccessId[0]->accessId))
            {
                $getretirementRiskAclAccess = "noAccess";
               // echo $getretirementRiskAclAccess; die();
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
            
            
            
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
            if(!isset($assetsModuleAclAccessId[0]->accessId))
            {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
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
            
            
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalBudgetModuleModuleModuleId[0]->id."'");
            if(!isset($personalBudgetModuleAclAccessId[0]->accessId))
            {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalBudgetModuleAclAccessId[0]->accessId."'");
                if(!isset($liabilitiesModuleAccessName[0]->name))
                { 
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
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
        $Module = 'Liabilities';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $roleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."' ");
        $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$roleId[0]->groupId."' and moduleId = '".$getModuleId[0]->id."'");
        //var_dump($getAclAccessId); die();
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
        
        $clients = DB::table('clients')
                        ->where('client_type','Main Client')
                        ->get()
                        ->map(function($client) {
                            
                            $clientData['id'] = $client->id;
                            $clientData['first_name'] = $client->first_name;
                            $clientData['last_name'] = $client->last_name;
                            $clientData['client_reference_id'] = $client->client_reference_id;
                            
                            $total_assets = DB::table('client_assets_liabilities')
                                                ->where('asset_liability_type', 1)
                                                ->where('client_reference_id', $client->client_reference_id)
                                                ->pluck('item_value')
                                                ->sum();
                            $total_liabilities = DB::table('client_assets_liabilities')
                                                        ->where('asset_liability_type', 2)
                                                        ->where('client_reference_id', $client->client_reference_id)
                                                        ->pluck('item_value')
                                                        ->sum();
        
                            $clientData['total_assets'] = $total_assets;
                            $clientData['total_liabilities'] = $total_liabilities;
                            $clientData['total_worth'] = $total_assets - $total_liabilities;
                            
                            return $clientData;
                        });
        // return view('fna.overview_index', ['getroleId' => $getroleId, 'clients' => $clients, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName, 'getAccessName'=>$getAccessName, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess]);        
        return view('fna.overviewAssetliabilities', ['getroleId' => $getroleId, 'clients' => $clients, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName, 'getAccessName'=>$getAccessName, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    
    }

    public function exportAssets(Request $request, $client_reference_id = '') {
        $file_name = 'clientAssets.csv';
        $assets_list = DB::select("SELECT 
                                id, client_reference_id, 
                                asset_type, asset_description, asset_amount,
                                apply_to_event, allocation_death, allocation_disability, 
                                allocation_dread_disease, allocation_impairment, subject_to_cgt,
                                cgt_asset_type, cgt_bona_fide_farm,
                                cgt_acquisition_date, cgt_initial_value, 
                                cgt_expenditure, cgt_disposal_cost FROM `client_assets` 
                            WHERE `client_reference_id` = '".$client_reference_id."'");
                            // and client_type = '".$client_type."'");  
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$file_name",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );
    
        $columns = array('id', 'client_reference_id', 'asset_type', 'asset_description', 'asset_amount', 'apply_to_event', 
        'allocation_death', 'allocation_disability', 'allocation_dread_disease', 'allocation_impairment', 
        'subject_to_cgt', 'cgt_asset_type', 'cgt_bona_fide_farm', 'cgt_acquisition_date', 'cgt_initial_value', 
        'cgt_expenditure', 'cgt_disposal_cost');
    
            $callback = function() use($assets_list, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
    
                foreach ($assets_list as $asset) {
                    $row['id']  = $asset->id;
                    $row['client_reference_id'] = $asset->client_reference_id;
                    $row['asset_type'] = $asset->asset_type;
                    $row['asset_description'] = $asset->asset_description;
                    $row['asset_amount'] = $asset->asset_amount;
                    $row['apply_to_event'] = $asset->apply_to_event;
                    $row['allocation_death'] = $asset->allocation_death;
                    $row['allocation_disability'] = $asset->allocation_disability;
                    $row['allocation_dread_disease'] = $asset->allocation_dread_disease;
                    $row['allocation_impairment'] = $asset->allocation_impairment;
                    $row['subject_to_cgt'] = $asset->subject_to_cgt;
                    $row['cgt_asset_type'] = $asset->cgt_asset_type;
                    $row['cgt_bona_fide_farm'] = $asset->cgt_bona_fide_farm;
                    $row['cgt_acquisition_date'] = $asset->cgt_acquisition_date;
                    $row['cgt_initial_value'] = $asset->cgt_initial_value;
                    $row['cgt_expenditure'] = $asset->cgt_expenditure;
                    $row['cgt_disposal_cost'] = $asset->cgt_disposal_cost;                   
                    
                    fputcsv($file, array( 
                        $row['id'],
                        $row['client_reference_id'],
                        $row['asset_type'],
                        $row['asset_description'],
                        $row['asset_amount'],
                        $row['apply_to_event'], 
                        $row['allocation_death'],
                        $row['allocation_disability'],
                        $row['allocation_dread_disease'],
                        $row['allocation_impairment'],
                        $row['subject_to_cgt'],
                        $row['cgt_asset_type'],
                        $row['cgt_bona_fide_farm'],
                        $row['cgt_acquisition_date'],
                        $row['cgt_initial_value'],
                        $row['cgt_expenditure'],
                        $row['cgt_disposal_cost']
                    ));
                }
                fclose($file);
            };
        return response()->stream($callback, 200, $headers);        
    }
    
    public function exportLiabilities(Request $request, $client_reference_id) {
        $file_name = 'allLiabilities.csv';
        $liabilities_list = DB::select("SELECT 
                            id, client_reference_id, 
                            liability_type, liability_sub_type, policy_number,
                            outstanding_balance, under_advice, type_of_business, 
                            original_balance,
                            loan_application_amount, thelimit,
                            principal_repaid, last_updated_by,
                            interest_rate_type, interest_rate_pa,
                            repayment_amount, repayment_frequency,
                            select_asset_type
                            FROM `client_liabilities_new` 
                        WHERE `client_reference_id` = '".$client_reference_id."'");
                        // and client_type = '".$client_type."'");  
    
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$file_name",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );
    
        $columns = array('id', 'client_reference_id', 
                            'liability_type', 'liability_sub_type', 'policy_number',
                            'outstanding_balance', 'under_advice', 'type_of_business', 
                            'original_balance',
                            'loan_application_amount', 'thelimit',
                            'principal_repaid', 'last_updated_by',
                            'interest_rate_type', 'interest_rate_pa', 
                            'loan_term', 'loan_term_value',
                            'repayment_amount', 'repayment_frequency',
                            'select_asset_type');
    
            $callback = function() use($liabilities_list, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
    
                foreach ($liabilities_list as $liability) {
                    $row['id'] = $liability->id;
                    $row['client_reference_id'] = $liability->client_reference_id;
                    $row['liability_type'] = $liability->liability_type;
                    $row['liability_sub_type'] = $liability->liability_sub_type;
                    $row['policy_number'] = $liability->policy_number;
                    $row['outstanding_balance'] = $liability->outstanding_balance;
                    $row['under_advice'] = $liability->under_advice;
                    $row['type_of_business'] = $liability->type_of_business;
                    $row['original_balance'] = $liability->original_balance;
                    $row['loan_application_amount'] = $liability->loan_application_amount;
                    $row['thelimit'] = $liability->thelimit;
                    $row['principal_repaid'] = $liability->principal_repaid;
                    $row['last_updated_by'] = $liability->last_updated_by;
                    $row['interest_rate_type'] = $liability->interest_rate_type;
                    $row['interest_rate_pa'] = $liability->interest_rate_pa;
                    
                    $row['repayment_amount'] = $liability->repayment_amount;
                    $row['repayment_frequency'] = $liability->repayment_frequency;
                    $row['select_asset_type'] = $liability->select_asset_type;
                            
                    fputcsv($file, array( 
                        $row['id'],
                        $row['client_reference_id'],
                        $row['liability_type'],
                        $row['liability_sub_type'],
                        $row['policy_number'],
                        $row['outstanding_balance'], 
                        $row['under_advice'],
                        $row['type_of_business'],
                        $row['loan_application_amount'],
                        $row['thelimit'],
                        $row['principal_repaid'],
                        $row['last_updated_by'],
                        $row['interest_rate_type'],
                        $row['interest_rate_pa'],
                        /*$row['loan_term'],
                        $row['loan_term_value'],*/
                        $row['repayment_amount'],
                        $row['repayment_frequency'],
                        $row['select_asset_type']                     
                    ));
                }
                fclose($file);
            };
    
            return response()->stream($callback, 200, $headers);    
    }
}