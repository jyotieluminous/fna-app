<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use URL;

class PaymentController extends Controller
{

    public function package($client_reference_id,$client_type,$default_package)
    {
        session_start();

        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $main_client = DB::table('clients')->where('client_reference_id', $client_reference_id)->where('client_type', $client_type)->first(['first_name','last_name','email']);
        $orders = DB::table('orders')->count();
        $first_name = isset($main_client->first_name) ? $main_client->first_name : '-';
        $last_name = isset($main_client->last_name) ? $main_client->last_name : '-';
        $clientName = $first_name." ".$last_name;
        $email = isset($main_client->email) ? $main_client->email : '-';
        $packages_price = DB::table('packages')->where('package_status', '1')->where('id', $default_package)->first(['id','package_price']);
        
        $cartTotal = isset($packages_price->package_price) ? $packages_price->package_price : '0'; // This amount needs to be sourced from your application
        $payment_id = isset($packages_price->id) ? $packages_price->id : '0';; 
        $passphrase = 'dMBhsDvcn2Q0oRd';//jt7NOE43FZPn
        
        
        // use within single line code
        $orderNum = IdGenerator::generate(['table' => 'orders', 'length' => 10, 'prefix' => date('y'),'reset_on_prefix_change'=>true]);
        $orderNumer = $orderNum.$orders;
        /*$data = array(
            // Merchant details
            'merchant_id' => '13079774',
            'merchant_key' => '54hvi0in3qwkr',
            'return_url' => URL::to('/successPayment'),
            'cancel_url' => URL::to('/cancelPayment'),
            'notify_url' => URL::to('/notifyPayment'),
            // Buyer details
            'name_first' => $first_name,
            'name_last'  => $last_name,
            'email_address'=> $email,
            // Transaction details
            'm_payment_id' => '', //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => 'Order#'.$orderNumer
        );*/
        
        $data = array(
            // Merchant details
            'merchant_id' => '10027202',
            'merchant_key' => 'zbn6mb2wba5ik',
            'return_url' => URL::to('/successPayment'),
            'cancel_url' => URL::to('/cancelPayment'),
            'notify_url' => URL::to('/notifyPayment'),
            // Buyer details
            'name_first' => $first_name,
            'name_last'  => $last_name,
            'email_address'=> $email,
            // Transaction details
            'm_payment_id' => $payment_id, //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => 'Order#'.$orderNumer,
        );
        Session::put('productData', $data);
        session()->put('productData.client_reference_id', $client_reference_id);
        session()->put('productData.client_type', $client_type);
        session()->put('productData.clientName', $clientName);
        
       
        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
        $testingMode = true;
        $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        
        $packages = DB::table('packages')->where('package_status', '1')->get();
        return view('payment.package',[
            'client_reference_id'=>$client_reference_id,
            'clientName'=>$clientName, 
            'packages' => $packages, 
            'client_type' => $client_type,
            'pfHost'=>$pfHost,
            'data'=>$data,
            'default_package'=>$default_package
            ]);
    }
    public function skipPackage($client_reference_id,$client_type){
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $_SESSION['client_reference_id_sent'] = $client_reference_id;
        $main_client = DB::select("SELECT * FROM `clients` where client_type = '".$client_type."' and client_reference_id = '".$client_reference_id."' ");
        
        $the_user_id= $main_client[0]->user_id;
        $userObj = DB::select("SELECT id FROM `users` where id ='$the_user_id' ");
        $userId = $userObj[0]->id;
        DB::table('users')->where('id', $userId)->update(['skip_status' => '0']);

        $bank_name = 0;
        return view('fna.overview', ['bank_name' => $bank_name,'client_reference_id'=>$client_reference_id, 'client_type'=>$client_type,'SpouseDataExits'=>'','reserveFundsDataMonths'=>'','clientData'=>'','monthFinalName'=>'']);

        
    }
    public function makePayment(Request $request){
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $client_reference_id = $request['client_reference_id'];
        $client_type = $request['client_type'];
        $client_package_id = $request['client_package'];
        
        $packages_price = DB::table('packages')->where('package_status', '1')->where('id', $client_package_id)->pluck('package_price');
        $main_client = DB::table('clients')->where('client_reference_id', $client_reference_id)->where('client_type', $client_type)->first(['first_name','last_name','email']);
        
        $first_name = isset($main_client->first_name) ? $main_client->first_name : '-';
        $last_name = isset($main_client->last_name) ? $main_client->last_name : '-'."!!";
        $email = isset($main_client->email) ? $main_client->email : '-';
        
        $cartTotal = $packages_price[0]; // This amount needs to be sourced from your application
        $passphrase = 'jt7NOE43FZPn';
        
        $data = array(
            // Merchant details
            'merchant_id' => '13079774',
            'merchant_key' => '54hvi0in3qwkr',
            'return_url' => 'https://fna2.phpapplord.co.za/return.php',
            'cancel_url' => 'https://fna2.phpapplord.co.za/cancel.php',
            'notify_url' => 'https://fna2.phpapplord.co.za/notify.php',
            // Buyer details
            'name_first' => $first_name,
            'name_last'  => $last_name,
            'email_address'=> $email,
            // Transaction details
            'm_payment_id' => '', //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => 'Order#171992'
        );
        
        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;// If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
        $testingMode = false;
        $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $htmlForm = '<form action="https://'.$pfHost.'/eng/process" method="post">';
        foreach($data as $name=> $value)
        {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
        }
        $htmlForm .= '<input type="submit" value="Pay Now" /></form>';
        echo $htmlForm;
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
    
    public function finalPayment (Request $request) {
        return view('payment.finalPayment');
    }
    public function storeClientPackage(Request $request) {
        dd($request->all());
    }
    public function successPayment(Request $request) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        if( null == (Session::get('productData')) ) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }  
        $productData = Session::get('productData');
        $user_id	     =  $productData['client_reference_id'];
        $client_type	 =  $productData['client_type'];
        $client_name	 =  $productData['clientName'];
        $order_nr    =  str_replace('Order#', '', $productData['item_name']);
        $price_sum   =  'paid';
        $package_id  =  $productData['m_payment_id'];
        $price       =  $productData['amount'];
        $activation_date_time = date("Y-m-d h:i");
        $packages_duration = DB::table('packages')->where('package_status', '1')
                                                    ->where('id', $package_id)
                                                    ->first(['duration','bank_statement_count','kyc_count','credit_report_count','aml_count','property_upload_count','vehicle_upload_count', 'cpb_asset_count', 'cpb_liability_count', 'astute_count']);
       
