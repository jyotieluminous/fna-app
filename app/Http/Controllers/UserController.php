<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use App\Company;
use App\Panaceaapi;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUser;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Rules\MatchOldPassword;
use App\Http\Requests\UserStore;
use App\Cpb;
use App\Lightstone;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function __construct()
    {
        // $this->middleware('auth');
    }
    
        public function changePassword() {
        return view('users.change_password');
    } 
    
    public function populate_liabilities_lightstone($client_ref, $client_type) {
        dd('populate liabilities via lightstone');
    }
    
    public function populate_assets_property($client_ref, $client_type) { 
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        /**
         * Fetching client address to populate
         */
        $client = DB::table('clients')
                    ->where('client_type', 'Main Client')
                    ->where('client_reference_id', $client_ref)
                    ->first();

        if (!empty($client->address)) { echo 'it has data'; } else { echo 'it has not';}
                    // dd($client);
        if(isset($client) && (!empty($client->address)) ) {
                $clientAddress = $client->address . ' ' . $client->city . ', ' . $client->state . ', ' . $client->zip; // . ', ' . $client->country ;
                // $clientAddress = "6 Emily hobhouse, kookrus, meyerton"; 
        } else {
                $tourl = url('/clientEdit',['client_reference_id'=>$client_ref]);
                session()->flash('failure', "Please fill in full address details for Populating property with lightstone at Client Update page!");
                return redirect()->back(); 
        }
        // echo $clientAddress;
        // die();
        /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 3)
                            ->where('title', 'Property')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'Property';
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/LightstoneProperty',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "LightstonePropertyFullAddress": "'.$clientAddress.'"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
        $response = curl_exec($curl);
        // dd(json_decode($response));
        curl_close($curl);
        
      DB::table('integrationData')->insert([
            'ClientReference' => $client_ref,
            'jsonobject' => json_encode($response),
            'Type' => 'LightstoneProperty'
            ]);
        /**
         * Please dont delete the below insert.
         * Its keeping track for the count of lightstone Property API is consumed
         */
        DB::table('api_hit_details')->insert([
            'id' => null,
            'client_id' => $client->id,
            'client_reference_id' => $client_ref,
            'client_type' => $client_type,
            'api_name' => $api_title,
            'api_id' => $api_id,
            'consumed_date' => DB::raw('now()'),
            'jsonobject' => json_encode($response)
        ]);
        // echo  "<pre>";
        $response = (array) json_decode($response, true);
        // print_r($response[0]);
        
        $property = (array) json_decode($response[0]) ;
        // print_r($property);
        
        // foreach($property as $key=>$value)
        //   {
        //         $mainArray = (array) $value;
        //         foreach ($mainArray as $key1 => $value1){
        //             $elementArray = (array) $value1;
        //             foreach($elementArray as $key2 => $value2) {
        //                 if($key2 === "predictedValue") {
        //                     $propertyAsset['cost'] = ($value2) ? $value2 : 0; 
        //                 }
        //                 if($key2 === "propertyID") {
        //                     $propertyAsset['propertyID'] = ($value2) ? $value2 : 0; 
        //                 }
        //             }
        //         }
        //   }
        
            foreach($property as $key=>$value)
          {     
                if(isset($property[2]))
                {
                    $propertyAsset['cost'] = $property[2]->predictedValue ? $property[2]->predictedValue: 0; 

                }else {
                    $propertyAsset['cost'] = 0;
                }

                $mainArray = (array) $value;

                foreach ($mainArray as $key1 => $value1){
                    $elementArray = (array) $value1;
                  
                    foreach($elementArray as $key2 => $value2) {

                        if($key2 === "propertyID") {
                            $propertyAsset['propertyID'] = ($value2) ? $value2 : 0; 
                        }
                    }
                }
          }
    $propertyAsset['description'] = $clientAddress;
    // print_r($propertyAsset);
      /**fetch category id = Fixed assets
      */
        $fetchCategoryId = DB::table("asset_liability_types")
                                ->select('id')
                                ->where('name', 'Fixed Assets')
                                ->where('asset_liability_type','1')
                                ->first();
        if(isset($fetchCategoryId)) {
            $propertyAsset['typeId'] = $fetchCategoryId->id;
        }
        $asset_count = DB::table('client_assets')
                    ->where('asset_description', $clientAddress)
                    ->where('asset_sub_type', $clientAddress)
                    ->count();
        if($asset_count == 0) {    
            $propertyAsset['cost'] = isset($propertyAsset['cost']) ?  $propertyAsset['cost'] : "0";
            DB::table('client_assets')->insert([
            'advisor_id' => $_SESSION['userId'],
            'capture_advisor_id' => $_SESSION['userId'],
            'client_reference_id' => $client_ref,
            'client_type' => $client_type,
            'asset_type' => $propertyAsset['typeId'],
            'asset_sub_type' => $propertyAsset['description'],
            'asset_description' => $propertyAsset['description'],
            'asset_amount' => $propertyAsset['cost'],
            'apply_to_event' => 'Buy',
            'allocation_death' => 0.00,
            'allocation_disability' => 0.00,
            'allocation_dread_disease' => 0.00,
            'allocation_impairment' => 0.00,
            'subject_to_cgt' => 'Yes',
            'cgt_asset_type' => 'Test',
            'cgt_bona_fide_farm' => 'Test',
            'cgt_acquisition_date' => Date::now(),
            'cgt_initial_value' => $propertyAsset['cost'],
            'cgt_expenditure' => $propertyAsset['cost'],
            'cgt_disposal_cost' => $propertyAsset['cost'],
            'asset_executor_value' => $propertyAsset['cost'],
            'source_of_import' => '1' //1 = Lightstone Property
            ]);
            session()->flash('success', 'Assets property populated via lightstone!');
        } else {
            session()->flash('failure', 'Assets property already populated via lightstone!');
        }
        return redirect()->back();          
    }
    public function populate_assets_lightstone($client_ref, $client_type) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        /**
         * Fetching client id to insert in the details
         */
        $client = DB::table('clients')
                    ->where('client_type', $client_type)
                    ->where('client_reference_id', $client_ref)
                    ->first();
        /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 2)
                            ->where('title', 'Vehicle')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'Vehicle';
        }
        /**
         * Fetching client Vehicle vin number to populate
         */
        $client = DB::table('clients')
                    ->where('client_type', $client_type)
                    ->where('client_reference_id', $client_ref)
                    ->first();
        // dd($client);
        // echo ($client->vehicle_vin_number);
        if(isset($client) && isset($client->vehicle_vin_number) ) {
            $clientVehicle = $client->vehicle_vin_number ;
            isset($client->vehicle_vin_number) ? $clientVehicle = $client->vehicle_vin_number : "";
        } else {
            session()->flash('failure', "Please fill in Vehicle Vin number for Populating vehicle details with lightstone at Personal Info page!");
            return redirect()->back(); 
        }
        // die($clientVehicle);
        $lightStone = new Lightstone();
        $response = $lightStone->LightstoneVehicle($clientVehicle);
        //$response = $lightStone->LightstoneVehicle('MBJM29BT302034731');
        //  echo "<pre>";print_r(json_decode($response));//die;
        DB::table('integrationData')->insert([
            'ClientReference' => $client_ref,
            'jsonobject' => json_encode($response),
            'Type' => 'LightstoneVehicle'
            ]);
        
        $arr = (array) json_decode($response, true);
        DB::table('api_hit_details')->insert([
            'id' => null,
            'client_id' => $client->id,
            'client_reference_id' => $client_ref,
            'client_type' => $client_type,
            'api_name' => $api_title,
            'api_id' => $api_id,
            'consumed_date' => DB::raw('now()'),
            'jsonobject' => json_encode($response)
        ]);
        if(isset($arr[0])) {
            $arr2 = json_decode($arr[0]);
        }
        $prev = $prevCost = 0;
        $assetData = [];
        // foreach($arr2 as $vehicleItems) {
        //     $vehicleItemsArr = (array)$vehicleItems;
        //     foreach ($vehicleItemsArr as $index => $value) {
        //         if($prev === 1) {
        //             $prev = 0;
        //             $assetData['description'] = $value;
        //         }
        //         if($value==="Full Model Description" ) {
        //             $prev = 1;
        //         }
        //         if($prevCost === 1) {
        //             $prevCost = 0;
        //             $assetData['cost'] = $value;
        //         }
        //         if($value==="Cost Estimate" ) {
        //             $prevCost = 1;
        //         }
        //     }
        // }
        
                foreach(collect($arr2) as $vehicleItem)
        {
            if($vehicleItem->Description == "Cost Estimate")
            {
           
                $assetData['cost'] = $vehicleItem->Value;
            }

            if($vehicleItem->Description == "Full Model Description")
            {
                $assetData['description'] = $vehicleItem->Value;
            }
        }
        
        $fetchCategoryId = DB::table("asset_liability_types")
                                ->select('id')
                                ->where('name', 'Moveable assets')
                                ->where('asset_liability_type','1')
                                ->first();
        if(isset($fetchCategoryId)) {
            $assetData['typeId'] = $fetchCategoryId->id;
        }
    if(isset($assetData['description']) && !empty($assetData['description']) ) {
        $asset_count = DB::table('client_assets')
                    ->where('asset_description', $assetData['description'])
                    ->where('client_reference_id', $client_ref)
                    ->where('client_type', $client_type)
                    ->count();

        if($asset_count == 0) {                
            DB::table('client_assets')->insert([
            'advisor_id' => $_SESSION['userId'],
            'capture_advisor_id' => $_SESSION['userId'],
            'client_reference_id' => $client_ref,
            'client_type' => $client_type,
            'asset_type' => $assetData['typeId'],
            'asset_sub_type' => $assetData['description'],
            'asset_description' => $assetData['description'],
            'asset_amount' => $assetData['cost'],
            'apply_to_event' => 'Buy',
            'allocation_death' => 0.00,
            'allocation_disability' => 0.00,
            'allocation_dread_disease' => 0.00,
            'allocation_impairment' => 0.00,
            'subject_to_cgt' => 'Yes',
            'cgt_asset_type' => 'Test',
            'cgt_bona_fide_farm' => 'Test',
            'cgt_acquisition_date' => Date::now(),
            'cgt_initial_value' => $assetData['cost'],
            'cgt_expenditure' => $assetData['cost'],
            'cgt_disposal_cost' => $assetData['cost'],
            'asset_executor_value' => $assetData['cost'],
            'source_of_import' => '2' //1 = Lightstone Vehicle
            ]);
            session()->flash('success', 'Assets vehicle populated via lightstone!');
        } else {
            session()->flash('failure', 'Assets vehicle already populated via lightstone!');
        }
    } else {
        session()->flash('failure', 'Blank data is returned via lightstone!');
    }
        return redirect()->back(); 
    }
    
    public function populate_client_vehicle_assets(Request $request) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        // print_r($request->all());
        $client_type = $request->client_type;
        $client_reference_id = $request->client_reference_id;
        /**
         * Fetching client id to insert in the details
         */
        $client = DB::table('clients')
                    ->where('client_type', $client_type)
                    ->where('client_reference_id', $client_reference_id)
                    ->first();
        /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 2)
                            ->where('title', 'Vehicle')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'Vehicle';
        }

        $clientVehicle = $request->vehicle_vin_number ;

        // die($clientVehicle);
        $lightStone = new Lightstone();
        $response = $lightStone->LightstoneVehicle($clientVehicle);
        $response = $lightStone->LightstoneVehicle('MBJM29BT302034731');
        //  echo "<pre>";print_r(json_decode($response));//die;
        DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($response),
            'Type' => 'LightstoneVehicle'
            ]);
        
        $arr = (array) json_decode($response, true);
        DB::table('api_hit_details')->insert([
            'id' => null,
            'client_id' => $client->id,
            'client_reference_id' => $client_reference_id,
            'client_type' => $client_type,
            'api_name' => $api_title,
            'api_id' => $api_id,
            'consumed_date' => DB::raw('now()'),
            'jsonobject' => json_encode($response)
        ]);
        if(isset($arr[0])) {
            $arr2 = json_decode($arr[0]);
        }
        $prev = $prevCost = 0;
        $assetData = [];

        foreach(collect($arr2) as $vehicleItem) {
            // if($vehicleItem->Description == "Cost Estimate") {
            //     $assetData['cost'] = $vehicleItem->Value;
            // }
            // if($vehicleItem->Description == "Full Model Description") {
            //     $assetData['description'] = $vehicleItem->Value;
            // }
            
            if($vehicleItem->Description == "Retail Estimate") {
                $assetData['cost'] = $vehicleItem->Value;
            }
            if($vehicleItem->Description == "Full Model Description") {
                $assetData['description'] = $vehicleItem->Value;
            }
        }
        // dd($assetData);
        $fetchCategoryId = DB::table("asset_liability_types")
                                ->select('id')
                                ->where('name', 'Moveable assets')
                                ->where('asset_liability_type','1')
                                ->first();
        if(isset($fetchCategoryId)) {
            $assetData['typeId'] = $fetchCategoryId->id;
        }
    if(isset($assetData['description']) && !empty($assetData['description']) ) {
        $asset_count = DB::table('client_assets')
                    ->where('asset_description', $assetData['description'])
                    ->where('client_reference_id', $client_reference_id)
                    ->where('client_type', $client_type)
                    ->count();

        if($asset_count == 0) {                
            DB::table('client_assets')->insert([
            'advisor_id' => $_SESSION['userId'],
            'capture_advisor_id' => $_SESSION['userId'],
            'client_reference_id' => $client_reference_id,
            'client_type' => $client_type,
            'asset_type' => $assetData['typeId'],
            'asset_sub_type' => $assetData['description'],
            'asset_description' => $assetData['description'],
            'asset_amount' => $assetData['cost'],
            'apply_to_event' => 'Buy',
            'allocation_death' => 0.00,
            'allocation_disability' => 0.00,
            'allocation_dread_disease' => 0.00,
            'allocation_impairment' => 0.00,
            'subject_to_cgt' => 'Yes',
            'cgt_asset_type' => 'Test',
            'cgt_bona_fide_farm' => 'Test',
            'cgt_acquisition_date' => Date::now(),
            'cgt_initial_value' => $assetData['cost'],
            'cgt_expenditure' => $assetData['cost'],
            'cgt_disposal_cost' => $assetData['cost'],
            'asset_executor_value' => $assetData['cost'],
            'vehicle_vin_number' => $clientVehicle,
            'source_of_import' => '2' //1 = Lightstone Vehicle
            
            ]);
            session()->flash('success', 'Assets vehicle populated via lightstone!');
        } else {
            session()->flash('failure', 'Assets vehicle already populated via lightstone!');
        }
    } else {
        session()->flash('failure', 'Blank data is returned via lightstone!');
    }
        return redirect()->back();        
    }
    
    public function populate_assets_lightstone_old($client_ref, $client_type) {
        $curl = curl_init();
        // LightstoneVehicle
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/LightstoneVehicle',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
            "LightstoneVehicle": "MBJM29BT302034731"
        }',
        //   CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/json'
        //   ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
          DB::table('integrationData')->insert([
            'ClientReference' => $client_ref,
            'jsonobject' => json_encode($response),
            'Type' => 'LightstoneVehicle'
            ]);
        $arr = (array) json_decode($response, true);
        
        if(isset($arr[0])) {
            $arr2 = json_decode($arr[0]);
        }
    
        $prev = $prevCost = 0;
        $assetData = [];
        foreach($arr2 as $vehicleItems) {
            $vehicleItemsArr = (array)$vehicleItems;
            foreach ($vehicleItemsArr as $index => $value) {
                if($prev === 1) {
                    $prev = 0;
                    $assetData['description'] = $value;
                }
                if($value==="Full Model Description" ) {
                    $prev = 1;
                }
                if($prevCost === 1) {
                    $prevCost = 0;
                    $assetData['cost'] = $value;
                }
                if($value==="Cost Estimate" ) {
                    $prevCost = 1;
                }
            }
        }

    /*Working fine with keys*/
    //     foreach($arr2 as $vehicleItems) {
    //     $vehicleItemsArr = (array)$vehicleItems;
    //     $current = 'Description';
    //     $keys = array_keys($vehicleItemsArr);
    //     print_r($keys);
    //     $ordinal = (array_search($current,$keys)+1)%count($keys);
    //     print_r($ordinal);
    //     $next = $keys[$ordinal];
    //     echo $next;
    //     print_r($vehicleItemsArr[$next]);
    // }

        $fetchCategoryId = DB::table("asset_liability_types")
                                ->select('id')
                                ->where('name', 'Moveable assets')
                                ->where('asset_liability_type','1')
                                ->first();
        if(isset($fetchCategoryId)) {
            $assetData['typeId'] = $fetchCategoryId->id;
        }
        $asset_count = DB::table('client_assets')
                    ->where('asset_description', $assetData['description'])
                    ->where('asset_sub_type', $assetData['description'])
                    ->count();
        if($asset_count == 0) {                
            DB::table('client_assets')->insert([
            'advisor_id' => $_SESSION['userId'],
            'capture_advisor_id' => $_SESSION['userId'],
            'client_reference_id' => $client_ref,
            'client_type' => $client_type,
            'asset_type' => $assetData['typeId'],
            'asset_sub_type' => $assetData['description'],
            'asset_description' => $assetData['description'],
            'asset_amount' => $assetData['cost'],
            'apply_to_event' => 'Buy',
            'allocation_death' => 0.00,
            'allocation_disability' => 0.00,
            'allocation_dread_disease' => 0.00,
            'allocation_impairment' => 0.00,
            'subject_to_cgt' => 'Yes',
            'cgt_asset_type' => 'Test',
            'cgt_bona_fide_farm' => 'Test',
            'cgt_acquisition_date' => Date::now(),
            'cgt_initial_value' => $assetData['cost'],
            'cgt_expenditure' => $assetData['cost'],
            'cgt_disposal_cost' => $assetData['cost'],
            'asset_executor_value' => $assetData['cost'], 
            'source_of_import' => 'Lightstone Property'
            ]);
            session()->flash('success', 'Assets populated via lightstone!');
        } else {
            session()->flash('failure', 'Assets already populated via lightstone!');
        }
        return redirect()->back(); 
    }
    
    public function populate_assets($client_ref, $client_type) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $client = DB::table('clients')
                        ->select('clients.*', 'users.idNumber')
                        ->join('users', 'clients.user_id', '=', 'users.id')
                        ->where('clients.client_reference_id', $client_ref)
                        ->where('clients.client_type', $client_type)
                        ->first();
        /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 5)
                            ->where('title', 'CPB assets')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'CPB assets';
        }   
                    
        $cpb = new Cpb;
         
        $response = $cpb->creditscores($client);
        
   
        DB::table('integrationData')->insert([
            'ClientReference' => $client_ref,
            'jsonobject' => json_encode($response),
            'Type' => 'CPB'
            ]);

        if($response->status_message == 'OK')
        {
            DB::table('api_hit_details')->insert([
                'id' => null,
                'client_id' => $client->id,
                'client_reference_id' => $client_ref,
                'client_type' => $client_type,
                'api_name' => $api_title,
                'api_id' => $api_id,
                'consumed_date' => DB::raw('now()'),
                'jsonobject' => json_encode($response)
            ]); 
            $assets = $response->Properties->DeedsRecords;
            
            foreach($assets as $asset)
            {
               
                if($asset->IsCurrentOwner == 'True')
                {
                    $asset_type = DB::table('asset_liability_types')->where('name', 'Fixed Assets')->first();
                    
                    $asset_count = DB::table('client_assets')
                                        ->where('client_reference_id', $client_ref)
                                        ->where('asset_description', $asset->BondHolder)
                                        ->where('asset_sub_type', $asset_type->name)
                                        ->count();
                    if($asset_count == 0)
                    {
                        //captured data needs to be revisited
                        DB::table('client_assets')->insert([
                            'advisor_id' => $_SESSION['userId'],
                            'capture_advisor_id' => $_SESSION['userId'],
                            'client_reference_id' => $client_ref,
                            'client_type' => $client_type,
                            'asset_type' => $asset_type->id,
                            'asset_sub_type' => $asset_type->name,
                            'asset_description' => $asset->BondHolder,
                            'asset_amount' => $asset->PurchaseAmount,
                            'apply_to_event' => 'Buy',
                            'allocation_death' => 0.00,
                            'allocation_disability' => 0.00,
                            'allocation_dread_disease' => 0.00,
                            'allocation_impairment' => 0.00,
                            'subject_to_cgt' => 'Yes',
                            'cgt_asset_type' => 'Test',
                            'cgt_bona_fide_farm' => 'Test',
                            'cgt_acquisition_date' => Date::now(),
                            'cgt_initial_value' => $asset->PurchaseAmount,
                            'cgt_expenditure' => $asset->PurchaseAmount,
                            'cgt_disposal_cost' => $asset->PurchaseAmount,
                            'asset_executor_value' => $asset->PurchaseAmount, 
                            'source_of_import' => '3' //'source_of_import' => '3' //3 = CPB
                            ]);
                    }
                }
            }
            session()->flash('success', 'Assets populated via CPB!');
            return redirect()->back(); 
        } else {
            dd('client ID not whitelisted');
        }
    }
    
    public function populate_liabilities($client_ref, $client_type) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $client = DB::table('clients')
                        ->select('clients.*', 'users.idNumber')
                        ->join('users', 'clients.user_id', '=', 'users.id')
                        ->where('clients.client_reference_id', $client_ref)
                        ->where('clients.client_type', $client_type)
                        ->first();
                /**
        * Fetching api id to insert in the details
        */
        $api_id = $api_title = "";
        $api_details = DB::table('extra_purchase')
                            ->where('p_id', 6)
                            ->where('title', 'CPB Liabilities')
                            ->pluck('p_id')
                            ->first();  
        if(isset($api_details)) {
            $api_id = $api_details;      
            $api_title = 'CPB Liabilities';
        }   

         $cpb = new Cpb;
         
        $response = $cpb->creditscores($client);
        
        //  dd($response);
  
        if($response->status_message == 'OK')
        {
            DB::table('api_hit_details')->insert([
                'id' => null,
                'client_id' => $client->id,
                'client_reference_id' => $client_ref,
                'client_type' => $client_type,
                'api_name' => $api_title,
                'api_id' => $api_id,
                'consumed_date' => DB::raw('now()'),
                'jsonobject' => json_encode($response)
            ]);  
            $liabilities = $response->PPDetail->PaymentProfile;
            
            foreach($liabilities as $liability)
            {
                
                if($liability->AccountStatus == 'Active')
                {
                    if($liability->AccountType != 'Open Services' && $liability->AccountType != 'Short Term Insurance')
                    {
                        $liability_type = DB::table('asset_liability_types')->where('name', 'Loan')->first();
                    
                        $liability_count = DB::table('client_liabilities_new')
                                                ->where('client_reference_id', $client_ref)
                                                ->where('type_of_business', $liability->CreditProvider)
                                                ->where('policy_number', $liability->AccountNumber)
                                                ->count();
                                                
                        $asset_liability_types = DB::table('asset_liability_types')->where('name',  $liability->AccountType)->first();
                        
                        if($liability_count == 0)
                        {
                            DB::table('client_liabilities_new')->insert([
                            'advisor_id' => $_SESSION['userId'],
                            'capture_advisor_id' => $_SESSION['userId'],
                            'client_reference_id' => $client_ref,
                            'client_type' => $client_type,
                            'liability_type' => $asset_liability_types ? $asset_liability_types->id : 10, 
                            'liability_sub_type' => $liability->CreditProvider,
                            'policy_number' => $liability->AccountNumber,
                            'outstanding_balance' => $liability->AmountOverdue,
                            'under_advice' => $liability->Source,
                            'type_of_business' => $liability->CreditProvider,
                            'original_balance' => $liability->OpeningBalanceCreditLimit,
                            'loan_application_amount' => $liability->OpeningBalanceCreditLimit,
                            'thelimit' => $liability->OpeningBalanceCreditLimit,
                            'principal_repaid' => intval($liability->RepaymentFrequency) * $liability->InstallmentAmount,
                            'last_updated_by' => $liability->DateOfLastPayment,
                            'interest_rate_type' => 'Fixed Interest', //cant get data
                            'interest_rate_pa' => 0, // cant get data
                            /*'loan_term' => 6, //cant get data
                            'loan_term_value' => 6 * $liability->InstallmentAmount, //cant get data*/
                            'repayment_amount' => $liability->InstallmentAmount,
                            'repayment_frequency' => $liability->RepaymentFrequencyDescription,
                            'select_asset_type' => 'Asset', //??,
                            'source_of_import' => '3'
                            ]);
                        }
                    }

                }
               
            }
            
            session()->flash('success', 'Liabilities populated via CPB!');
            
            return redirect()->back();
        }else {
            dd('client ID not whitelisted');
        }
        
        
        
    }
    
    public function income_expense_notes($client_reference_id) {
        session_start();

        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $notes = DB::table('income_expense_notes')->where('user_id', $_SESSION['userId'])->where('client_reference_id', $client_reference_id)->first();

        return view('users.create_notes', ['notes' => $notes, 'client_reference_id' => $client_reference_id]);
    }

    public function income_expense_notes_store(Request $request, $client_reference_id) {
        session_start();

        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }


        if(isset($_SESSION['userId']))
        {
            $notes = $request->notes;
            $user_id = $_SESSION['userId'];

            $notes_count = DB::table('income_expense_notes')->where('user_id', $user_id)->where('client_reference_id', $client_reference_id)->count();

            if($notes_count == 0)
            {
                DB::table('income_expense_notes')->insert([
                    'notes' => $notes,
                    'user_id' => $user_id,
                    'client_reference_id' => $client_reference_id
                ]);

                $request->session()->flash('success', 'Notes created successfully');


                return redirect()->route('createIncomeExpense', ['client_reference_id' => $client_reference_id]);

            }else {

                DB::table('income_expense_notes')->where('user_id', $user_id)->where('client_reference_id', $client_reference_id)->update([
                    'notes' => $notes
                ]);

                $request->session()->flash('success', 'Notes updated successfully');


                return redirect()->route('fetchIncomeExpense', ['client_reference_id' => $client_reference_id]);

            }
        }
    }


      public function signature($signature = null) {

    

        if($signature)
        {
            $filename = explode('.', $signature)[0];
        }else {
    
            $filename = '';
        }
    
      
        return view('signature')->with(['filename' => $filename]);
    }
    
        public function authenticateUser() {
        if(!isset($_SESSION)) { session_start(); }


        if(!isset($_SESSION['email']) || !isset($_SESSION['password']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $email = $_SESSION['email']; 
        $password = $_SESSION['password'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://fnaapi.phpapplord.co.za/index.php/Registration/login',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array('email' => $email,'password' => $password),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        //("<pre>"); var_dump($response->success); die();
        if(isset($response->success))
        {
            $_SESSION['login'] = 'yes';
            $_SESSION['token'] = $response->success->message->token;
            $_SESSION['userId'] = $response->success->message->data->user_id;
            $_SESSION['userType'] = $response->success->message->data->user_type;///$users[0]->type;
            $_SESSION['group_id'] = $response->success->message->data->group_id;
            $_SESSION['roleId'] = $response->success->message->data->group_id;

              if($response->success->message->data->group_id == 16)
            {
                $client = DB::table('clients')->where('user_id', $response->success->message->data->user_id)->first();
                $_SESSION['client_reference_id'] = $client->client_reference_id;
                $_SESSION['client_type'] = $client->client_type;

                return redirect()->route('clientEdit', ['id' => $client->client_reference_id]);
            }

            return redirect()->route('clientList');
        }
        else
        {
           return redirect()->back()->with('message', 'Wrong Login Credential'); 
        }
        
    }
    
    public function resetPassword(Request $request)
    {       
        session_start();

        $request->validate([
            'currentPassword' => ['required', new MatchOldPassword],
            'newPassword' => ['required'],
            'password_confirmation' => ['same:newPassword'],
        ]);


        DB::table('users')
            ->where('id', $_SESSION['userId'])
            ->update(['password'=> Hash::make($request->newPassword)]);


        return redirect()->back()->with('success', 'Password changed successfully');
    }
    
    public function resetuserpassword(Request $request,$client_reference_id,$client_type,$user_id,$active_code)
    {       
        return view('users.resetUserPassword',['client_reference_id'=>$client_reference_id,'client_type'=>$client_type,'user_id'=>$user_id,'active_code'=>$active_code,'message'=>'']);
    }
    public function updatePassword(Request $request)
    {       
        $client_reference_id =  $request->client_reference_id;
        $client_type =  str_replace("_"," ",$request->client_type);
        $user_id =  $request->user_id;
        $active_code =  $request->active_code;
        $password =  $request->password;
        
        $checkUserActive = DB::table('users')->where('id', $user_id)->where('active_status','0')->first();
        if(!empty($checkUserActive)){   
          
            if($checkUserActive->active_status == 0){
                $checkUserActivation = DB::table('users_activation')->where('user_id', $user_id)->where('remember_token', $active_code)->first();
                $getUserData = DB::table('clients')->where('user_id', $user_id)->first();
                $user_id = $getUserData->user_id;
                $final_password =password_hash($password, PASSWORD_BCRYPT);
                if(isset($checkUserActivation) && !empty($checkUserActivation))
                {
                  $updatePassword = DB::select("UPDATE users set password = '".$final_password."' where id = '".$user_id."'");
                  $updateStatus = DB::select("UPDATE users set active_status = '1' where id = '".$user_id."'");
                  return redirect('/')->with('message', 'Password updated successfully '); 
                }
                else
                {
                    return redirect()->back()->with('message', 'Activation code does not match'); 
                }
            }
            else
            {
                return redirect()->back()->with('message', 'User already active'); 
            }
        }
        else
        {
            return redirect()->back()->with('message', 'Wrong username'); 
        }
        
    }
    
     public function companyAdvisorView(){
        if(!isset($_SESSION)) { session_start(); }
        return view('users.componyAdvisor');
    }
    
    public function selectClientRegisterOption(Request $request) {

        if(!isset($_SESSION)) { session_start(); }
        if($request->client_option == 'manually')
        {

            return redirect()->route('clientList');

        }else {

            return redirect()->route('upload_csv');
        }
    }

        public function storeCompanyAdvisor(Request $request)
    {
        // dd($request->all());
                if(!isset($_SESSION)) { session_start(); }

        $request->validate([
            'company_name' => 'required',
            'company_email' => 'required|email',
            'company_phone' => 'required',
            'company_address' => 'required',
            'astute_code' => 'required',
            'advisor_name' => 'required',
            'advisor_surname' => 'required',
            'email' => 'required|email|unique:users',
            'advisor_phone' => 'required',
            'advisor_gender' => 'required',
            'advisor_idNo' => 'required',
            'advisor_dob' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);
        
        

        $user_id = DB::table('users')->insertGetId([
            'email' => $request->email,
            'name' => $request->advisor_name,
            'surname' => $request->advisor_surname,
            'idNumber' => $request->advisor_idNo,
            'type' => 'Main Advisor',
            'phone' => $request->advisor_phone,
            'gender' => $request->advisor_gender,
            'dob' => $request->advisor_dob,
            'active_status' => true,
            'password' => Hash::make($request->password),
            'seed_analytics_broker_code' => $request->seed_code,
            'king_price_broker_code' => $request->king_code
        ]);

        $company_id = DB::table('company')->insertGetId([
            'name' => $request->company_name,
            'address' => $request->company_address,
            'billing_email' => $request->company_email,
            'reception_phone' => $request->company_phone
        ]);

        $reqion_id = DB::table('company_regions')->insertGetId([
            'region_name' => $request->company_address,
            'region_address' => $request->company_address,
            'is_active' => true,
            'company_id' => $company_id

        ]);

        $company_id = DB::table('advisors')->insertGetId([
            'company_id' => $company_id,
            'user_id' => $user_id,
            'is_active' => true,
            'region_id' => $reqion_id,
            'parent_id' => $company_id,
            'type' => 'Main Advisor'
        ]);

        DB::table('permissions')->insert([
            'groupId' => 14,
            'userId' => $user_id,
        ]);


        $_SESSION['email'] = $request->email;
        $_SESSION['password'] = $request->password;

        $this->authenticateUser();

        $request->session()->flash('success', 'User created successfully');



       return redirect()->route('clientRegistrationOptions');
    }
    
    public function companyAdvisorPlanView(){
        return view('users.companyAdvisorPlan');
    }

        public function upload_csv() {
        if(!isset($_SESSION)) { session_start(); }
        if(!isset($_SESSION['email']) || !isset($_SESSION['password']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
	    if(empty($_SESSION['login'])) 	
        {      	
            header("location: https://fna2.phpapplord.co.za/public/");	
            exit;	
        } 
        return view('users.upload_csv');
    }
    public function import_clients(Request $request) {
        if(!isset($_SESSION)) { session_start(); }
        if(empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        unset($_SESSION["csv_client_reference_id"]);
        $userId = $_SESSION['userId'];
        $allowed = array('csv');

        $filename = $_FILES['upload']['name'];

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $clientInfo = array();
        $clientReference = "";

        if (in_array($ext, $allowed)) {
            $handle = fopen($_FILES['upload']['tmp_name'], "r");
           
    		$headers = fgetcsv($handle, 1000, ",");

    		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
    		{
    		    $clientInfo[] = $data;
    		}
    
            fclose($handle);
            $rowCount = 0;
            $error_msg = '';
            $error = 1;
            $success = 0;
            foreach($clientInfo as $client_info)
            {
                $addClientError = 0;
                if(strtoupper($client_info[0]) == "MAIN CLIENT")
                {
                    $main_email = $client_info[3];
                    $main_id_number = $client_info[7];;
    
                    //  * Checking unique email address
                    $uniqueEmail = DB::table('users')
                                        ->where('email',$main_email)
                                        ->orWhere('idNumber',$main_id_number)
                                        ->count();
                    if($uniqueEmail > 0) {
                        $error_msg .= "Row ".$rowCount." - ". $main_email . " address OR ".$main_id_number . " number already exists in users.\n";
                        $addClientError = 1;
                    } 
                    //  * Checking unique email in clients table
                    $uniqueEmailClient = DB::table('clients')
                                        ->where('email',$main_email)
                                        ->count();
                    if($uniqueEmailClient > 0) {
                        $error_msg .= $main_email . " number already exists in clients.\n";
                        $addClientError = 1;
                    }
                    if($addClientError === 0) {
                        $clientNumber = DB::select("SELECT * FROM clientNumbering order by id desc");

                        if(empty($clientNumber)) {
                            $new_client_reference = "fna000000001";
                        } else {
                            $count = $clientNumber[0]->num + 1;
                            DB::table('clientNumbering')->insert([
                                'id' => null,
                                'num' => $count,
                            ]);
                            $dbId = strlen($count);
                            $calculate = 12 - $dbId;
                            //echo $calculate;
                            $myzeros = "";
                            for($i = 0; $i < $calculate; $i++)
                            {
                            $myzeros .= "0";
                            }
                            $new_client_reference = "fna".$myzeros.$count;
                        } 
                        $_SESSION['csv_client_reference_id'] = $new_client_reference;
                        
                        $client_reference_id = $_SESSION["csv_client_reference_id"];

                        // $clientReference = $new_client_reference;
                        // $client_reference_id = $clientReference;
                        $advisor_id = $userId;
                        //print_r("<pre>"); var_dump($_POST); die(); 
                        $main_first_name = $client_info[1];
                        $main_last_name = $client_info[2];
                        $main_email = $client_info[3];
                        $main_birth_day = $client_info[4];
                        $main_retirement_age = $client_info[5];
                        $main_gender =$client_info[9];
                        $main_marital_status = $client_info[6];
                        $main_client_type = $client_info[0];
                        $capture_user_id = $userId;
                        $main_id_number = $client_info[7];;
                        $main_phone =  $client_info[8];
                        // $main_password = md5($main_first_name.'-'.$main_last_name);
                        $main_password = Hash::make('123456');
                        $marriage_type = '1';

                        //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
                        //$saveMainData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_email','$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')");
                        $lastUserMainID = DB::table('users')->insertGetId(
                        [
                            'email' => $main_email, 
                            'password'=>$main_password,
                            'name' => $main_first_name, 
                            'surname' => $main_last_name, 
                            'idNumber' => $main_id_number, 
                            'type' => 'Client',
                            'phone' => $main_phone,
                            'gender' => $main_gender,
                            'dob' => $main_birth_day
                        ]);

                        DB::table('permissions')->insert([
                            'groupId' => 16,
                            'userId' => $lastUserMainID
                        ]);

                        $saveMainIDData = DB::table('clients')->insertGetId(
                        [
                            'advisor_id' => $advisor_id, 
                            'first_name'=>$main_first_name,
                            'last_name' => $main_last_name, 
                            'email' => $main_email, 
                            'date_of_birth' => $main_birth_day, 
                            'retirement_age' => $main_retirement_age,
                            'gender' => $main_gender,
                            'marital_status' => $main_marital_status,
                            'client_type' => $main_client_type,
                            'capture_user_id' => $capture_user_id,
                            'client_reference_id' => $client_reference_id,
                            'user_id'=>$lastUserMainID,
                            'marriage_type'=>$marriage_type
                
                        ]);
                        $success++;
                        $_SESSION['client_reference_id'] = $client_reference_id;
                    }
                }
                if(strtoupper($client_info[0]) == "SPOUSE" && isset($_SESSION['csv_client_reference_id'])) {
                    $main_email = $client_info[3];
                    $main_id_number = $client_info[7];;
    
                    //  * Checking unique email address
                    $uniqueEmail = DB::table('users')
                                        ->where('email',$main_email)
                                        ->orWhere('idNumber',$main_id_number)
                                        ->count();
                    if($uniqueEmail > 0) {
                        $error_msg .= "Row ".$rowCount." - ". $main_email . " address OR ".$main_id_number . " number already exists in users\n";
                        $addClientError = 1;
                    }                    
                    // Checking unique email in clients table
                    $uniqueEmailClient = DB::table('clients')
                                        ->where('email',$main_email)
                                        ->count();
                    if($uniqueEmailClient > 0) {
                        $error_msg .= $main_email . " number already exists in clients.\n";
                        $addClientError = 1;
                    }

                    if($addClientError === 0) {
                        $client_reference_id = $_SESSION["csv_client_reference_id"];

                    // $client_reference_id = $_SESSION['client_reference_id'];
                    $advisor_id = $userId;
                    //print_r("<pre>"); var_dump($_POST); die(); 
                    $main_first_name = $client_info[1];
                    $main_last_name = $client_info[2];
                    $main_email = $client_info[3];
                    $main_birth_day = $client_info[4];
                    $main_retirement_age = $client_info[5];
                    $main_gender =$client_info[9];
                    $main_marital_status = $client_info[6];
                    $main_client_type = $client_info[0];
                    $capture_user_id = $userId;
                    $main_id_number = $client_info[7];;
                    $main_phone =  $client_info[8];
                    $main_password = Hash::make('123456');
                    $marriage_type = '1';

                    //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
                    //$saveMainData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_email','$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')");
                    $lastUserMainID = DB::table('users')->insertGetId(
                    [
                        'email' => $main_email, 
                        'password'=>$main_password,
                        'name' => $main_first_name, 
                        'surname' => $main_last_name, 
                        'idNumber' => $main_id_number, 
                        'type' => 'Client',
                        'phone' => $main_phone,
                        'gender' => $main_gender,
                        'dob' => $main_birth_day
                    ]);

                    DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);
                    
                    $saveMainIDData = DB::table('clients')->insertGetId(
                    [
                        'advisor_id' => $advisor_id, 
                        'first_name'=>$main_first_name,
                        'last_name' => $main_last_name, 
                        'email' => $main_email, 
                        'date_of_birth' => $main_birth_day, 
                        'retirement_age' => $main_retirement_age,
                        'gender' => $main_gender,
                        'marital_status' => $main_marital_status,
                        'client_type' => $main_client_type,
                        'capture_user_id' => $capture_user_id,
                        'client_reference_id' => $client_reference_id,
                        'user_id'=>$lastUserMainID,
                        'marriage_type'=>$marriage_type
            
                    ]);
                    $success++;
                }
            }   
                
                if(strtoupper($client_info[0]) != "MAIN CLIENT"  && strtoupper($client_info[0]) != "SPOUSE" && isset($_SESSION['csv_client_reference_id']))
                {
                    $client_reference_id = $_SESSION["csv_client_reference_id"];
                    DB::table('dependants')->insert([
                        'advisor_id' => $userId,
                        'capture_user_id' => $userId,
                        'client_reference_id' => $client_reference_id,
                        'dependant_type' => $client_info[0],
                        'first_name' => $client_info[1],
                        'last_name' => $client_info[2],
                        'date_of_birth' => $client_info[4],
                        'gender' => $client_info[9],
                        'dependant_until_age' => $client_info[5]
                    ]);
                    $success++;
                }
                $rowCount++;
            }
            $message = "Total row are " . $rowCount . ".\n<br> ";
            $message .= "Successfully inserted rows are " . $success . ".\n<br> ";
            // $final_msg = ($error_msg.$message);
            $final_msg = nl2br($error_msg.$message);
            // $final_msg = str_replace("<br />", "", $final_msg);
            // $request->session()->forget('csv_client_reference_id');
            session()->forget('csv_client_reference_id');
            session()->flash('success', $final_msg );
            return redirect()->route('clientList');
        } else {
            $final_msg = "You are uploading wrong File format";
            session()->flash('success', $final_msg );
            return redirect()->route('clientList');
        }
    }
        public function import_clients_old_08nov2022(Request $request) {
     
        if(!isset($_SESSION)) { session_start(); }
        
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        
        $userId = $_SESSION['userId'];
        $allowed = array('csv');

        $filename = $_FILES['upload']['name'];

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $clientInfo = array();
        $clientReference = "";

        if (in_array($ext, $allowed)) {
            $handle = fopen($_FILES['upload']['tmp_name'], "r");
           
    		$headers = fgetcsv($handle, 1000, ",");

    		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
    		{
    		    $clientInfo[] = $data;
    		}
    
            fclose($handle);
            foreach($clientInfo as $client_info)
            {
                if($client_info[0] == "Main client")
                {
                    $clientNumber = DB::select("SELECT * FROM clientNumbering order by id desc");

                    if(empty($clientNumber))
                    {
                        $new_client_reference = "fna000000001";
                    }
                    else
                    {
            
                        $count = $clientNumber[0]->num + 1;
                        DB::table('clientNumbering')->insert([
                            'id' => null,
                            'num' => $count,
                        ]);
                        $dbId = strlen($count);
                        $calculate = 12 - $dbId;
                        //echo $calculate;
                        $myzeros = "";
                        for($i = 0; $i < $calculate; $i++)
                        {
                           $myzeros .= "0";
                        }
                        $new_client_reference = "fna".$myzeros.$count;
                    } 
                    $clientReference = $new_client_reference;
                    $client_reference_id = $clientReference;
                    $advisor_id = $userId;
                    //print_r("<pre>"); var_dump($_POST); die(); 
                    $main_first_name = $client_info[1];
                    $main_last_name = $client_info[2];
                    $main_email = $client_info[3];
                    $main_birth_day = $client_info[4];
                    $main_retirement_age = $client_info[5];
                    $main_gender ="none";
                    $main_marital_status = $client_info[6];
                    $main_client_type = $client_info[0];
                    $capture_user_id = $userId;
                    $main_id_number = $client_info[7];;
                    $main_phone =  $client_info[8];
                    // $main_password = md5($main_first_name.'-'.$main_last_name);
                    $main_password = Hash::make('123456');
            
                    //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
                    //$saveMainData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_email','$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')");
                    $lastUserMainID = DB::table('users')->insertGetId(
                    [
                        'email' => $main_email, 
                        'password'=>$main_password,
                        'name' => $main_first_name, 
                        'surname' => $main_last_name, 
                        'idNumber' => $main_id_number, 
                        'type' => 'Client',
                        'phone' => $main_phone,
                        'gender' => $main_gender,
                        'dob' => $main_birth_day
                    ]);

                    DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);

                    $saveMainIDData = DB::table('clients')->insertGetId(
                    [
                        'advisor_id' => $advisor_id, 
                        'first_name'=>$main_first_name,
                        'last_name' => $main_last_name, 
                        'email' => $main_email, 
                        'date_of_birth' => $main_birth_day, 
                        'retirement_age' => $main_retirement_age,
                        'gender' => $main_gender,
                        'marital_status' => $main_marital_status,
                        'client_type' => $main_client_type,
                        'capture_user_id' => $capture_user_id,
                        'client_reference_id' => $client_reference_id
            
                    ]);
              
                    $_SESSION['client_reference_id'] = $client_reference_id;
                    
                }
                if($client_info[0] == "Spouse")
                {

                    $client_reference_id = $_SESSION['client_reference_id'];
                    $advisor_id = $userId;
                    //print_r("<pre>"); var_dump($_POST); die(); 
                    $main_first_name = $client_info[1];
                    $main_last_name = $client_info[2];
                    $main_email = $client_info[3];
                    $main_birth_day = $client_info[4];
                    $main_retirement_age = $client_info[5];
                    $main_gender ="none";
                    $main_marital_status = $client_info[6];
                    $main_client_type = $client_info[0];
                    $capture_user_id = $userId;
                    $main_id_number = $client_info[7];;
                    $main_phone =  $client_info[8];
                    $main_password = Hash::make('123456');
                    //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
                    //$saveMainData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_email','$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')");
                    $lastUserMainID = DB::table('users')->insertGetId(
                    [
                        'email' => $main_email, 
                        'password'=>$main_password,
                        'name' => $main_first_name, 
                        'surname' => $main_last_name, 
                        'idNumber' => $main_id_number, 
                        'type' => 'Client',
                        'phone' => $main_phone,
                        'gender' => $main_gender,
                        'dob' => $main_birth_day
                    ]);

                    DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);
                    
                    $saveMainIDData = DB::table('clients')->insertGetId(
                    [
                        'advisor_id' => $advisor_id, 
                        'first_name'=>$main_first_name,
                        'last_name' => $main_last_name, 
                        'email' => $main_email, 
                        'date_of_birth' => $main_birth_day, 
                        'retirement_age' => $main_retirement_age,
                        'gender' => $main_gender,
                        'marital_status' => $main_marital_status,
                        'client_type' => $main_client_type,
                        'capture_user_id' => $capture_user_id,
                        'client_reference_id' => $client_reference_id
            
                    ]);

                   
                }
                if($client_info[0] != "Spouse" && $client_info[0] != "Main client")
                {
                    $client_reference_id = $_SESSION['client_reference_id'];
                    // DB::select("INSERT into dependants values (
                    //     null,
                    //     '".$userId."', 
                    //     '".$userId."', 
                    //     '".$client_reference_id."',
                    //     '".$client_info[0]."',
                    //     '".$client_info[1]."', 
                    //     '".$client_info[2]."', 
                    //     '".$client_info[4]."', 
                    //     'none', 
                    //     '".$client_info[5]."')
                    //     ");

                    DB::table('dependants')->insert([
                        'advisor_id' => $userId,
                        'capture_user_id' => $userId,
                        'client_reference_id' => $client_reference_id,
                        'dependant_type' => $client_info[0],
                        'first_name' => $client_info[1],
                        'last_name' => $client_info[2],
                        'date_of_birth' => $client_info[4],
                        'gender' => $client_info[9],
                        'dependant_until_age' => $client_info[5]
                    ]);
                }
            }
            
            
        }
        else
        {
            echo "You are uploading wrong File format";
        }
        

        return redirect()->route('clientList');
    }

    public function clientRegistrationOptions() {
        return view('users.add_options');
    }

    public function editUserProfile($client_reference_id_sent)
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $_SESSION['client_reference_id_sent'] = $client_reference_id_sent;

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
        
        
        $personal_details = DB::select("SELECT * FROM `personal_details`");

        $user = DB::table('users')->where('id', $_SESSION['userId'])->first();
        

        return view('users.edit', ['user' => $user, 'personal_details' => $personal_details, 'getroleId' => $getroleId,'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
    }

    public function updateUserProfile(UserStore $request, $id)
    {

        DB::table('users')->where('id', $id)->update([
            'email' => $request->email,
            'name' => $request->name,
            'surname' => $request->surname,
            'idNumber' => $request->id_number,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'dob' => $request->dob[0] . '-'. $request->dob[1] . '-' . $request->dob[2]
        ]);


        $request->session()->flash('success', 'User details successfully updated');

        return redirect()->back();
    }  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
     
        return view('user.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if((Auth::user()->getRole()->name == 'General User'))
        {
            abort(403, "You don't have permission to add the user");
        }
        
        if(Auth::user()->getRole()->name == 'Super Admin')
        {
            $companies = Company::all();
        }else{
            
            $companies = [];
        }
        
        return view('user.create', ['companies' => $companies]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUser $request)
    {
        $user = new User();

        // $password = $this->randomPassword();

        $user->name = $request->name;
        $user->id_number = $request->id_number;
        $user->cell_number = $request->cell_number;
        $user->city_town = $request->city;
        $user->street = $request->street;
        $user->code = $request->code;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        
        if(Auth::user()->getRole()->name == 'Super Admin')
        {
            $user->company_id = $request->company_id;
        }else{
            $user->company_id = Auth::user()->company->id;
        }
        
        $role = Role::findOrFail($request->role);

        $user->save();
        $userName = $user->name;
        $idNumber = $request->id_number;
        $section = 'User Management';
        $action = "Created User $userName ID Number: $idNumber";
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
        $user->roles()->attach($role);

       

        $request->session()->flash('success', 'User Successfully Registered!');
        
        // $users = User::where('company_id', Auth::user()->company_id)->where('id', '!=', Auth::id())->get();
        return view('user.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);


        if((Auth::user()->getRole()->name == 'General User' && Auth::user()->id != $user->id) || ($user->getRole()->name == 'Super Admin' && $user->id != Auth::user()->id))
        {
            abort(403, "You don't have permission to edit a user");
        }
        


        return view('user.edit', ['user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $userName = $request->name;
        $idNumber = $request->id_number;
        $section = 'User Management';
        $action = "Updated User $userName ID Number: $idNumber";
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

        $user->name = $request->name;
        $user->email = $request->email;
        $user->id_number = $request->id_number;
        $user->cell_number = $request->cell;
        $user->city_town = $request->city_town;
        $user->street = $request->street;
        $user->code = $request->code;
        $user->save();

        $role = Role::findOrFail($request->role);

        $user->roles()->sync($role);
        $request->session()->flash('success', 'User Updated Succesfully');
        return redirect()->back();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);


        if((Auth::user()->getRole()->name != 'Super Admin' && $user->getRole()->name == 'Super Admin') || $user->id == Auth::id() || Auth::user()->getRole()->name == 'Genaral User')
        {
            abort(403, 'You are not authorized for this action');
        }

        if($user->getRole()->name == 'Company Admin' && count($user->getCompanyAdmins()) <= 1)
        {
            abort(403, 'Delete  Failed, Please ensure there is atleast one Company Admin');
        }

       
        $user->delete();
        $userName =  $user->name;
        $idNumber =  $user->id_number;
        $section = 'User Management';
        $action = "Deleted User $userName ID Number: $idNumber";
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
        return redirect()->back();
    }

    public function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1; 
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass);
    }


    public function edit_password()
    {
        return view('user.change_password');
    }

    public function change_password(Request $request)
    {
        
        $request->validate([
            'currentPassword' => ['required', new MatchOldPassword],
            'newPassword' => ['required'],
            'password_confirmation' => ['same:newPassword'],
        ]);


        User::find(auth()->user()->id)->update(['password'=> Hash::make($request->newPassword)]);

        return redirect()->route('users.index')->with('success', 'Password changed successfully');;

    }
    
    public function validate_accounts($bankHolder, $bankHolderIniValue, $accountNumber, $bankCode, $idNumber, $requestType, $bankAccountType)
    {
    
       $surname = strtoupper($bankHolder);
        $initials = strtoupper($bankHolderIniValue);
        $bankAccountType = strtoupper($bankAccountType);

        $url = 'https://www.easyavs.co.za:7220/AVSService.svc/PartnerServices/RunAVSRequest';

        $xml = "<SRQ>
                 <CR>
                  <U>PieterBen</U>
                  <P>f68668e2c4d695fdf8fd2a486b8453bbbde9f038d1f477943cbb6bfe28e2a9cb</P>
                 </CR>
                 <RL>
                  <R>
                   <CR>Myreference02</CR> 
                   <RT>".$requestType."</RT> 
                   <AT>".$bankAccountType."</AT> 
                   <IT>SID</IT>
                   <IN>".$initials."</IN>
                   <N>".$surname."</N> 
                   <ID>".$idNumber."</ID>
                   <TX></TX>
                   <BC>".$bankCode."</BC>
                   <AN>".$accountNumber."</AN>
                   <PN>0710121602</PN>
                   <EM>demuenator@gmail.com</EM>
                  </R>
                 </RL> 
                </SRQ>";

     
        //Initiate cURL
        $curl = curl_init($url);

        //Set the Content-Type to text/xml.
        curl_setopt ($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
     
        
        if(curl_errno($curl)){
            throw new Exception(curl_error($curl));
        }
        
        curl_close($curl);

       

        $myimage = simplexml_load_string($result);
      
        $myimage = (array) $myimage;
      
        $bankValid = (array) $myimage["ARL"]->AR->RQV;
        $accountNumCheck = (array) $myimage["ARL"]->AR->ACV;
        $accountTypeCheck = (array) $myimage["ARL"]->AR->ATV;
        $IdCheck = (array) $myimage["ARL"]->AR->IDV;
        $InitialCheck = (array) $myimage["ARL"]->AR->INV;
        $accountTypeCheck = (array) $myimage["ARL"]->AR->NV;
       
        $addString = $bankValid[0].$accountNumCheck[0].$accountTypeCheck[0].$IdCheck[0].$InitialCheck[0].$accountTypeCheck[0];
        

        if($addString === "YYYYYY")
        {
            return response()->json(['data' => 'Account Valid'], 200);
        }
        else{
            return response()->json(['data' => 'Account InValid'], 200);
        } 
       
    }

}
