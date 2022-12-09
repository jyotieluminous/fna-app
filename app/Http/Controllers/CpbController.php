<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;



class CpbController extends Controller
{
    public function kyc($user) {
        
        
        $client = DB::table('clients')
                    ->select('users.*', 'clients.*')
                    ->join('users', 'clients.user_id', '=', 'users.id')
                    ->where('clients.id', $user)
                    ->first();
    
        $data = $this->handleKYC($client);
        
        if($data->http_code == '401')
        {
            return redirect()->back()->with(['information' => 'Id Not Whitelisted']);
            
        }else if($data->http_code == '200')
        {
            
            if(!Storage::disk('public_kyc_uploads')->put("kyc_$client->idNumber.pdf", base64_decode($data->EncodedPDF))) {
            return false;
            }
            
            $file = public_path()."/uploads/kyc/kyc_$client->idNumber.pdf";
        
            return response()->file($file);
            
        }else {
            
            return redirect()->back()->with(['information' => 'Something went wrong, please try again later']);

        }
        
         

    }
    
    public function cpb($user)
    {
        $client = DB::table('clients')
            ->select('users.*', 'clients.*')
            ->join('users', 'clients.user_id', '=', 'users.id')
            ->where('clients.id', $user)
            ->first();
    
        $data = $this->handleCPBReport($client);
        
        if($data->http_code == '401')
        {
            return redirect()->back()->with(['information' => 'Id Not Whitelisted']);
            
        }else if($data->http_code == '200')
        {
            
            if(!Storage::disk('public_cpb_report')->put("cpb_$client->idNumber.pdf", base64_decode($data->EncodedPDF))) {
            return false;
            }
            
            $file = public_path()."/uploads/cpb/cpb_$client->idNumber.pdf";
        
            return response()->file($file);
            
        }else {
            
            return redirect()->back()->with(['information' => 'Something went wrong, please try again later']);

        }
    }
    
    public function handleCPBReport($client) {
        $token = $this->getToken();
        
        $response = Http::post('https://expertaatestapi.bureauhouse.co.za/wrapper/creditreport', [
        'Token' => $token,
        'IDNumber' => $client->idNumber,
        'Surname' => $client->last_name,
        'Reference' => 'Test',
        'InputPerson' => 'QA',
        'EnquiryDoneBy' => 'Christopher Burger',
        'PermissiblePurpose' => 'Internal Use',
        'EnquiryReason' => 'QA'
        ]);
        
        return $response->object();
    }

    public function getToken() {
            
            $response = Http::post('https://expertaatestapi.bureauhouse.co.za/token/token', [
            'AccountNumber' => '300174',
            'UserCode' => 'T_3001740001',
            'BureauName' => 'APITEST',
            'Password' => 'ZPVKT9ESS',
            'CallingModule' => 'Integration'
        ]);
        
        $response = json_decode($response);

        return $token = $response->Results[0];
    }

    public function handleKYC($client) {
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://expertaatestapi.bureauhouse.co.za/wrapper/kyc',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array('Token' => $this->getToken(),
          'IDNumber' => $client->idNumber,
          'Reference' => 'Test',
          'Surname' => $client->last_name,
          'UseDHAExtra' => '',
          'Reference' => 'Test',
          'TemplateName' => 'Test',
          'InputPerson' => '{
          "IDNumber": "'.$client->idNumber.'",
          "FirstName": "'.$client->first_name.'",
          "Surname": "'.$client->last_name.'"
        }','InputAddress' => '{
          "Line1": "'.$client->address.'",
          "Line2": "'.$client->city.'",
          "Line3": "'.$client->state.'",
          "Line4": "",
          "Line5": "",
          "PostCode": "'.$client->zip.'"
        }','PermissiblePurpose' => 'Internal Use'),
        ));
        

        $response = curl_exec($curl);
        
        curl_close($curl);
        
        $response = json_decode($response);
        
        return $response;

    }
}
