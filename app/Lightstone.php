<?php
namespace App;
use Illuminate\Support\Facades\Http;
    
class Lightstone
{
    function __construct() {}
    function LightstoneVehicle($clientVehicle)
    {
        // die($clientVehicle);
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
             "LightstoneVehicle": "'. $clientVehicle .'"
        }',
        //   CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/json'
        //   ), //"MBJM29BT302034731"
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    public function lightStoneProperty() {
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
            "LightstonePropertyFullAddress": "6 Emily hobhouse, kookrus, meyerton"
        }',
        //   CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/json'
        //   ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}