<?php

    //namespace App;
    use Illuminate\Support\Facades\Http;
     //var_dump();
    //  $testObject = new Cpb();
    // var_dump($testObject->getToken());
    class Cpb
    {
        
        protected $token;
        
        function __construct() {
             echo "Test";
            //creditscores();
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
            'IDNumber' => "8905025056088",
            'Surname' => "Els",
            'Reference' => 'Test',
            'InputPerson' => 'QA',
            'EnquiryDoneBy' => 'Christopher Burger',
            'PermissiblePurpose' => 'Internal Use',
            'EnquiryReason' => 'QA'
            ]);
            
            return $response->object();

        }
        
        public function kyc() {
            $token = $this->getToken();
            
     
            $clientObj = (object) ['FirstName' => "Abrie", 'Surname' => "Els"];
            $addressObj = (object) ['Address Line' => "6 Emily Hobhouse, kookrus, meyerton ", 'PostCode' => "1961"];
            $emailTemplate = (object) ["EmailAddresses" => "", "TotalRecords" =>  "", "TotalReturnedRecords" => ""];
            $telephoneTemplate = (object) ["Telephones" => "", "BusinessTelephones" => "", "TotalRecords" => "", "TotalReturnedRecords" => ""];
            $addressTemplate = (object) ["Addresses" => "", "TotalRecords" => "", "TotalReturnedRecords" => ""];
            $peopleTemplate = (object) ["People" => "", "TotalRecords" => "", "TotalReturnedRecords" => ""];
            
            
            dd([
            "Token" => $token,
            "IDNumber" => "8905025056088",
            "Surname" =>"Els",
            'PermissiblePurpose' => 'Internal Use',
            "UseDHAExtra" => "test",
           "Reference" => "Test",
           "Version" => "1",
          "TemplateName" => "TemplateName",
          "TemplateID" => "TemplateID",
           "AllDetails" => "AllDetails",
           "InputPerson" => json_encode($clientObj),
           "InputAddress" => json_encode($addressObj),
           "InputTelephone" => json_encode($client->phone),
           "InputEmail" => "abrie.els@gmail.com",
           "InputEmployer" => "test",
           "PeopleTemplate" => json_encode($peopleTemplate),
           "AddressTemplate" => json_encode($addressTemplate),
           "TelephoneTemplate" => json_encode($telephoneTemplate),
           "EmailTemplate" => json_encode($emailTemplate),
           "EmployerTemplate" => json_encode((object)[])
            ]);
             $response = Http::acceptJson()->post('https://apitest.bureauhouse.co.za/wrapper/kyc', [
            "Token" => $token,
            "IDNumber" => "8905025056088",
            "Surname" =>"Els",
            'PermissiblePurpose' => 'Internal Use',
            "UseDHAExtra" => "test",
           "Reference" => "Test",
           "Version" => "1",
          "TemplateName" => "TemplateName",
          "TemplateID" => "TemplateID",
           "AllDetails" => "AllDetails",
           "InputPerson" => json_encode($clientObj),
           "InputAddress" => json_encode($addressObj),
           "InputTelephone" => json_encode($client->phone),
           "InputEmail" => "abrie.els@gmail.com",
           "InputEmployer" => "test",
           "PeopleTemplate" => json_encode($peopleTemplate),
           "AddressTemplate" => json_encode($addressTemplate),
           "TelephoneTemplate" => json_encode($telephoneTemplate),
           "EmailTemplate" => json_encode($emailTemplate),
           "EmployerTemplate" => json_encode((object)[])
            ]);
            
            dd($response);
            
        }
        
        // public function screening_report($client) {
            
        //     $token = $this->getToken();
            
        //     $response = Http::post('https://apitest.bureauhouse.co.za/wrapper/screeningreport', [
        //     'Token' => $token,
        //     'PermissiblePurpose' => 'Internal Use',
        //     'Reference' => 'Test',
        //     'Term' => 'Test',
        //     'Refinement' => 'QA',
        //     'Scope' => 'default',
        //     'Categories' => 'Adverse Media',
        //     'WatchListIDs' => ''
        //     ]);
        //     dd($response->body());
        // }
        
    }
?>