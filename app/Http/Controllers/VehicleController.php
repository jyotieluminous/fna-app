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

class VehicleController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    public function index($client_reference_id,$client_type)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $myPublicFolder = public_path();
        $savePath = $myPublicFolder."/vehicle/response.txt";
        $fh = fopen($savePath,'r');
        $dataInsert = array();
        while ($line = fgets($fh)) {
           $vehicleData = json_decode($line);
           foreach($vehicleData as $vehicle){
               if($vehicle->Description == 'Full Model Description')
               {
                    $dataInsert['vehicleDescription']= $vehicle->Description; 
                    $dataInsert['vehicleLable']= $vehicle->Value; 
               }
               if($vehicle->Description == 'Cost Estimate')
               {
                    $dataInsert['vehicleCostLable']= $vehicle->Description; 
                    $dataInsert['vehicleCost']= $vehicle->Value; 
               }
           }
        }
        $getAssetLiabilityId = DB::select("SELECT *  FROM `asset_liability_types` WHERE `asset_liability_type` = 1 AND `name` = 'Moveable assets'");  
        if(isset($getAssetLiabilityId)) {
             $AssetID = $getAssetLiabilityId[0]->id;
        } else {
            $AssetID = 0;
        }
        $insertDataArray = [
                'advisor_id'=>$userId,
                'capture_advisor_id'=>$userId,
                'client_reference_id'=>$client_reference_id,
                'client_type'=>$client_type,
                'asset_type'=>$AssetID,
                'asset_sub_type'=>'',
                'asset_description'=>$dataInsert['vehicleLable'],
                'asset_amount'=>$dataInsert['vehicleCost'],
                'apply_to_event'=>'',
                'allocation_death'=>'50',
                'allocation_disability'=>'50',
                'allocation_dread_disease'=>'50',
                'allocation_impairment'=>'50',
                'cgt_asset_type'=>'',
                'cgt_bona_fide_farm'=>'',
                'cgt_acquisition_date'=>date('y-m-d'),
                'cgt_initial_value'=>'0',
                'cgt_expenditure'=>'0',
                'cgt_disposal_cost'=>'0',
                'asset_executor_value'=>'0',
                'subject_to_cgt'=>''
            ];
            $chkExistsAsset = DB::table('client_assets')->where('asset_description','like', '' . $dataInsert['vehicleLable'] . '%')->count();
            // echo $chkExistsAsset; die;
            if($chkExistsAsset === 0) {
                $insuranceId = DB::table('client_assets')->insert($insertDataArray);
                $message = "Vehicle inserted successfully";
            } else {
                $message = "Vehicle already exists";
            }
        fclose($fh);
        echo $message;
    }
   
   public function getPropertyId() {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://apis.lightstone.co.za/lspsearch/v1/address',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "maxRowsToReturn": 1,
          "propertyType": "3",
          "streetNumber": "6",
          "streetName": "kliprivier",
          "streetType": "street",
          "suburb": "Kookrus",
          "estateName": "",
          "town": "Meyerton",
          "province": "gauteng",
          "postCode": "1961",
          "deedsOfficeCode": "string",
          "township": "string",
          "erfNumber": 131
        }
        ',
          CURLOPT_HTTPHEADER => array(
            'Ocp-Apim-Subscription-Key: ed2993d082d9474b9ecfa0aa363ba676',
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        echo $response;
   }
   
   public function automatedValuationModel($propertyId = '') {
       
   }

   public function evm($propertyId = '') {
       
   }    
   
}