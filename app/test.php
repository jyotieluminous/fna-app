<?php
 $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://expertaatestapi.bureauhouse.co.za/token/token?AccountNumber=300174&UserCode=T_3001740001&BureauName=APITEST&Password=ZPVKT9ESS&CallingModule=Integration',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
            )); 
            $response = curl_exec($curl);
            $response = json_decode($response);
            $response = $response->Results[0];
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://expertaatestapi.bureauhouse.co.za/wrapper/creditreport?Token='.$response.'&IDNumber=7707077777087&Surname=Burger&Reference=Test&InputPerson=QA&EnquiryDoneBy=Christopher%20Burger&PermissiblePurpose=Internal%20Use&EnquiryReason=QA',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            
            $response = curl_exec($curl);
            
            curl_close($curl);
            var_dump($response); die();
            $response = json_decode($response);
            var_dump($response); die();

?>