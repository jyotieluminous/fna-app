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

class PaymentFeatureController extends Controller
{
    public function successFeaturePayment(Request $request) {
        echo '<pre>';
        print_r(Session::get('productFeatureVehicleData'));
        echo "successFeaturePayment";
        
        $productData = Session::get('productFeatureVehicleData');
        if(isset($productData['client_reference_id'])) {
            echo "inside";
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
            $feature_name = $productData['packageName'];
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
             * Updating the order table with the count number = count number + 1
             * on the basis of features selected
             * 0-fna,1-lighstone Property,2-lightstone vehicle, 3-CPB
             */
            if ($feature_id == "1") {
                /**
                 * fetch the value of order_property_count from order table
                 * and increment it by 1
                 */
                $property_count = 0;
                $order_property_count = DB::table('orders')
                                            ->where('user_id', $user_id)
                                            ->orderBy('id', 'DESC')
                                            ->first(['order_property_count','id']);
                $property_count = $order_property_count->order_property_count + 1;

                /**
                 * update the orders table with the new value in the column
                 * 
                 */
                DB::table('orders')
                        ->where('user_id', $user_id)
                        ->where('id',$order_property_count->id)
                        ->update([
                        'order_property_count' => $property_count
                    ]);
            } elseif($feature_id == "2") {
                /**
                 * fetch the value of order_property_count from order table
                 * and increment it by 1
                 */
                $vehicle_count = 0;
                $order_vehicle_count = DB::table('orders')
                                            ->where('user_id', $user_id)
                                            ->orderBy('id', 'DESC')
                                            ->first(['order_vehicle_count','id']);
                $vehicle_count = $order_vehicle_count->order_vehicle_count + 1;
                /**
                 * update the orders table with the new value in the column
                 * 
                 */
                DB::table('orders')
                        ->where('user_id', $user_id)                
                        ->where('id',$order_vehicle_count->id)
                        ->update([
                            'order_vehicle_count' => $vehicle_count
                        ]);
            } elseif($feature_id == "3") {
                /**
                 * fetch the value of order_property_count from order table
                 * and increment it by 1
                 */
                $credit_count = 0;
                $order_credit_count = DB::table('orders')
                                            ->where('user_id', $user_id)
                                            ->orderBy('id', 'DESC')
                                            ->first(['order_credit_count','id']);
                $credit_count = $order_credit_count->order_credit_count + 1;
                /**
                 * update the orders table with the new value in the column
                 * 
                 */
                DB::table('orders')->where('user_id', $user_id)
                        ->where('id',$order_credit_count->id)
                        ->update([
                            'order_credit_count' => $credit_count
                        ]);
            }

            // dd($request->all());
            session()->forget('productFeatureData');
            session()->flush();
            return view('payment.successFeaturePayment',['userData' => $userData]);
        }
    }
    public function cancelFeaturePayment(Request $request) {
        // echo "cancelFeaturePayment";
        $productData = Session::get('productFeatureData');
        // echo "<pre>";
        // print_r($productData);
        $client_reference_id = $productData['client_reference_id'];
        $client_type = $productData['client_type'];
        $client_name = $productData['clientName'];
        if(isset($productData['client_reference_id'])) {
            echo "inside";
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
            $feature_name = $productData['packageName'];
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
        session()->forget($productData);
        session()->flush();
        return view('payment.cancelFeaturePayment',['userData' => $userData]);
    }
    public function notifyFeaturePayment(Request $request) {
        echo "notifyFeaturePayment";
        dd($request->all());
    }
    
}