        $duration = isset($packages_duration->duration) ? $packages_duration->duration : '0';

        $order_bank_statement_count = isset($packages_duration->bank_statement_count) ? $packages_duration->bank_statement_count : '0';
        $order_kyc_count = isset($packages_duration->kyc_count) ? $packages_duration->kyc_count : '0';
        $order_credit_count = isset($packages_duration->credit_report_count) ? $packages_duration->credit_report_count : '0';
        $order_aml_count = isset($packages_duration->aml_count) ? $packages_duration->aml_count : '0';
        $order_property_count = isset($packages_duration->property_upload_count) ? $packages_duration->property_upload_count : '0';
        $order_vehicle_count = isset($packages_duration->vehicle_upload_count) ? $packages_duration->vehicle_upload_count : '0';


        $order_cpb_asset_count = isset($packages_duration->cpb_asset_count) ? $packages_duration->cpb_asset_count : '0';
        $order_cpb_liability_count = isset($packages_duration->cpb_liability_count) ? $packages_duration->cpb_liability_count : '0';
        $order_astute_count = isset($packages_duration->astute_count) ? $packages_duration->astute_count : '0';


        $expiry_date_time = date('Y-m-d h:i', strtotime("+".$duration." months", strtotime($activation_date_time)));;
        $insertData = array(
            'user_id'=>$user_id,
            'client_type'=>$client_type,
            'client_name'=>$client_name,
            'order_nr'=>$order_nr,
            'price_sum'=>$price_sum,
            'package_id'=>$package_id,
            'price'=>$price,
            'order_date'=>date('y-m-d'),
            'activation_date_time'=>$activation_date_time,
            'expiry_date_time'=>$expiry_date_time,
            'order_bank_statement_count'=>$order_bank_statement_count,
            'order_credit_count'=>$order_credit_count,
            'order_property_count'=>$order_property_count,
            'order_vehicle_count'=>$order_vehicle_count,
            'order_kyc_count'=>$order_kyc_count,
            'order_aml_count'=>$order_aml_count,
            'order_cpb_asset_count'=>$order_cpb_asset_count,
            'order_cpb_liability_count'=>$order_cpb_liability_count,
            'order_astute_count'=>$order_astute_count
            );
        $lastId     = DB::table('orders')->insertGetId($insertData);
        $userData   = DB::table('orders')->where('id', $lastId)->first();
        session()->forget($productData);
        session()->flush();
        return view('payment.successPayment',['userData'=>$userData]);
    }
    public function cancelPayment(Request $request) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $productData = Session::get('productData');
        $client_reference_id	     =  $productData['client_reference_id'];
        $client_type	 =  $productData['client_type'];
        $client_name	 =  $productData['clientName'];
        session()->forget($productData);
        session()->flush();
        return view('payment.cancelPayment',['client_name'=>$client_name,'client_reference_id'=>$client_reference_id,'client_type'=>$client_type]);
    }
    public function notifyPayment(Request $request) {
        dd($request->all());
    }
    public function successExtraFetaurePayment(Request $request) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $productData            =  Session::get('productData');    
        $user_id                =  $productData['client_reference_id'];
        $client_type            =  $productData['client_type'];
        $client_name            =  $productData['clientName'];
        $order_nr               =  str_replace('Order#', '', $productData['item_name']);
        $price_sum              =  'paid';
        $package_id             =  Session::get('package_id');
        $price                  =  $productData['amount'];
        $feature_id             =  $productData['m_payment_id'];
        $feature_name           =  Session::get('title');
        $package_count          =  Session::get('package_count')+Session::get('prev_bank_upload_count'); 
        $prev_order_id           =  Session::get('prev_order_id');
        
        $insertData = array(
            'user_id'=>$user_id,
            'client_type'=>$client_type,
            'client_name'=>$client_name,
            'package_id'=>$package_id,
            'feature_id'=>$feature_id,
            'feature_name'=>$feature_name,
            'feature_price'=>$price,
            'order_nr'=>$order_nr,
            'order_date'=>date("Y-m-d h:i"),
            'activation_date_time'=>Session::get('activation_date_time'),
            'expiry_date_time'=>Session::get('expiry_date_time'),
            'order_status'=>'paid'
            );
        
        $lastId     = DB::table('order_features')->insertGetId($insertData);
        $userData   = DB::table('order_features')->where('id', $lastId)->first();

        DB::select("UPDATE orders set order_bank_statement_count = '".$package_count."' where id = '$prev_order_id'"); 
        session()->forget($productData);
        session()->flush();
        return view('payment.successExtraFetaurePayment',['userData'=>$userData]);
    }
    
}