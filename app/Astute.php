<?php

    //namespace App;
    namespace App;

use PDF;
use App\Astute;
use App\User;
use App\Client;
use App\Invoice;
use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\PaymentGatewayConfig;
use App\Exports\PaymentExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StorePaymentConfig;
// use DB;
use DataTables;
    class Astute
    {
        protected $username;
        protected $password;
        protected $useragent;
        function __construct($username, $password, $useragent) {
            $this->username = $username;
            $this->password = $password;
            $this->useragent = $useragent;
          }
        public function getProductSectorSet()
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/ProductSectorSet',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'"
        
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            return $response;
        }
        
        public function getMessageHeaders($SectorCode)
        {
        
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/MessageHeaders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
        	        "SectorCode": "'.$SectorCode.'"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        
        public function GetProductset($SectorCode)
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/ProductSet',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
        	        "SectorCode":  "'.$SectorCode.'"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function GetPrimarUsers()
        {
        
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/PrimaryUsers',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
            "UserName": "Abrie89",
            "Password": "Applord@1223!!!!!",
            "UserAgent": "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
            "assistantusername":"Abrie89"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function GetProfileGroups()
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/ProfileGroups',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
            "UserName": "Abrie89",
            "Password": "Applord@1223!!!!!",
            "UserAgent": "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
            "profileUsername":"Abrie89"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function GetWebMessageContent($MessageGuidId)
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/WebMessageContent',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
                    "MessageGuidId": "'.$MessageGuidId.'",
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        
        public function GetCombinedMessageHeaders($MessageGuidId)
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/CombinedMessageHeader',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
                    "MessageGuidId": "'.$MessageGuidId.'",
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        function MessageContent($MessageGuidId)
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/CombinedMessageContent',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
                    "MessageGuidId": "'.$MessageGuidId.'",
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
        // 	header("Content-Type: application/json");
            return $response;
        }
        
        
        public function GetCombinedMessageContent($MessageGuidId)
        {
        
        
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/CombinedMessageContent',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
                    "UserName": "'.$this->username.'",
                    "Password": "'.$this->password.'", 
                    "UserAgent": "'.$this->useragent.'",
                    "MessageGuidId": "'.$MessageGuidId.'",
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function ChangePassword()
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/ChangePassword',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
            "UserName": "Abrie89",
            "Password": "Applord@1223!!!!!",
            "UserAgent": "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
            "oldpassword":"Test1234",
            "newpassword":"Test12345"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        public function GetContentProviderSet($CcpProduct)
        {
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/ContentProviderSet',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
        
            "UserName": "'.$this->username.'",
            "Password": "'.$this->password.'", 
            "UserAgent": "'.$this->useragent.'",
            "CcpProduct": "'.$CcpProduct.'"
        
        }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function IntemediaryRequest()
        {
        
            $curl = curl_init();
        
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/IntermediaryRequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
            
                "UserName": "Abrie89",
                "Password": "Applord@1223!!!!!",
                "UserAgent": "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
                "DateOfBirth": "1972-07-23",
                "IdNumber": "7207230081089",
                "Initials": "S",
                "IdType": "1",
                "RequestDetails":"OMU,LIB",
                "CellNumber": "0606401905",
                "EmailAddress": "abrie.els@gmail.com",
                "RequestDigitalConsent": "true",
                "OverrideDigitalConsent": "true",
                "PolicyNumber":"01005296847"
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);
            echo $response;
        }
        
        public function CCPRequest($client_reference_id = '',$client_type = '')
        {
            /**
             * Fetching client address to populate
             */
            $client = DB::table('clients')
                        ->join('users', 'users.id' , '=', 'clients.user_id' )
                        ->where('client_type', $client_type)
                        ->where('client_reference_id', $client_reference_id)
                        // ->toSql();
                        ->first();
                        // dd($client);
            $postParameter = [];
            if(isset($client)) {
                $UserName = isset($client->first_name) ? $client->first_name : "";
                $Password = $client->last_name;
                $UserAgent = "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC";
                $DateOfBirth = isset($client->date_of_birth) ? $client->date_of_birth : "" ;
                $IdNumber = isset($client->idNumber) ? $client->idNumber : "" ; 
                $Initials = isset($client->first_name) ? substr($client->first_name, 0, 1) : "" ;
                $IdType = '1';
                $CellNumber = isset($client->phone) ? $client->phone : "" ; $client->phone;
                $EmailAddress = isset($client->email) ? $client->email : "" ; $client->email;
                $RequestDigitalConsent = 'true';
                $OverrideDigitalConsent = 'false';
                $Surname = isset($client->last_name) ? $client->last_name : "" ; $client->last_name;
                                $postParameter = array(
                                    "UserName"=> "Abrie89",
                                    "Password"=> "Applord@1223!!!!!",
                                    "UserAgent"=> "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
                                    "DateOfBirth"=> date('Y-m-d', strtotime($DateOfBirth)),
                                    "IdNumber"=>  $IdNumber,
                                    "Initials"=> $Initials,
                                    "IdType"=> $IdType,
                                    "RequestDetails"=>"ABSAL,AGL,ALTL,CHTL,DSIL,DSLL,LIBL,METL,MOML,MOMWL,NGLL,OMGPL,OMUL,OUTL,PPSL,SETL,SLML,SLMNAL,STLBL,FMIL,MOMEL" ,
                                    "CellNumber"=> $CellNumber,
                                    "EmailAddress"=> $EmailAddress,
                                    "RequestDigitalConsent"=> "true",
                                    "OverrideDigitalConsent"=> "false",
                                    "Surname"=>$Surname
                                );
            }   
            $postParameter =  json_encode($postParameter);
            // print_r($postParameter);
            // die;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/CCPRequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLINFO_HEADER_OUT => true,
                CURLOPT_POST => true,
                CURLOPT_VERBOSE => true,
                CURLOPT_POSTFIELDS => $postParameter,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                )
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        	header("Content-Type: application/json");
            
            
    
            return $response;
        }
        
        public function CCPRequest_old($client_reference_id = '',$client_type = '')
        {
            /**
             * Fetching client address to populate
             */
            $client = DB::table('clients')
                        ->join('users', 'users.id' , '=', 'clients.user_id' )
                        ->where('client_type', $client_type)
                        ->where('client_reference_id', $client_reference_id)
                        // ->toSql();
                        ->first();
                        // dd($client);
            if(isset($client)) {
                $UserName = $client->first_name;
                $Password = $client->last_name;
                $UserAgent = "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC";
                $DateOfBirth = $client->date_of_birth;
                $IdNumber = $client->idNumber;
                $Initials = substr($client->first_name, 0, 1);
                $IdType = '1';
                $CellNumber = $client->phone;
                $EmailAddress = $client->email;
                $RequestDigitalConsent = 'true';
                $OverrideDigitalConsent = 'false';
                $Surname = $client->last_name;
            }   

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://obxj52f93l.execute-api.eu-west-1.amazonaws.com/V1/CCPRequest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLINFO_HEADER_OUT => true,
                CURLOPT_POST => true,
                CURLOPT_VERBOSE => true,
                CURLOPT_POSTFIELDS => '{
                    "UserName": "Abrie89",
                    "Password": "Applord@1223!!!!!",
                    "UserAgent": "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC",
                    "DateOfBirth": "1974-10-01",
                    "IdNumber":  "7410015004033",
                    "Initials": "K",
                    "IdType": "1",
                    "RequestDetails":"ABSAL,
                                            AGL,
                                            ALTL,
                                            CHTL,
                                            DSIL,
                                            DSLL,
                                            LIBL,
                                            METL,
                                            MOML,
                                            MOMWL,
                                            NGLL,
                                            OMGPL,
                                            OMUL,
                                            OUTL,
                                            PPSL,
                                            SETL,
                                            SLML,
                                            SLMNAL,
                                            STLBL,
                                            FMIL,
                                            MOMEL",
                    "CellNumber": "0847841235",
                    "EmailAddress": "dc@sample.co.za",
                    "RequestDigitalConsent": "true",
                    "OverrideDigitalConsent": "false",
                    "Surname":"Reeves"
    }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                )
            ));
        
            $response = curl_exec($curl);



            curl_close($curl);
        	header("Content-Type: application/json");

            return $response;
        }        
    }
    


?>