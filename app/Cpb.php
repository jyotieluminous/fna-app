<?php

    namespace App;
    use Illuminate\Support\Facades\Http;
    
    class Cpb
    {
        
        protected $token;
        
        function __construct() {
            // echo "Test";
            // creditscores();
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
        
        public function creditscores($client)
        {
        
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
        
        public function kyc($client) {
            $token = $this->getToken();

            $url = "https://expertaatestapi.bureauhouse.co.za/wrapper/kyc";
            // $clientObj = (object) ['IDNumber' => '7707077777087', 'FirstName' => "CHRISTOPHER", 'Surname' => "BURGER"];
            // $addressObj = (object) ['Line1' => "32 VYFDE STREET", "Line2" => "RUSTHOF", 'Line3' => 'STRAND', 'Line4' => '', 'Line5' => '', 'PostCode' => "7140"];
            // $inputTelephone = (object) ['IDNumber' => '7707077777087', 'TelNumber' => '0846138000' ];
            // $emailTemplate = (object) ["EmailAddresses" => "", "TotalRecords" =>  "", "TotalReturnedRecords" => ""];
            // $telephoneTemplate = (object) ["TelNumber" => "0607735293", "IDNumber" => "9208025743089"];
            // $addressTemplate = (object) ["Addresses" => "meyesdal alberton", "TotalRecords" => "", "TotalReturnedRecords" => ""];
            // $peopleTemplate = (object) ["People" => "", "TotalRecords" => "", "TotalReturnedRecords" => ""];
            

            // $response = Http::post($url, array(
            //   "Token" => $token,
            //   "PermissiblePurpose" => "Internal Use",
            //   "IDNumber" => "7707077777087",
            //   "Surname" => "BURGER",
            //   "Reference" => "Test",
            //   "Version" => "1",
            //   "TemplateName" => "Test",
            //   "TemplateID" => "string",
            //   "AllDetails" => "string",
            //   "InputPerson" => '{
            //   "InputIDNumber": "",
            //   "IDNumber": "7707077777087",
            //   "ConsumerHashID": "",
            //   "Passport": "",
            //   "FirstName": "CHRISTOPHER",
            //   "SecondName": "",
            //   "ThirdName": "",
            //   "Surname": "BURGER",
            //   "MaidenName": "",
            //   "DateOfBirth": "",
            //   "Age": "",
            //   "AgeBand": "",
            //   "Title": "",
            //   "IsMinor": "",
            //   "InputIDPassedCDV": "",
            //   "InputIDIsOldFormat": "",
            //   "IDExists": "",
            //   "Gender": "",
            //   "MarriageDate": "",
            //   "MaritalStatus": "",
            //   "Score": "",
            //   "Country": "",
            //   "Source": "",
            //   "OriginalSource": "",
            //   "LatestDate": "",
            //   "UsingDHARealtime": "",
            //   "Reference": ""
            // }',
            //   "InputAddress" => '{
            //   "Line1": "32 VYFDE STREET",
            //   "Line2": "RUSTHOF",
            //   "Line3": "STRAND",
            //   "Line4": "",
            //   "Line5": "",
            //   "PostCode": "7140"
            // }',
            //   "InputTelephone" => '{
            //   "IDNumber": "7707077777087",
            //   "IDNumberTen": "",
            //   "Passport": "",
            //   "TelNumber": "0846138000",
            //   "TelType": "",
            //   "FirstDate": "",
            //   "LatestDate": "",
            //   "FirstStatus": "",
            //   "LatestStatus": "",
            //   "Score": "",
            //   "IsDiallable": "",
            //   "IsValid": "",
            //   "Links": "",
            //   "BusinessName": "",
            //   "Region": "",
            //   "Network": "",
            //   "Source": "",
            //   "KYCSource": "",
            //   "Reference": "",
            //   "Surname": "",
            //   "FirstNames": "",
            //   "Occurrences": ""
            // }',
            //   "InputEmail" => "string",
            //   "InputEmployer" => "string",
            //   "PeopleTemplate" => '{
            //   "People": "",
            //   "TotalRecords": "",
            //   "TotalReturnedRecords": ""
            // }',
            //   "AddressTemplate" => '{
            //   "Addresses": "",
            //   "TotalRecords": "",
            //   "TotalReturnedRecords": ""
            // }',
            //   "TelephoneTemplate" => '{
            //   "Telephones": "",
            //   "BusinessTelephones": "",
            //   "TotalRecords": "",
            //   "TotalReturnedRecords": ""
            // }',
            //   "EmailTemplate" => '{
            //   "EmailAddresses": "",
            //   "TotalRecords": "",
            //   "TotalReturnedRecords": ""
            // }',
            //   "EmployerTemplate" => '{}',
            // ));
            
            // dd($response);
            
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
              CURLOPT_POSTFIELDS => array(
                  'Token' => $token,
                  'IDNumber' => '7707077777087',
                  'Reference' => 'Test',
                  'Surname' => 'BURGER',
                  'UseDHAExtra' => '',
                  'Reference' => 'Test',
                  'TemplateName' => 'Test',
              'InputPerson' => '{
              "IDNumber": "7707077777087",
              "FirstName": "Abraham",
              "Surname": "Els"}',
              'InputAddress' => '{
              "Line1": "47 Handel Ave",
              "Line2": "Kookrus",
              "Line3": "Meyerton",
              "Line4": "",
              "Line5": "",
              "PostCode": "1961"
            }',
            'PermissiblePurpose' => 'Internal Use'
            ),
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            echo $response;
        }
        
      
    }
?>