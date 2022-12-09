<?php

function lightstoneProperty()
{
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
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

}

?>
