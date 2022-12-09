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

class PaymentPropertyController extends Controller
{
    public function successPropertyPayment(Request $request) {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        if( null == (Session::get('productPropertyData')) ) {
            // return redirect()->route('whatamiworth', ['client_reference_id' => $_SESSION['client_reference_id'], 'client_type' => $_SESSION['client_type']]);
            return redirect()->route('whatamiworth', ['client_reference_id' => $_SESSION['client_reference_id_sent'] , 'client_type' => 'Main Client']);
        }
        $productData = Session::get('productPropertyData');
        if(isset($productData['client_reference_id'])) {
            $user_id = $productData['client_reference_id'];
            $client_type = $productData['client_type'];
            $client_name = $productData['clientName'];
            $order_nr = str_replace('Order#', '', $productData['item_name']);
            $price_sum = 'paid';
            $package_id = $productData['m_payment_id'];
            $feature_id = 0;
            if(strpos($productData['m_payment_id'],"_") > 0){
                $arrPayment = explode("_", $productData['m_payment_id']);
                $package_id = $arrPayment[0];
                $feature_id = $arrPayment[1];
            }
            $feature_name = $productData['featureName'];
            $price = $productData['amount'];
            $activation_date_time = date("Y-m-d h:i");
            $packages = DB::table('orders')
                            ->where('user_id',$user_id)
                            ->where('client_type', $client_type)
                            ->orderBy('id','DESC')
                            ->first(['activation_date_time', 'expiry_date_time']);
            $activation_date_time = $expiry_date_time = "";
            if(isset($packages)) {
                $activation_date_time = $packages->activation_date_time;
                $expiry_date_time = $packages->expiry_date_time;
            }
            $activation_date_time = $activation_date_time;
            $expiry_date_time = $expiry_date_time;
            $insertData = array(
                'user_id'  =>  $user_id,
                'client_type'  =>  $client_type,
                'client_name' => $client_name,
                'order_number' => $order_nr,
                'order_status' => $price_sum,
                'package_id' => $package_id,
                'feature_id' => $feature_id,
                'feature_name' => $feature_name,
                'feature_price' => $price,
                'order_date' => date('y-m-d'),
                'activation_date_time' => $activation_date_time,
                'expiry_date_time' => $expiry_date_time
                );
            $lastId     = DB::table('order_features')->insertGetId($insertData);
            $userData   = DB::table('order_features')->where('id', $lastId)->first();
            

            /**
             * fetch the value of order_property_count from order table
             * and increment it by 1
             */
            $features_count = DB::table('extra_purchase')     
                ->where('p_id', '5') // ['CPB assets' ])
                ->pluck('count');            
            $property_count = 0;
            $order_property_count = DB::table('orders')
                                        ->where('user_id', $user_id)
                                        ->orderBy('id', 'DESC')
                                        ->first(['order_property_count','id']);
            $property_count = $order_property_count->order_property_count + $features_count[0];;

            /**
             * Updating the order table with the count number = count number + 1
             * on the basis of features selected
             * i.e Property
             */
            DB::table('orders')
                    ->where('user_id', $user_id)
                    ->where('id',$order_property_count->id)
                    ->update([
                    'order_property_count' => $property_count
                ]);

            // dd($request->all());
            session()->forget('productPropertyData');
            session()->flush();
            return view('payment.successVehiclePayment',['userData' => $userData]);
        }
    }
    public function cancelPropertyPayment(Request $request) {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        if( null == (Session::get('productPropertyData')) ) {
            return redirect()->route('whatamiworth', ['client_reference_id' => $_SESSION['client_reference_id'], 'client_type' => $_SESSION['client_type']]);
        }
        $productData = Session::get('productPropertyData');
        if(isset($productData['client_reference_id'])) {
            $user_id = $productData['client_reference_id'];
            $client_type = $productData['client_type'];
            $client_name = $productData['clientName'];
            $order_nr = str_replace('Order#', '', $productData['item_name']);
            $price_sum = 'cancel';
            $package_id = $productData['m_payment_id'];
            $feature_id = 0;
            if(strpos($productData['m_payment_id'],"_") > 0){
                $arrPayment = explode("_", $productData['m_payment_id']);
                $package_id = $arrPayment[0];
                $feature_id = $arrPayment[1];
            }
            $feature_name = $productData['featureName'];
            $price = $productData['amount'];
            $activation_date_time = date("Y-m-d h:i");
            $packages = DB::table('orders')
                            ->where('user_id',$user_id)
                            ->where('client_type', $client_type)
                            ->orderBy('id','DESC')
                            ->first(['activation_date_time', 'expiry_date_time']);
            $activation_date_time = $expiry_date_time = "";
            if(isset($packages)) {
                $activation_date_time = $packages->activation_date_time;
                $expiry_date_time = $packages->expiry_date_time;
            }
            $activation_date_time = $activation_date_time;
            $expiry_date_time = $expiry_date_time;
            $insertData = array(
                'user_id'  =>  $user_id,
                'client_type'  =>  $client_type,
                'client_name' => $client_name,
                'order_number' => $order_nr,
                'order_status' => $price_sum,
                'package_id' => $package_id,
                'feature_id' => $feature_id,
                'feature_name' => $feature_name,
                'feature_price' => $price,
                'order_date' => date('y-m-d'),
                'activation_date_time' => $activation_date_time,
                'expiry_date_time' => $expiry_date_time
                );
            $lastId     = DB::table('order_features')->insertGetId($insertData);
            $userData   = DB::table('order_features')->where('id', $lastId)->first();
        }
        session()->forget('productPropertyData');
        session()->flush();
        return view('payment.cancelFeaturePayment',['userData' => $userData]);
    }
    public function notifyPropertyPayment(Request $request) 
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        } 
        echo "notifyFeaturePayment";
        dd($request->all());
    }
    
}