<?php

namespace App\Http\Controllers;

use PDF;
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
use App\Http\Requests\ClientStore;
use Illuminate\Support\Facades\Hash;
use DB;
use Mail;
use App\Mail\SendUserMail;
use App\Mail\SendUserRegisterMail;
use Intervention\Image\Facades\Image as InterventionImage;
use Illuminate\Support\Facades\Storage;
use App\Cpb;
use File;

use DataTables;

class ClientController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    

    public function download_mandate($client_reference_id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");


        $main_client = DB::table('clients')->where('client_reference_id', $client_reference_id)->where('client_type', 'Main Client')->first();
        $mandate = DB::table('mandate')->where('client_id', $main_client->id)->first();
      
        if($mandate) {
            $file = storage_path().'/app/public/'.$mandate->mandate_path;
            DB::table('audit')->insert([
                'id' => null,
                'user' => $userId,
                'module' => $personalInfoModule,
                'role' => $userRole[0]->name,
                'action' => "Landed on Client Download disclosure page",
                'action_details' => json_encode(['file'=>$file]),
                'date' => DB::raw('now()')
            ]);
            return response()->download($file);
        } else {
            DB::table('audit')->insert([
                'id' => null,
                'user' => $userId,
                'module' => $personalInfoModule,
                'role' => $userRole[0]->name,
                'action' => "Landed on Client Download disclosure - Disclosure not uploaded by Client",
                'date' => DB::raw('now()')
            ]);
            session()->flash('information', 'Mandate has not been uploaded');
            return redirect()->back();
        }
        
    }
    
    public function seemore() {
        echo "hello world";
        $cpb = new Cpb();
        $cpb->creditscores();
        
    }
    
    
    public function PasswordRecoveryEmail($clientReferenceId){
        
      if(isset($clientReferenceId)){
       //get client info
        $client = DB::select("SELECT * FROM clients inner join users on clients.user_id = users.id where clients.client_reference_id  = '$clientReferenceId' and clients.`client_type` = 'Main client'");
       
        if(count($client)>0){
            
        $link_ref =  $this->generateRandomString();  //generate url string
        $userId = $client[0]->user_id;
        $email = $client[0]->email;
        $user = $client[0]->first_name.' '.$client[0]->last_name;
       
        $created_links = DB::table('created_links')->insert(
            [
                'user_id'=>$userId,
            	'link_ref' => $link_ref, 
            	'status' => 'not used', 
            	'created_on' => date("Y/m/d"), 
            	'updated_on' => date("Y/m/d")
            	
            ]);
            
        //send link
        $link_ref = 'https://fna2.phpapplord.co.za/public/clientslink/'.$link_ref;
        $mailData = [
                    'title' => 'Welcome To Flight Plan',
                    'name' => $user,
                    'link' =>$link_ref
                ];
                
                //send email
                 try {
                       $emailSent = Mail::to($email)->send(new SendUserMail($mailData));
                       // var_dump('email sent');
                       session()->flash('message', 'Link sent successfully.');
                       return redirect()->route('clientList');
                     } catch (Exception $ex) {
                        session()->flash('message',$ex);
                       //var_dump($ex);
                    }
        }else{
            $msg =  'No matches for client reference '.$clientReferenceId;
            session()->flash('message',$msg);
            return redirect()->route('clientList');
        }
        //redirect to client list
      }
    }
    
    
    public function clientslink($linkReff){
        
        $created_links = DB::table('created_links')->where('link_ref',$linkReff)->get();
        
        if(count($created_links)<=0){
            var_dump('Invalid link');
        }else{
           return view('clients.passwordRecovery',['userId'=>$created_links[0]->user_id,'link_ref'=>$linkReff]) ;
        }
       
    }
    
    public function clientpasswordupdate(Request $request ){
        $request->validate([
        'password' => 'required|confirmed|min:6',
    ]);
    session_start();
     $newPassword = Hash::make($request->password);
     //echo "hello here"; die();
     //update password
     $id = $_POST["userId"];
       //try {
             $updatePassword = DB::table('users')->where('id', $id)->update(['password' =>$newPassword]);
             //$id = Auth::id();
             $user = DB::table('users')
                        ->select('clients.client_reference_id', 'users.*', 'clients.client_type')
                        ->join('clients', 'users.id', '=', 'clients.user_id')
                        ->where('users.id', $id)->first();
             //print("<pre>"); var_dump($user); die();       
             //$this->authenticateUser(); 
             $_SESSION['login'] = 'yes';
            $_SESSION['token'] = "tststst";
            $_SESSION['userId'] = $id;
            $_SESSION['userType'] = "client";///$users[0]->type;
            $_SESSION['group_id'] = 16;
            $_SESSION['roleId'] = 16;
             $_SESSION['email'] = $user->email;
             $_SESSION['password'] = $request->password;
             $_SESSION['client_type'] = $user->client_type;
             $_SESSION['client_reference_id'] = $user->client_reference_id;
             $updateCreatedLink = DB::table('created_links')->where('user_id', $id)->update(['status' => 'used']);
             //var_dump($updateCreatedLink); die(); 
             session()->flash('message','Password has been updated successfully!');
             header('Location: https://fna2.phpapplord.co.za/public/clientEdit/'.$_SESSION['client_reference_id']);
             //return redirect()->route('clientEdit', ['client_reference_id' => $_SESSION['client_reference_id']]);
             
           //} catch (Exception $ex) {
           // session()->flash('message',$ex);
    
            //}
     //login user
     
    }
    
    public function authenticateUser() {
        if(!isset($_SESSION)) {session_start();}
        
      
        if(!isset($_SESSION['email']) || !isset($_SESSION['password']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        
        // dd($_SESSION);
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
        
        session_unset();
        Session::flush();
        
  
        if(isset($response->success))
        {
          
            session_start();
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
    
    public function mail()
    {
        $saveMainIDData = DB::table('clients')->insert(
            [
            	'advisor_id' => $advisor_id, 
            	'first_name' => $first_name, 
            	'last_name' => $last_name, 
            	'email' => $email, 
            	'date_of_birth' => $date_of_birth, 
            	'retirement_age' => $retirement_age, 
            	'gender' => $gender, 
            	'marital_status' => $marital_status, 
            	'client_type' => $client_type, 
            	'capture_user_id' => $capture_user_id, 
            	'client_reference_id' => $client_reference_id, 
            	'user_id' => $user_id, 
            ]);
        
    }
    public function clientListAjax(Request $request)
    {
       if ($request->ajax()) {
           
        if($request->authenticatedUserType == 'App Administrator')
        {
              
            $clients  = DB::select("SELECT * , IFNULL(DATE_FORMAT(date_of_birth,'%d-%m-%Y'),date_of_birth) AS date_of_birth FROM `clients` WHERE `client_type` = 'Main Client'");
           // $clients = DB::table('clients')->where('client_type', 'Main Client')->get();
        } elseif($request->authenticatedUserType == 'Main Advisor') {
            //echo "SELECT * , IFNULL(DATE_FORMAT(date_of_birth,'%d-%m-%Y'),date_of_birth) AS date_of_birth FROM `clients` WHERE `client_type` = 'Main Client' AND advisor_id ='".$request->userId."'";   
            $clients  = DB::select("SELECT * , IFNULL(DATE_FORMAT(date_of_birth,'%d-%m-%Y'),date_of_birth) AS date_of_birth FROM `clients` WHERE `client_type` = 'Main Client' AND advisor_id ='".$request->userId."'");
              // $clients = DB::table('clients')->where('client_type', 'Main Client')->get();
        } else {
            $clients  = DB::select("SELECT * , IFNULL(DATE_FORMAT(date_of_birth,'%d-%m-%Y'),date_of_birth) AS date_of_birth FROM `clients` WHERE `client_type` = 'Main Client' AND capture_user_id ='.$request->userId.'");
            //$clients = DB::table('clients')->where('client_type', 'Main Client')->where('capture_user_id', $request->userId)->get();
        }
        // dd($clients);
         // $clients = DB::select("SELECT * FROM `clients` where client_type =  'Main Client' ");
            return Datatables::of($clients)
                    ->addIndexColumn()
                    ->addColumn('action', function($row) {
                           $btn = '<a data-rowid="'.$row->id.'" href="'.url('/overview/'.$row->client_reference_id.'/Main Client').'" ><i class="fa fa-eye"></i></a>';
                           $btn .= '<a data-rowid="'.$row->id.'" href="'.url('/clientEdit/'.$row->client_reference_id).'" ><i class="fa fa-edit"></i></a>';
                           $btn = $btn.'<a href="'.url('/clientDelete/'.$row->client_reference_id).'"><i class="fa fa-trash-can"></i></a>';
                           $btn = $btn.'<a class="btn-links" href="'.url('/passwordRecoveryEmail/'.$row->client_reference_id).'"><i class="link-btn">Send link</i></a>';
                           return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('clients.clientList');
        
       
    }
    
    public function activeAccount($id,$token)  
    {
        $id = base64_decode($id);
        $usersActivationData = DB::select("SELECT * FROM `users_activation` where user_id = '$id' and remember_token = '$token'");
        if(!empty($usersActivationData))
        {
           
            $active_data =$usersActivationData[0]->date_time;
            $current_time = now();
            $diff = strtotime($current_time) - strtotime($active_data);
            $fullDays    = floor($diff/(60*60*24));   
            $fullHours   = floor(($diff-($fullDays*60*60*24))/(60*60));   
            $fullMinutes = floor(($diff-($fullDays*60*60*24)-($fullHours*60*60))/60);      
            if($fullMinutes <= 60)
            {
                DB::table('users')->where('id', $id)->update(['active_status' =>'1']);
                DB::select("Delete FROM users_activation where user_id = '$id' ");
                 echo "account active";
            }
            else
            {
                echo "link is expired";
            }
        }
        die;
    }
    public function csvClientStore()
    { 
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        // session()->forget('csv_client_reference_id');
        // session()->forget('csv_client_reference_id');
        unset($_SESSION["csv_client_reference_id"]);
        $userId = $_SESSION['userId'];
        $allowed = array('csv');
        
        $filename = $_FILES['csvUploader']['name'];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $clientInfo = array();
        $clientReference = "";
        if (in_array($ext, $allowed)) {
            $handle = fopen($_FILES['csvUploader']['tmp_name'], "r");
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
                    // //  * Checking unique email in clients table
                    $uniqueEmailClient = DB::table('clients')
                                        ->where('email',$main_email)
                                        ->count();
                    if($uniqueEmailClient > 0) {
                        $error_msg .= $main_email . " number already exists in clients.\n";
                        $addClientError = 1;
                    }

                    if($addClientError === 0) {
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
                        $myzeros = "";
                            for($i = 0; $i < $calculate; $i++)
                            {
                            $myzeros .= "0";
                            }
                            $new_client_reference = "fna".$myzeros.$count;
                        }
                        $_SESSION['csv_client_reference_id'] = $new_client_reference;
                        
                        $client_reference_id = $_SESSION["csv_client_reference_id"];
                        $advisor_id = $userId;
                        $main_first_name = $client_info[1];
                        $main_last_name = $client_info[2];
                        $main_email = $client_info[3];
                        $main_birth_day = $client_info[4];
                        $main_retirement_age = $client_info[5];
                        $main_gender = $client_info[9];
                        $main_marital_status = $client_info[6];
                        $main_client_type = $client_info[0];
                        $capture_user_id = $userId;
                        $main_id_number = $client_info[7];;
                        $main_phone =  $client_info[8];
                        $main_password = Hash::make('123456');
                        $marriage_type = '1';
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
                            'date_of_birth' => date('d-m-Y', strtotime($main_birth_day)), 
                            'retirement_age' => $main_retirement_age,
                            'gender' => $main_gender,
                            'marital_status' => $main_marital_status,
                            'client_type' => 'Main Client',
                            'capture_user_id' => $capture_user_id,
                            'client_reference_id' => $client_reference_id,
                            'user_id'=>$lastUserMainID,
                            'marriage_type'=>$marriage_type
                
                        ]);
                        $success++;
                    }
                }
                
                if(strtoupper($client_info[0]) == "SPOUSE" && isset($_SESSION['csv_client_reference_id']))
                {
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
                        $advisor_id = $userId;
                        $main_first_name = $client_info[1];
                        $main_last_name = $client_info[2];
                        $main_email = $client_info[3];
                        $main_birth_day = $client_info[4];
                        $main_retirement_age = $client_info[5];
                        $main_gender = $client_info[9];
                        $main_marital_status = $client_info[6];
                        $main_client_type = $client_info[0];
                        $capture_user_id = $userId;
                        $main_id_number = $client_info[7];
                        $main_phone =  $client_info[8];
                        $main_password = Hash::make('123456');
                        $marriage_type = '1';
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
                            'date_of_birth' => date('d-m-Y', strtotime($main_birth_day)), 
                            'retirement_age' => $main_retirement_age,
                            'gender' => $main_gender,
                            'marital_status' => $main_marital_status,
                            'client_type' => 'Spouse',
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
            
        }
        else
        {
            echo "You are uploading wrong File format";
        }
        
    }
    public function clientCreate()  
    {
        //echo getcwd(); die();
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        // echo $new_client_reference;
        // die;
        $userId = $_SESSION['userId'];
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

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Create page",
            'date' => DB::raw('now()')
        ]);        
        
        $personal_details = DB::select("SELECT * FROM `personal_details`");
        //$personal_details = DB::select("SELECT * FROM `clients` where userId = '$userId' ");
        return view('clients.clientCreate', ['new_client_reference' => $new_client_reference, 'personal_details' => $personal_details, 'getroleId' => $getroleId,'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
    }
    
    
    public function clientDelete($id)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        
        $userId = $_SESSION['userId'];
        
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        $clientUserData = DB::select("SELECT user_id FROM `clients` where client_reference_id = '$id' ");
        $clientUserId = $clientUserData[0]->user_id;
        
        $all_clients = DB::select("SELECT * FROM `clients` where client_reference_id = '$id' ");
        $all_users = DB::select("SELECT * FROM `users` where id = '$clientUserId' ");
        $all_dependants = DB::select("SELECT * FROM dependants where client_reference_id = '$id' ");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Delete page",
            'action_details' => json_encode(['clients'=>$all_clients,'users'=>json_encode($all_users),'dependants'=>$all_dependants]),
            'date' => DB::raw('now()')
        ]);  
        foreach($all_clients as $clients) {
            $user_id = isset($clients->user_id) ? $clients->user_id : "";
            DB::select("Delete FROM users where id = '$user_id' ");
        }
        DB::select("Delete FROM clients where client_reference_id = '$id' ");
        // DB::select("Delete FROM users where id = '$clientUserId' ");
        DB::select("Delete FROM dependants where client_reference_id = '$id' ");
        return redirect()->route('clientList');
    }
    
    public function dependantDelete($id, $ref)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Dependant delete from Client Delete page",
            'date' => DB::raw('now()')
        ]);  
        DB::select("Delete FROM dependants where id = '$id' ");
        return redirect()->route('clientEdit', $ref);
    }
    
    public function clientEdit($id)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        /*
            Define All Modules Names
        */
        $userType = $_SESSION['userType'];
        //dd($userType);
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
        
        
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);

        
        $personal_details = DB::select("SELECT * FROM `personal_details`");
        //$main_client = DB::select("SELECT * FROM `clients` where client_type = 'Main Spouse' or  client_type = 'Main Client' and client_reference_id = '$id' ");
        // if($userType != "App Administrator")
        if($userType == "client" || $userType == "Client")
        {
            $check_pachage_is_present = DB::table('orders')
            ->where('client_type','Main Client')
            ->where('user_id',$id)
            ->orderBy('order_date', 'desc')->first();
            if(empty($check_pachage_is_present)) {
                return redirect()->route('getClientAuthorization');
            }
            else
            {
                $current_date_time= date("Y-m-d h:i");
                $expiry_date_time = date('Y-m-d h:i', strtotime($check_pachage_is_present->expiry_date_time));
                
                if($current_date_time >= $expiry_date_time)
                {
                    return redirect()->route('package', ['client_reference_id' => $id,'client_type'=>'Main Client','default_package'=>'1']);
                }
            }
        }
        

        $main_client = DB::select("SELECT * FROM `clients` where client_type = 'Main Client' and client_reference_id = '$id' ");
        
        $the_user_id= $main_client[0]->user_id;
        $userObj = DB::select("SELECT * FROM `users` where id ='$the_user_id' ");
        
        /*Checking and setting $userSpouseObj
        IF it exists then set the block
        else
        blank*/
        $userSpouseObj = '';
        $the_spouse_user_id = 0;
        $spouse = DB::select("SELECT * FROM `clients` where client_type = 'Spouse' and client_reference_id = '$id' ");
        $has_spouse = count($spouse);
        if($has_spouse>0) {
            $the_spouse_user_id= $spouse[0]->user_id;
            $userSpouseObj = DB::select("SELECT * FROM `users` where id ='$the_spouse_user_id' ");
            $spouseCount = 1;
        } else {
            $spouseCount = 0;
        }
        $dependants = DB::select("SELECT * FROM `dependants` where client_reference_id = '$id' ");
        $clientLicenseImages = DB::select("SELECT license_name FROM `client_license_images` where client_reference_id = '$id' ");
        
        
        $signature = null;

      

        $signatureObj = DB::table('signatures')->where('client_id', $main_client[0]->id)->first();
        $mandate = DB::table('mandate')->where('client_id', $main_client[0]->id)->first();


        if($signatureObj)
        {
            $signature = explode('/', $signatureObj->signature_path)[2];
        }
        
        $main_client_authorisated = $spouse_client_authorisated = 0 ;

        $users_list = DB::select("SELECT id, client_type,
                                    CASE
                                        WHEN (
                                            bank_statement = 1 AND 
                                            policy_from_astute = 1 AND 
                                            investment_debts=1 AND 
                                            assets_liabilites_data = 1 AND 
                                            cipc_data = 1) THEN 1
                                        ELSE 0
                                    END as authorised
                                    FROM `client_authorizations` 
                                    WHERE client_reference_id = '".$id."' order by client_type ASC");
                                    
        if(isset($users_list)) {
            foreach($users_list as $user) {
                if($user->client_type == 'Main Client') {
                    $main_client_authorisated = $user->authorised;
                }
                if($user->client_type == 'Spouse') {
                    $spouse_client_authorisated = $user->authorised;
                }                
            }
        }
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Edit page",
            'action_details' => json_encode($main_client).';Spouse'.json_encode($spouse),
            'date' => DB::raw('now()')
            
        // $is_administrator = 
        ]);
        return view('clients.clientEdit', [
            'main_client_authorisated' => $main_client_authorisated,
            'spouse_client_authorisated' => $spouse_client_authorisated,
            'spouseCount' => $spouseCount, 
            'userObj'=>$userObj, 
            'signature' => $signature, 
            'mandate' => $mandate, 
            'userSpouseObj'=>$userSpouseObj,
            'client_reference_id' => $id,
            'dependant' => $dependants,
            'spouse' => $spouse, 
            'main_client' => $main_client, 
            'personal_details' => $personal_details,
            'getroleId' => $getroleId,
            'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, 
            '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 
            'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,
            'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 
            'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 
            'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 
            'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 
            'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 
            'getAccessName'=>$getAccessName,
            'is_administrator'=>'',
            'clientLicenseImages'=>$clientLicenseImages]);
    }
    
    
    
    
   /* public function clientSave(ClientStore $request)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        // print_r($_POST);
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);

        $client_reference_id = $_POST["client_reference_id"];
        $advisor_id = $userId;
        //print_r("<preo"); var_dump($_POST); die(); 
        $main_vehicle_image = $_POST["main_vehicle_image"];
        
        $main_first_name = $_POST["main_first_name"];
        $main_last_name = $_POST["main_last_name"];
        $main_email = $_POST["email"];
        $main_birth_day = $_POST["main_dob"];
        $main_retirement_age = $_POST["main_retirement_age"];
        $main_gender = $_POST["main_gender"];
        $main_marital_status = $_POST["main_marital_status"];
        $main_marriage_type = $_POST["main_marriage_type"];
        $main_client_type = "Main Client";
        $capture_user_id = $userId;
        $main_id_number = $_POST["main_idNo"];
        $main_phone = $_POST["main_phone"];
        $main_password = password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT); //md5($main_first_name.'-'.$main_last_name);
        $main_address = $_POST["main_address"];       
        $main_city = $_POST["main_city"];       
        $main_state = $_POST["main_state"];         
        $main_postcode = (isset($_POST["main_postcode"]) ? $_POST["main_postcode"] : "");       
        $main_country = $_POST["main_country"];       
        $main_vehicle_number = $_POST["main_vehicle_number"];       
        
        
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
            'marriage_type' => $main_marriage_type,
            'client_type' => $main_client_type,
            'capture_user_id' => $capture_user_id,
            'client_reference_id' => $client_reference_id,
            'address' => $main_address,
            'city' => $main_city,
            'state' => $main_state,
            'zip' => $main_postcode,
            'country' => $main_country,
            'vehicle_vin_number' => $main_vehicle_number
        ]);
            DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);
        //$lastUserMainID = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$main_email','$main_password','$main_first_name','$main_last_name','','Client','','$main_gender','$main_birth_day')");
        
        
        if(!empty($saveMainIDData))
        {
            
            DB::table('clients')->where('id', $saveMainIDData)->update(['user_id' => $lastUserMainID]);
            $active_string = $this::generateRandomString();
            $saveUsersActivationData = DB::table('users_activation')->insertGetId(
            [
                'user_id' => $saveMainIDData, 
                'remember_token'=>$active_string,
                'date_time' => DB::raw('now()')
    
            ]); 
        
                    if(!empty($saveUsersActivationData))
                    {
                        $activation_link = '<a href="'.url('/resetuserpassword/'.$client_reference_id.'/Main Client/'.$saveMainIDData.'/'.$active_string).'">Click here</a>';                 
                        $mailData = [
                            'title' => 'Flight Plan',
                            'name' => $main_first_name.' '.$main_last_name,
                            'link' =>$activation_link,
                            'username' =>$main_email,
                            'password' => password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT),
                        ];
                        $welcomeEmailSent = Mail::to($main_email)->send(new SendUserRegisterMail($mailData));
                    }
                    
        }
        
        if(isset($_POST['spouse_show']) && $_POST['spouse_show'] === "yes") {
            $advisor_id = $userId;
            $spouse_first_name = $_POST["spouse_first_name"];
            $spouse_last_name = $_POST["spouse_last_name"];
            $spouse_email = $_POST["spouse_email"];
            $spouse_birth_day = $_POST["spouse_dob"];
            $spouse_retirement_age = $_POST["spouse_retirement_age"];
            $spouse_gender = $_POST["spouse_gender"];
            $spouse_marital_status = $_POST["spouse_marital_status"];
            $spouse_marriage_type = $_POST["spouse_marriage_type"];
            $spouse_client_type = "Spouse";
            $capture_user_id = $userId;
            $spouse_password = md5($spouse_first_name.'-'.$spouse_last_name);
            $spouse_id_number = $_POST["spouse_idNo"];
            $spouse_phone = $_POST["spouse_phone"];

            //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
            //$saveSpouseData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$spouse_first_name', '$spouse_last_name','$spouse_email' ,'$spouse_birth_day', '$spouse_retirement_age', '$spouse_gender', '$spouse_marital_status', '$spouse_client_type', '$capture_user_id', '$client_reference_id')");
    
            $saveSpouseIDData = DB::table('clients')->insertGetId(
            [
                'advisor_id' => $advisor_id, 
                'first_name'=>$spouse_first_name,
                'last_name' => $spouse_last_name, 
                'email' => $spouse_email, 
                'date_of_birth' => $spouse_birth_day, 
                'retirement_age' => $spouse_retirement_age,
                'gender' => $spouse_gender,
                'marital_status' => $spouse_marital_status,
                'marriage_type' => $spouse_marriage_type,
                'client_type' => $spouse_client_type,
                'capture_user_id' => $capture_user_id,
                'client_reference_id' => $client_reference_id    
            ]);
            
            DB::table('permissions')->insert([
                            'groupId' => 16,
                            'userId' => $saveSpouseIDData
                        ]);
            
            //$saveSpouseUserData = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$spouse_email','$spouse_password','$spouse_first_name','$spouse_last_name','','Client','','$spouse_gender','$spouse_birth_day')");
            $lastUserSpouseID = DB::table('users')->insertGetId(
            [
                'email' => $spouse_email, 
                'password'=>$spouse_password,
                'name' => $spouse_first_name, 
                'surname' => $spouse_last_name, 
                'idNumber' => $spouse_id_number, 
                'type' => 'Client',
                'phone' => $spouse_phone,
                'gender' => $spouse_gender,
                'dob' => $spouse_birth_day
            ]);
            
            if(!empty($lastUserSpouseID))
            {
                DB::table('clients')->where('id', $saveSpouseIDData)->update(['user_id' => $lastUserSpouseID]);
            }
        }
        if(isset($_POST['dependant_show']) && $_POST['dependant_show'] === "yes") {
            $count = count($_POST['dependant_type']); 
            for($j = 0; $j < $count; $j++)
            { 
                DB::select("INSERT into dependants values (
                    null,
                    '".$userId."', 
                    '".$userId."', 
                    '".$client_reference_id."',
                    '".$_POST['dependant_type'][$j]."',
                    '".$_POST['dependant_first_name'][$j]."', 
                    '".$_POST['dependant_last_name'][$j]."', 
                    '".$_POST['dependant_year'][$j]."-".$_POST['dependant_month'][$j]."-".$_POST['dependant_day'][$j]."', 
                    '".$_POST['dependant_gender'][$j]."', 
                    '".$_POST['dependant_age'][$j]."')
                    ");
            }
        }
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Save page",
            'action_details' => json_encode($_POST),
            'date' => DB::raw('now()')
        ]); 
        return redirect()->route('clientList');
    }*/
    public function clientSave(ClientStore $request)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        // print_r($_POST);
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);

        $client_reference_id = $_POST["client_reference_id"];
        $advisor_id = $userId;
        //print_r("<preo"); var_dump($_POST); die(); 
        $main_vehicle_image = '';//$_POST["main_vehicle_image"];
        
        $main_nickname = $_POST["main_nickname"];
        $main_initials = $_POST["main_initials"];
        $main_first_name = $_POST["main_first_name"];
        $main_middle_name = $_POST["main_middle_name"];
        $main_last_name = $_POST["main_last_name"];
        $main_email = $_POST["email"];
        $main_birth_day = $_POST["main_dob"];
        $main_retirement_age = $_POST["main_retirement_age"];
        $main_gender = $_POST["main_gender"];
        $main_marital_status = $_POST["main_marital_status"];
        $main_marriage_type = $_POST["main_marriage_type"];
        $main_client_type = "Main Client";
        $capture_user_id = $userId;
        $main_id_number = $_POST["main_idNo"];
        $main_phone = $_POST["main_phone"];
        $main_password = password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT); //md5($main_first_name.'-'.$main_last_name);
        $main_address = $_POST["main_address"];       
        $main_city = $_POST["main_city"];       
        $main_state = $_POST["main_state"];         
        $main_postcode = (isset($_POST["main_postcode"]) ? $_POST["main_postcode"] : "");       
        $main_country = $_POST["main_country"];       
        $main_vehicle_number = '';//$_POST["main_vehicle_number"];
        $main_vehicle_number_json = '';//$_POST["main_vehicle_number_json"];
        
        
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
        
        $saveMainIDData = DB::table('clients')->insertGetId(
        [
            'advisor_id' => $advisor_id, 
            'nickname'=>$main_nickname,
            'initials'=>$main_initials,
            'first_name'=>$main_first_name,
            'middle_name'=>$main_middle_name,
            'last_name' => $main_last_name, 
            'email' => $main_email, 
            'date_of_birth' => $main_birth_day, 
            'retirement_age' => $main_retirement_age,
            'gender' => $main_gender,
            'marital_status' => $main_marital_status,
            'marriage_type' => $main_marriage_type,
            'client_type' => $main_client_type,
            'capture_user_id' => $capture_user_id,
            'client_reference_id' => $client_reference_id,
            'address' => $main_address,
            'city' => $main_city,
            'state' => $main_state,
            'zip' => $main_postcode,
            'country' => $main_country,
            'vehicle_vin_number' => $main_vehicle_number,
            'vehicle_vin_number_json'=>$main_vehicle_number_json
        ]);


        /*if(count($main_vehicle_image) > 0 && isset($main_vehicle_image[0]) && !empty($main_vehicle_image[0]))
        {
            foreach ($main_vehicle_image as $key => $img) {
                
                $folderPath = "vehicle-scan/";        
                $image_parts = explode(";base64,", $img);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];                
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'pic_'.uniqid().'.png';                
                $file = $folderPath . $fileName;
                Storage::disk('public')->put($file, $image_base64);
                DB::table('client_license_images')->insert([
                        'license_name' => $fileName,
                        'client_reference_id' => $client_reference_id,
                        'client_type'=> $main_client_type,
                        'capture_date'=>DB::raw('now()')
                    ]);
            }
        }*/
         
            DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);
        //$lastUserMainID = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$main_email','$main_password','$main_first_name','$main_last_name','','Client','','$main_gender','$main_birth_day')");
        
        
        if(!empty($saveMainIDData))
        {
            
            DB::table('clients')->where('id', $saveMainIDData)->update(['user_id' => $lastUserMainID]);
            $active_string = $this::generateRandomString();
            $saveUsersActivationData = DB::table('users_activation')->insertGetId(
            [
                'user_id' => $lastUserMainID, 
                'remember_token'=>$active_string,
                'date_time' => DB::raw('now()')
    
            ]); 
        
                    if(!empty($saveUsersActivationData))
                    {
                        $activation_link = '<a href="'.url('/resetuserpassword/'.$client_reference_id.'/Main_Client/'.$lastUserMainID.'/'.$active_string).'">Click here</a>';                 
                        $mailData = [
                            'title' => 'Flight Plan',
                            'name' => $main_first_name.' '.$main_last_name,
                            'link' =>$activation_link,
                            'username' =>$main_email,
                            'password' => password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT),
                        ];
                        
                        $welcomeEmailSent = Mail::to($main_email)->send(new SendUserRegisterMail($mailData));
                        //dd($welcomeEmailSent,$mailData);
                    }
                    
        }
        
        if(isset($_POST['spouse_show']) && $_POST['spouse_show'] === "yes") {
            $advisor_id = $userId;
            $spouse_nickname = $_POST["spouse_nickname"];
            $spouse_initials = $_POST["spouse_initials"];
            $spouse_first_name = $_POST["spouse_first_name"];
            $spouse_middle_name = $_POST["spouse_middle_name"];
            $spouse_last_name = $_POST["spouse_last_name"];
            $spouse_email = $_POST["spouse_email"];
            $spouse_birth_day = $_POST["spouse_dob"];
            $spouse_retirement_age = $_POST["spouse_retirement_age"];
            $spouse_gender = $_POST["spouse_gender"];
            $spouse_marital_status = $_POST["spouse_marital_status"];
            $spouse_marriage_type = $_POST["spouse_marriage_type"];
            $spouse_client_type = "Spouse";
            $capture_user_id = $userId;
            $spouse_password = md5($spouse_first_name.'-'.$spouse_last_name);
            $spouse_id_number = $_POST["spouse_idNo"];
            $spouse_phone = $_POST["spouse_phone"];
            $spouse_vehicle_number = '';//$_POST["spouse_vehicle_number"];

            //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
            //$saveSpouseData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$spouse_first_name', '$spouse_last_name','$spouse_email' ,'$spouse_birth_day', '$spouse_retirement_age', '$spouse_gender', '$spouse_marital_status', '$spouse_client_type', '$capture_user_id', '$client_reference_id')");
    
            $saveSpouseIDData = DB::table('clients')->insertGetId(
            [
                'advisor_id' => $advisor_id, 
                'nickname'=>$spouse_nickname,
                'initials'=>$spouse_initials,
                'first_name'=>$spouse_first_name,
                'middle_name'=>$spouse_middle_name,
                'last_name' => $spouse_last_name, 
                'email' => $spouse_email, 
                'date_of_birth' => $spouse_birth_day, 
                'retirement_age' => $spouse_retirement_age,
                'gender' => $spouse_gender,
                'marital_status' => $spouse_marital_status,
                'marriage_type' => $spouse_marriage_type,
                'client_type' => $spouse_client_type,
                'capture_user_id' => $capture_user_id,
                'client_reference_id' => $client_reference_id,
                'vehicle_vin_number' => $spouse_vehicle_number   
            ]);
            
            DB::table('permissions')->insert([
                            'groupId' => 16,
                            'userId' => $saveSpouseIDData
                        ]);
            
            //$saveSpouseUserData = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$spouse_email','$spouse_password','$spouse_first_name','$spouse_last_name','','Client','','$spouse_gender','$spouse_birth_day')");
            $lastUserSpouseID = DB::table('users')->insertGetId(
            [
                'email' => $spouse_email, 
                'password'=>$spouse_password,
                'name' => $spouse_first_name, 
                'surname' => $spouse_last_name, 
                'idNumber' => $spouse_id_number, 
                'type' => 'Client',
                'phone' => $spouse_phone,
                'gender' => $spouse_gender,
                'dob' => $spouse_birth_day
            ]);
            
            if(!empty($lastUserSpouseID))
            {
                DB::table('clients')->where('id', $saveSpouseIDData)->update(['user_id' => $lastUserSpouseID]);
            }
        }
        if(isset($_POST['dependant_show']) && $_POST['dependant_show'] === "yes") {
            $count = count($_POST['dependant_type']); 
            for($j = 0; $j < $count; $j++)
            { 
                DB::select("INSERT into dependants values (
                    null,
                    '".$userId."', 
                    '".$userId."', 
                    '".$client_reference_id."',
                    '".$_POST['dependant_type'][$j]."',
                    '".$_POST['dependant_first_name'][$j]."', 
                    '".$_POST['dependant_middle_name'][$j]."', 
                    '".$_POST['dependant_last_name'][$j]."', 
                    '".$_POST['dependant_year'][$j]."-".$_POST['dependant_month'][$j]."-".$_POST['dependant_day'][$j]."', 
                    '".$_POST['dependant_gender'][$j]."', 
                    '".$_POST['dependant_age'][$j]."')
                    ");
            }
        }
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Save page",
            'action_details' => json_encode($_POST),
            'date' => DB::raw('now()')
        ]); 
        return redirect()->route('clientList');
    }
    public function clientSave_27sep2022(ClientStore $request)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        // print_r($_POST);
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);

        $client_reference_id = $_POST["client_reference_id"];
        $advisor_id = $userId;
        //print_r("<pre>"); var_dump($_POST); die(); 
        $main_first_name = $_POST["main_first_name"];
        $main_last_name = $_POST["main_last_name"];
        $main_email = $_POST["email"];
        $main_birth_day = $_POST["main_dob"];
        $main_retirement_age = $_POST["main_retirement_age"];
        $main_gender = $_POST["main_gender"];
        $main_marital_status = $_POST["main_marital_status"];
        $main_marriage_type = $_POST["main_marriage_type"];
        $main_client_type = "Main Client";
        $capture_user_id = $userId;
        $main_id_number = $_POST["main_idNo"];
        $main_phone = $_POST["main_phone"];
        $main_password = password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT); //md5($main_first_name.'-'.$main_last_name);
        $main_address = $_POST["main_address"];       
        $main_city = $_POST["main_city"];       
        $main_state = $_POST["main_state"];       
        $main_postcode = $_POST["main_postcode"];       
        $main_country = $_POST["main_country"];       
        $main_vehicle_number = $_POST["main_vehicle_number"];       
        
        
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
            'marriage_type' => $main_marriage_type,
            'client_type' => $main_client_type,
            'capture_user_id' => $capture_user_id,
            'client_reference_id' => $client_reference_id,
            'address' => $main_address,
            'city' => $main_city,
            'state' => $main_state,
            'zip' => $main_postcode,
            'country' => $main_country,
            'vehicle_vin_number' => $main_vehicle_number

        ]);
        
            DB::table('permissions')->insert([
                        'groupId' => 16,
                        'userId' => $lastUserMainID
                    ]);
        //$lastUserMainID = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$main_email','$main_password','$main_first_name','$main_last_name','','Client','','$main_gender','$main_birth_day')");
        
        
        // if(!empty($saveMainIDData))
        // {
            
        //     DB::table('clients')->where('id', $saveMainIDData)->update(['user_id' => $lastUserMainID]);
        //     $active_string = $this::generateRandomString();
        //     $saveUsersActivationData = DB::table('users_activation')->insertGetId(
        //     [
        //         'user_id' => $saveMainIDData, 
        //         'remember_token'=>$active_string,
        //         'date_time' => DB::raw('now()')
    
        //     ]); 
        
        //             if(!empty($saveUsersActivationData))
        //             {
        //                 $activation_link = '<a href="'.url('/resetuserpassword/'.$client_reference_id.'/Main Client/'.$saveMainIDData.'/'.$active_string).'">Click here</a>';                 
        //                 $mailData = [
        //                     'title' => 'Flight Plan',
        //                     'name' => $main_first_name.' '.$main_last_name,
        //                     'link' =>$activation_link,
        //                     'username' =>$main_email,
        //                     'password' => password_hash($main_first_name.'-'.$main_last_name, PASSWORD_BCRYPT),
        //                 ];
        //                 $welcomeEmailSent = Mail::to($main_email)->send(new SendUserRegisterMail($mailData));
        //             }
                    
        // }
        
        if(isset($_POST['spouse_show']) && $_POST['spouse_show'] === "yes") {
            $advisor_id = $userId;
            $spouse_first_name = $_POST["spouse_first_name"];
            $spouse_last_name = $_POST["spouse_last_name"];
            $spouse_email = $_POST["spouse_email"];
            $spouse_birth_day = $_POST["spouse_dob"];
            $spouse_retirement_age = $_POST["spouse_retirement_age"];
            $spouse_gender = $_POST["spouse_gender"];
            $spouse_marital_status = $_POST["spouse_marital_status"];
            $spouse_marriage_type = $_POST["spouse_marriage_type"];
            $spouse_client_type = "Spouse";
            $capture_user_id = $userId;
            $spouse_password = md5($spouse_first_name.'-'.$spouse_last_name);
            $spouse_id_number = $_POST["spouse_idNo"];
            $spouse_phone = $_POST["spouse_phone"];
            $spouse_vehicle_number = $_POST["spouse_vehicle_number"];
            //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
            //$saveSpouseData = DB::select("INSERT into clients (advisor_id, first_name, last_name,email, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$spouse_first_name', '$spouse_last_name','$spouse_email' ,'$spouse_birth_day', '$spouse_retirement_age', '$spouse_gender', '$spouse_marital_status', '$spouse_client_type', '$capture_user_id', '$client_reference_id')");
    
            $saveSpouseIDData = DB::table('clients')->insertGetId(
            [
                'advisor_id' => $advisor_id, 
                'first_name'=>$spouse_first_name,
                'last_name' => $spouse_last_name, 
                'email' => $spouse_email, 
                'date_of_birth' => $spouse_birth_day, 
                'retirement_age' => $spouse_retirement_age,
                'gender' => $spouse_gender,
                'marital_status' => $spouse_marital_status,
                'marriage_type' => $spouse_marriage_type,
                'client_type' => $spouse_client_type,
                'capture_user_id' => $capture_user_id,
                'client_reference_id' => $client_reference_id,
                'vehicle_vin_number' => $spouse_vehicle_number   
            ]);
            
            DB::table('permissions')->insert([
                            'groupId' => 16,
                            'userId' => $saveSpouseIDData
                        ]);
            
            //$saveSpouseUserData = DB::select("INSERT into users (email, password, name,surname, idNumber, type, phone, gender, dob) values ('$spouse_email','$spouse_password','$spouse_first_name','$spouse_last_name','','Client','','$spouse_gender','$spouse_birth_day')");
            $lastUserSpouseID = DB::table('users')->insertGetId(
            [
                'email' => $spouse_email, 
                'password'=>$spouse_password,
                'name' => $spouse_first_name, 
                'surname' => $spouse_last_name, 
                'idNumber' => $spouse_id_number, 
                'type' => 'Client',
                'phone' => $spouse_phone,
                'gender' => $spouse_gender,
                'dob' => $spouse_birth_day
            ]);
            
            if(!empty($lastUserSpouseID))
            {
                DB::table('clients')->where('id', $saveSpouseIDData)->update(['user_id' => $lastUserSpouseID]);
            }
        }
        if(isset($_POST['dependant_show']) && $_POST['dependant_show'] === "yes") {
            $count = count($_POST['dependant_type']); 
            for($j = 0; $j < $count; $j++)
            { 
                DB::select("INSERT into dependants values (
                    null,
                    '".$userId."', 
                    '".$userId."', 
                    '".$client_reference_id."',
                    '".$_POST['dependant_type'][$j]."',
                    '".$_POST['dependant_first_name'][$j]."', 
                    '".$_POST['dependant_last_name'][$j]."', 
                    '".$_POST['dependant_year'][$j]."-".$_POST['dependant_month'][$j]."-".$_POST['dependant_day'][$j]."', 
                    '".$_POST['dependant_gender'][$j]."', 
                    '".$_POST['dependant_age'][$j]."')
                    ");
            }
        }
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Save page",
            'action_details' => json_encode($_POST),
            'date' => DB::raw('now()')
        ]); 
        return redirect()->route('clientList');
    }
    
    function generateRandomString($length = 10) 
    {
        
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
        
    }
    
    public function clientUpdateSignature(Request $request)  
    {   
        session_start();

        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        //print_r($request);
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");

        if($request->signature1){
            if(explode(':', $request->signature1)[0] == 'data')
            {
                $signature = time().'.'. explode('/', explode(':', substr($request->signature1, 0, strpos($request->signature1, ';')))[1])[1];
                //InterventionImage::make($request->signature1)->save(public_path('images/client_signatures/'). $signature);
                InterventionImage::make($request->signature1)->resize(200, null)->save(public_path('images/client_signatures/'). $signature);
                $path = 'images/client_signatures/'.$signature;
                // echo DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->toSql();
                $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();
                // dd($main_client);
                if(isset($main_client)) {
                    $signature_count = DB::table('signatures')->where('client_id', $main_client->id)->count();
                        if($signature_count == 0)
                        {
                            DB::table('signatures')->insert([
                                'client_id' => $main_client->id,
                                'client_reference_id' => $_POST["client_reference_id"],
                                'signature_path' => $path
                            ]);
                            DB::table('audit')->insert([
                                'id' => null,
                                'user' => $userId,
                                'module' => $personalInfoModule,
                                'role' => $userRole[0]->name,
                                'action' => "Landed on Client Create Signature page",
                                'action_details' => json_encode($path),
                                'date' => DB::raw('now()')
                            ]);
                        } else {
                            $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();
                            $signature_path1 = DB::table('signatures')->where('client_id', $main_client->id)->first();
                            // print_r($signature_path1);
                            $image_path = public_path(''.$signature_path1->signature_path);  // Value is not URL but directory file path
                            if(File::exists($image_path)) {
                                File::delete($image_path);
                            }
                            DB::table('signatures')->where('client_id', $main_client->id)->update([
                                'signature_path' => $path
                            ]);
        
                            DB::table('audit')->insert([
                                'id' => null,
                                'user' => $userId,
                                'module' => $personalInfoModule,
                                'role' => $userRole[0]->name,
                                'action' => "Landed on Client Update Signature page",
                                'action_details' => json_encode(['old_file'=> $image_path, 'new_file'=>$path]),
                                'date' => DB::raw('now()')
                            ]);                    
                        }
                }

            }
        }
?>
<script type="text/javascript">
parent.location.reload();
</script>
<?php
    }
    
    public function storeClientDisclosure(Request $request)
    {
        if($request->hasFile('mandate')){
            echo "storeClientDisclosure";
            print_r($request->hasFile('mandate'));
            dd($request->all());    
            $mandate_file = $request->file('mandate');
           
            $ext = $mandate_file->guessExtension();
            $mandate_path = Storage::disk('public')->putFileAs('mandates', $mandate_file, 'mandate-'. rand(123456, 999999) .'.'. $ext);

            $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();

            $mandate_count = DB::table('mandate')->where('client_id', $main_client->id)->count();

            
            if($mandate_count == 0) {
                DB::table('mandate')->insert([
                    'client_id' => $main_client->id,
                    'client_reference_id' => $_POST["client_reference_id"],
                    'mandate_path' => $mandate_path
                ]);
            } else {
                DB::table('mandate')->where('client_id', $main_client->id)->update([
                    'mandate_path' => $mandate_path
                ]);
        
            }

        } else {
            echo "Please select a file to upload";
        }
    }
    
    public function clientUpdate(Request $request)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        
        $advisor_id = $userId;

        if($request->signature){

            if(explode(':', $request->signature)[0] == 'data')
            {
                $signature = time().'.'. explode('/', explode(':', substr($request->signature, 0, strpos($request->signature, ';')))[1])[1];
                InterventionImage::make($request->signature)->save(public_path('images/client_signatures/'). $signature);
    
                $path = 'images/client_signatures/'.$signature;


                $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();

                $signature_count = DB::table('signatures')->where('client_id', $main_client->id)->count();

                
                if($signature_count == 0)
                {
                    DB::table('signatures')->insert([
                        'client_id' => $main_client->id,
                        'client_reference_id' => $_POST["client_reference_id"],
                        'signature_path' => $path
                    ]);

                }else {

                    DB::table('signatures')->where('client_id', $main_client->id)->update([
                        'signature_path' => $path
                    ]);
            
                }
                

            }

        }
                // dd($request->all());
        if($request->hasFile('mandate')){
            $request->validate([
            'mandate'  => 'required|mimes:pdf|max:10000'
            ]);
    
            $mandate_file = $request->file('mandate');
           
            $ext = $mandate_file->guessExtension();
            $mandate_path = Storage::disk('public')->putFileAs('mandates', $mandate_file, 'mandate-'. rand(123456, 999999) .'.'. $ext);
            
            $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();
            $mandate_count = DB::table('mandate')->where('client_id', $main_client->id)->count();
            
            if($mandate_count == 0)
            {
                DB::table('mandate')->insert([
                    'client_id' => $main_client->id,
                    'client_reference_id' => $_POST["client_reference_id"],
                    'mandate_path' => $mandate_path
                ]);
                DB::table('audit')->insert([
                    'id' => null,
                    'user' => $userId,
                    'module' => $personalInfoModule,
                    'role' => $userRole[0]->name,
                    'action' => "Landed on Client Create Upload disclosure page",
                    'action_details' => json_encode(['file'=>$mandate_path]),
                    'date' => DB::raw('now()')
                ]); 
            }else {
                $main_client = DB::table('clients')->where('client_reference_id', $request->client_reference_id)->where('client_type', 'Main Client')->first();
                $signature_path1 = DB::table('mandate')->where('client_id', $main_client->id)->first();
                $image_path = storage_path().'/app/public/'.$signature_path1->mandate_path;  // Value is not URL but directory file path
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                DB::table('mandate')->where('client_id', $main_client->id)->update([
                    'mandate_path' => $mandate_path
                ]);
                DB::table('audit')->insert([
                    'id' => null,
                    'user' => $userId,
                    'module' => $personalInfoModule,
                    'role' => $userRole[0]->name,
                    'action' => "Landed on Client update Upload disclosure page",
                    'action_details' => json_encode(['old_file'=>$image_path,'new_file'=>$mandate_path]),
                    'date' => DB::raw('now()')
                ]); 
            }
        }
        $request->validate([
            'main_idNo' => 'required|unique:users,idNumber,'.$_POST["main_user_id"],
            ]
            ,
            [
            'main_idNo.required'=> 'Id Number is required.',
            'main_idNo.unique'=> 'This Id Number has already been taken.'
            ]);


        // print_r("<pre>"); var_dump($_POST); die(); 
        $main_nickname = $_POST["main_nickname"];
        $main_initials = $_POST["main_initials"];
        $main_first_name = $_POST["main_first_name"];
        $main_middle_name = $_POST["main_middle_name"]; 
        $main_last_name = $_POST["main_last_name"]; 
        $main_birth_day = $_POST["main_dob"];
        $main_retirement_age = $_POST["main_retirement_age"];
        $main_gender = $_POST["main_gender"];
        $main_marital_status = $_POST["main_marital_status"];
        $main_marriage_type = $_POST["main_marriage_type"];
        $main_client_type = "Main Client";
        $capture_user_id = $userId; 
        $client_reference_id = $_POST["client_reference_id"];
        $main_id_number = $_POST["main_idNo"];
        $main_phone = $_POST["main_phone"];
        $main_email = $_POST["main_email"];
        $main_user_id = $_POST['main_user_id'];
        $main_address = $_POST["main_address"];       
        $main_city = $_POST["main_city"];       
        $main_state = $_POST["main_state"];       
        $main_postcode = (isset($_POST["main_postcode"]) ? $_POST["main_postcode"] : "");       
        $main_country = $_POST["main_country"]; 
        $main_vehicle_number = '';//$_POST["main_vehicle_number"]; 
        $main_vehicle_number_json = '';//$_POST["main_vehicle_number_json"];
        
        //echo "update clients set first_name = '$main_first_name' , last_name = '$main_last_name' , date_of_birth = '$main_birth_day' , retirement_age = '$main_retirement_age' , marital_status = '$main_marital_status' where client_reference_id = '$client_reference_id' and client_type = 'Main Client'"; die();
        $saveData = DB::select("update clients set 
                        nickname = '$main_nickname' ,
                        initials = '$main_initials' ,
                        first_name = '$main_first_name' ,
                        middle_name = '$main_middle_name' , 
                        last_name = '$main_last_name' , 
                        date_of_birth = '$main_birth_day' , 
                        retirement_age = '$main_retirement_age' , 
                        gender = '$main_gender' , 
                        email = '$main_email',
                        marital_status = '$main_marital_status',
                        marriage_type = '$main_marriage_type',
                        address = '$main_address',
                        city = '$main_city',
                        state = '$main_state',
                        zip = '$main_postcode',
                        country = '$main_country',
                        vehicle_vin_number = '$main_vehicle_number',
                        vehicle_vin_number_json = '$main_vehicle_number_json'
                        where 
                        user_id = '$main_user_id'");                     
        
        $saveUserData = DB::select("update users set 
                        name = '$main_first_name' , 
                        surname = '$main_last_name' , 
                        idNumber = '$main_id_number' , 
                        phone = '$main_phone' , 
                        gender = '$main_gender' , 
                        dob = '$main_birth_day'
                        where 
                        id = '$main_user_id'");
        $main_vehicle_image = '';//$_POST["main_vehicle_image"];
       /* if(count($main_vehicle_image) > 0 && isset($main_vehicle_image[0]) && !empty($main_vehicle_image[0]))
                {
                    foreach ($main_vehicle_image as $key => $img) {
                        
                        $folderPath = "vehicle-scan/";        
                        $image_parts = explode(";base64,", $img);
                        $image_type_aux = explode("image/", $image_parts[0]);
                        $image_type = $image_type_aux[1];                
                        $image_base64 = base64_decode($image_parts[1]);
                        $fileName = 'pic_'.uniqid().'.png';                
                        $file = $folderPath . $fileName;
                        Storage::disk('public')->put($file, $image_base64);
                        DB::table('client_license_images')->insert([
                                'license_name' => $fileName,
                                'client_reference_id' => $client_reference_id,
                                'client_type'=> $main_client_type,
                                'capture_date'=>DB::raw('now()')
                            ]);
                    }
                }*/
                
        $advisor_id = $userId;
        $spouse_nickname = $_POST["spouse_nickname"];
        $spouse_initials = $_POST["spouse_initials"];
        $spouse_first_name = $_POST["spouse_first_name"];
        $spouse_middle_name = $_POST["spouse_middle_name"];
        $spouse_last_name = $_POST["spouse_last_name"];
        $spouse_birth_day = $_POST["spouse_dob"];
        $spouse_retirement_age = $_POST["spouse_retirement_age"];
        $spouse_gender = $_POST["spouse_gender"];
        $spouse_email = $_POST["spouse_email"];
        $spouse_marital_status = $_POST["spouse_marital_status"];
        $spouse_vehicle_number = '';//$_POST["spouse_vehicle_number"];
        $spouse_client_type = "Spouse";
        $capture_user_id = $userId;
        $client_reference_id = $_POST["client_reference_id"];
        $spouse_id_number = $_POST["spouse_idNo"];
        $spouse_phone = $_POST["spouse_phone"];
        $spouse_user_id = $_POST["spouse_user_id"];
 
        // dd($spouse_user_id);
        //echo "update clients set first_name = '$spouse_first_name' , last_name = '$spouse_last_name' , date_of_birth = '$spouse_birth_day' , retirement_age = '$spouse_retirement_age' , marital_status = '$spouse_marital_status' where client_reference_id = '$client_reference_id' and client_type = 'Spouse'"; die();
        // DB::select("update clients set 
        // first_name = '$spouse_first_name' , 
        // last_name = '$spouse_last_name' , 
        // email = '$spouse_email',
        // date_of_birth = '$spouse_birth_day' , 
        // retirement_age = '$spouse_retirement_age' , 
        // marital_status = '$spouse_marital_status'
        // where user_id = '$spouse_user_id'");

        if(isset($_POST['spouse_show']) && $_POST['spouse_show'] === "yes") {
            // $request->validate([
            //     'spouse_idNo' => 'required|unique:users,idNumber,'.$_POST["spouse_user_id"],
            //     ]
            //     ,
            //     [
            //     'spouse_idNo.required'=> 'Id Number is required.',
            //     'spouse_idNo.unique'=> 'This Id Number has already been taken.'
            //     ]);
            $spouse_user_id_from_clients = 0;
            $clients_spouse = DB::table('clients')
                                ->selectRaw('id')
                                ->where('client_reference_id', $_POST["client_reference_id"])
                                ->where('client_type','Spouse')
                                ->pluck('id');
            if(isset($clients_spouse[0])) {
                $spouse_user_id_from_clients =  $clients_spouse[0];
            }                                    
            // $spouse_user_id_from_clients =  $clients_spouse[0];
            $request->validate([
                      'spouse_email' => 'required|unique:clients,email,' . $spouse_user_id_from_clients
                   ],
                   [
                      'spouse_email.required'=> 'Spouse email is required.',
                      'spouse_email.unique'=> 'Spouse email has already been taken.'
                   ]);
                   
                   
                $saveSpouseData = DB::table('clients')->updateOrInsert([
                'client_reference_id'   => $_POST["client_reference_id"],
                'client_type' => 'Spouse'
                ],
                [
                'nickname' => $spouse_nickname,
                'initials' => $spouse_initials,
                'first_name' => $spouse_first_name,
                'middle_name' => $spouse_middle_name,
                'last_name' => $spouse_last_name,
                'email' => $spouse_email,
                'date_of_birth' => $spouse_birth_day,
                'retirement_age' => $spouse_retirement_age,
                'marital_status' => $spouse_marital_status,
        	    'advisor_id' => $advisor_id,
                'capture_user_id' => $capture_user_id,
                'client_reference_id' => $client_reference_id,
                'gender' => $spouse_gender,
                'vehicle_vin_number' => $spouse_vehicle_number
            ]);    

                if(isset($spouse_user_id) && $spouse_user_id > 0) {
                    $saveSpouseUserData = DB::select("update users set 
                                                name = '$spouse_first_name' , 
                                                surname = '$spouse_last_name' , 
                                                idNumber = '$spouse_id_number' , 
                                                phone = '$spouse_phone' , 
                                                gender = '$spouse_gender' , 
                                                dob = '$spouse_birth_day' 
                                                where 
                                                id = '$spouse_user_id'");
                } else {
                        $password =  md5($spouse_first_name.'-'.$spouse_last_name);
                        $user_types = 'Client';
                            $saveSpouseUserData = DB::select("insert into  users 
                                        (   `email`, 
                                            `password`, 
                                            `name`, 
                                            `surname`, 
                                            `idNumber`, 
                                            `type`, 
                                            `phone`, 
                                            `gender`, 
                                            `dob`  ) 
                                    values (
                                            '". $spouse_email ."', 
                                            '". $password ."' , 
                                            '". $spouse_first_name ."' , 
                                            '". $spouse_last_name ."' , 
                                            '". $spouse_id_number ."', 
                                            '". $user_types ."' , 
                                            '". $spouse_phone ."',
                                            '". $spouse_gender ."', 
                                            '". $spouse_birth_day ."'
                                            )
                                    ");
                            $lastInsertId = DB::getPdo()->lastInsertId();
                            $saveSpouseUserData1 = DB::select("update clients set 
                                                user_id = '".$lastInsertId."'  
                                                where 
                                                id = '$spouse_user_id_from_clients'");
                }
        }
        if(isset($_POST['dependant_show']) && $_POST['dependant_show'] === "yes") {
        if(isset($_POST['dependant_type']))
        {
            $count = count($_POST['dependant_type']); 
            if($count > 0){
             for($j = 0; $j < $count; $j++)
                { 
                    if(isset($_POST['dependant_id'][$j]))
                    {
                        DB::select("update dependants set
                        dependant_type = '".$_POST['dependant_type'][$j]."',
                        first_name = '".$_POST['dependant_first_name'][$j]."', 
                        middle_name = '".$_POST['dependant_middle_name'][$j]."',
                        last_name = '".$_POST['dependant_last_name'][$j]."', 
                        date_of_birth = '".$_POST['dependant_year'][$j]."-".$_POST['dependant_month'][$j]."-".$_POST['dependant_day'][$j]."', 
                        gender = '".$_POST['dependant_gender'][$j]."', 
                        dependant_until_age = '".$_POST['dependant_age'][$j]."'
                        where id = '".$_POST['dependant_id'][$j]."'
                        ");
                    }
                    else
                    {
                        DB::select("INSERT into dependants values (
                        null,
                        '".$userId."', 
                        '".$userId."', 
                        '".$client_reference_id."',
                        '".$_POST['dependant_type'][$j]."',
                        '".$_POST['dependant_first_name'][$j]."', 
                        '".$_POST['dependant_middle_name'][$j]."', 
                        '".$_POST['dependant_last_name'][$j]."', 
                        '".$_POST['dependant_year'][$j]."-".$_POST['dependant_month'][$j]."-".$_POST['dependant_day'][$j]."', 
                        '".$_POST['dependant_gender'][$j]."', 
                        '".$_POST['dependant_age'][$j]."')
                        ");
                    }
                    
                }   
            }
        }
        }

        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Update page",
            'action_details' => json_encode($_POST),
            'date' => DB::raw('now()')
        ]);
        
        if($_SESSION['userType'] == 'Client') {
            return redirect()->route('clientEdit', ['id' => $_SESSION['client_reference_id']]);
        } else {
            return redirect()->route('clientList');
        }
    }
    
 
    
    public function spouseSave(Request $request)  
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Information Spouse Save page",
            'date' => DB::raw('now()')
        ]); 
        
        /*
        print_r("<pre>"); var_dump($_POST);
        $advisor_id = $userId;
        $main_first_name = $_POST["main_first_name"];
        $main_last_name = $_POST["main_last_name"];
        $main_birth_day = $_POST["main_day"]."-".$_POST["main_month"]."-".$_POST["main_year"];
        $main_retirement_age = $_POST["main_retirement_age"];
        $main_gender = $_POST["main_gender"];
        $main_marital_status = $_POST["main_marital_status"];
        $main_client_type = "Main Client";
        $capture_user_id = $userId;
        $client_reference_id = 'fna0000001';
        //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
        DB::select("INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')");*/
        
        $advisor_id = $userId;
        $spouse_first_name = $_POST["spouse_first_name"];
        $spouse_last_name = $_POST["spouse_last_name"];
        $spouse_birth_day = $_POST["spouse_day"]."-".$_POST["spouse_month"]."-".$_POST["spouse_year"];
        $spouse_retirement_age = $_POST["spouse_retirement_age"];
        $spouse_gender = $_POST["spouse_gender"];
        $spouse_marital_status = $_POST["spouse_marital_status"];
        $spouse_marriage_type = $_POST["spouse_marriage_type"];
        $spouse_client_type = "Spouse";
        $capture_user_id = $userId;
        $client_reference_id = 'fna0000002';
        //echo "INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$main_first_name', '$main_last_name', '$main_birth_day', '$main_retirement_age', '$main_gender', '$main_marital_status', '$main_client_type', '$capture_user_id', '$client_reference_id')"; die();
        $saveData = DB::select("INSERT into clients (advisor_id, first_name, last_name, date_of_birth, retirement_age, gender, marital_status, marriage_type, client_type, capture_user_id, client_reference_id) values ('$advisor_id', '$spouse_first_name', '$spouse_last_name', '$spouse_birth_day', '$spouse_retirement_age', '$spouse_gender', '$spouse_marital_status', '$spouse_marriage_type', '$spouse_client_type', '$capture_user_id', '$client_reference_id')");
        return redirect()->route('clientList');  
    }
    public function clientList()
    {
        session_start();    
        if(empty($_SESSION['login']))
        {         
            header("location: https://fna2.phpapplord.co.za/public/");               
            exit;
        }
        if(isset($_SESSION['userType']) && $_SESSION['userType'] === 'Client') {
            // return->redirect('clientEdit', ['id' => $client]);
            return redirect()->route('clientEdit',['id'=>$_SESSION['userId']]);
        }
        $userId = $_SESSION['userId'];
        $authenticatedUserType = isset($_SESSION['userType']) ? $_SESSION['userType'] : "";
        $data = session()->all();

        //print_r($data);

        
        $personalInfoModule = 'Personal Information';
        $dependantsModule = 'Dependants';
        $assetsModule = 'Assets';
        $liabilitiesModule = 'Liabilities';
        $personalBudgetModule = 'Personal budget'; 
        $riskModule = 'Risk Objectives';
        $retirementRiskModule = 'Retirement Risks Objectives'; 
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");
        $dependantsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$dependantsModule'");
        $assetsModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$assetsModule'");
        $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalBudgetModuleModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalBudgetModule'");
        $riskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$riskModule'");
        $retirementRiskModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$retirementRiskModule'");
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."'");
        if(!isset($getroleId[0]->groupId))
        {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        }
        else
        {
            //var_dump($getroleId[0]->groupId); die();
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$retirementRiskModuleModuleId[0]->id."'");
            if(!isset($getretirementRiskAclAccessId[0]->accessId))
            {
                $getretirementRiskAclAccess = "noAccess";
               // echo $getretirementRiskAclAccess; die();
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
            
            
            
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$assetsModuleModuleId[0]->id."'");
            if(!isset($assetsModuleAclAccessId[0]->accessId))
            {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
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
            
            
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$getroleId[0]->groupId."' and moduleId = '".$personalBudgetModuleModuleModuleId[0]->id."'");
            if(!isset($personalBudgetModuleAclAccessId[0]->accessId))
            {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            }
            else
            {
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '".@$personalBudgetModuleAclAccessId[0]->accessId."'");
                if(!isset($liabilitiesModuleAccessName[0]->name))
                { 
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    
                }
                else
                {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }
            
            $personalInfoModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".@$getroleId[0]->groupId."' and moduleId = '".$personalInfoModuleModuleId[0]->id."'");
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
        $Module = 'Liabilities';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $roleId = DB::select("SELECT * FROM `permissions` where userId = '".$userId."' ");
        $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '".$roleId[0]->groupId."' and moduleId = '".$getModuleId[0]->id."'");
        // var_dump($getAclAccessId); die();
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
        
        // $userRole = DB::select("SELECT * FROM `user_groups` WHERE `id` = '".@$roleId[0]->groupId."'");
        // // dd($userRole);
        //     DB::table('audit')->insert([
        //         'id' => null,
        //         'user' => $userId,
        //         'module' => $personalInfoModule,
        //         'role' => $userRole[0]->name,
        //         'action' => "Landed on Client List",
        //         'date' => DB::raw('now()')
        //     ]);        
        
        if($authenticatedUserType == 'App Administrator')
        {
            $clients = DB::table('clients')->where('client_type', 'Main Client')->get();
        } elseif ($authenticatedUserType == "Main Advisor") {
            $clients = DB::table('clients')->where('client_type', 'Main Client')->where('advisor_id', $userId)->get();
        } else {
            $clients = DB::table('clients')->where('client_type', 'Main Client')->where('capture_user_id', $userId)->get();
        }
        // dd($clients);
        return view('clients.clientList', ['authenticatedUserType' => $authenticatedUserType, 'userId' => $userId, 'getroleId' => $getroleId, 'clients' => $clients, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName, 'getAccessName'=>$getAccessName, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    } 
    
    public function exportClients() 
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $personalInfoModule = 'Personal Information';
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Client Export CSV Page",
            'date' => DB::raw('now()')
        ]); 
        $file_name = 'clientLists.csv';
        $assets_list = DB::select("SELECT
                                    id, advisor_id,
                                    first_name, last_name,
                                    email, date_of_birth,
                                    retirement_age, gender,
                                    marital_status, marriage_type,
                                    client_type, client_reference_id,
                                    address, city,
                                    state, zip,
                                    country, vehicle_vin_number
                                FROM `clients`
                                ORDER BY `clients`.`id`
                                DESC ");
 
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename=$file_name",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );
    
        $columns = array('id', 'advisor_id', 'first_name', 'last_name', 'email', 'date_of_birth', 
                        'retirement_age', 'gender', 'marital_status','marriage_type', 'client_type',
                        'client_reference_id', 'address',  'city',
                        'state', 'zip', 'country', 'vehicle_vin_number');

            $callback = function() use($assets_list, $columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);
    
                foreach ($assets_list as $asset) {
                    $row['id']  = $asset->id;
                    $row['advisor_id'] = $asset->advisor_id;
                    $row['first_name'] = $asset->first_name;
                    $row['last_name'] = $asset->last_name;
                    $row['email'] = $asset->email;
                    $row['date_of_birth'] = $asset->date_of_birth;
                    $row['retirement_age'] = $asset->retirement_age;
                    $row['gender'] = $asset->gender;
                    $row['marital_status'] = $asset->marital_status;
                    $row['marriage_type'] = $asset->marriage_type;
                    $row['client_type'] = $asset->client_type;
                    $row['client_reference_id'] = $asset->client_reference_id;
                    $row['address'] = $asset->address;
                    $row['city'] = $asset->city;
                    $row['state'] = $asset->state;
                    $row['zip'] = $asset->zip;
                    $row['country'] = $asset->country;                  
                    $row['vehicle_vin_number'] = $asset->vehicle_vin_number;                   
                    
                    fputcsv($file, array( 
                        $row['id'],
                        $row['advisor_id'],
                        $row['first_name'],
                        $row['last_name'],
                        $row['email'],
                        $row['date_of_birth'], 
                        $row['retirement_age'],
                        $row['gender'],
                        $row['marital_status'],
                        $row['marriage_type'],
                        $row['client_type'],
                        $row['client_reference_id'],
                        $row['address'],
                        $row['city'],
                        $row['state'],
                        $row['zip'],
                        $row['country'],
                        $row['vehicle_vin_number']
                    ));
                }
                fclose($file);
            };
        return response()->stream($callback, 200, $headers);        
    }
    
    public function getClientAuthorization() 
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {             
            header("location: https://fna2.phpapplord.co.za/public/");               
            exit;
        } 
        // print_r($_SESSION);
        $client_reference_id = $_SESSION['client_reference_id'];

        // $clients = DB::table('clients')
        //             ->where('client_reference_id', '=', '".$client_reference_id."')->get();
        //     // select("SELECT * FROM `clients` where client_reference_id =  '".$client_reference_id."' ");
            
        $clients = DB::select("select * from clients Where client_reference_id = '".$client_reference_id."' and client_type In ('Main Client','Spouse')");            
            // print_r($clients);die;
        $authorizations_label =  DB::table('client_authorization_settings')->get();
        // $main_client =  DB::table('client_authorizations')
        //                 ->where('client_reference_id','".$client_reference_id."')
        //                 ->where('client_type' ,'Main Client');
                        // ->get();
        $main_client = DB::select(" select * from client_authorizations Where client_reference_id = '".$client_reference_id."' and client_type In ('Main Client')");                          
        $main_client_arr = $spouse_client_arr = [];                        
        if(isset($main_client)) {
            // print_r($main_client);
            foreach($main_client as $main_data) {
                // print_r($main_data);
                array_push($main_client_arr,$main_data->bank_statement);
                array_push($main_client_arr,$main_data->policy_from_astute);
                array_push($main_client_arr,$main_data->investment_debts);
                array_push($main_client_arr,$main_data->assets_liabilites_data);
                array_push($main_client_arr,$main_data->cipc_data);
                // print_r($main_client_arr);
            }
        }
        // print_r($main_client_arr);die;
        // $spouse_client =  DB::table('client_authorizations')
        //                 ->where('client_reference_id','".$client_reference_id."')
        //                 ->where('client_type' ,'Main Client')
        //                 ->get();
        $spouse_client = DB::select(" select * from client_authorizations Where client_reference_id = '".$client_reference_id."' and client_type In ('Spouse')");                          
                            
        if(isset($spouse_client)) {
            foreach($spouse_client as $spouse_data) {
                array_push($spouse_client_arr,$spouse_data->bank_statement);
                array_push($spouse_client_arr,$spouse_data->policy_from_astute);
                array_push($spouse_client_arr,$spouse_data->investment_debts);
                array_push($spouse_client_arr,$spouse_data->assets_liabilites_data);
                array_push($spouse_client_arr,$spouse_data->cipc_data);
            }
        }
        return view('clients.createClientAuthorization', ['clients' => $clients, 'authorizations_label' => $authorizations_label, 'client_reference_id' => $client_reference_id,
        'main_client' => $main_client_arr,
        'spouse_client' => $spouse_client_arr ]);
    }
    public function saveClientAuthorization(Request $request)
    {
        session_start(); 
        if(empty($_SESSION['login']))
        {             
            header("location: https://fna2.phpapplord.co.za/public/");               
            exit;
        }
        $userId = $_SESSION['userId'];
        // for($i=1; $i<=5; $i++) {
        //     echo $_POST["main_".$i]. '=='. $i . "<br/>";
        // }
        $bank = isset($_POST["main_1"]) ? $_POST["main_1"] : "0";
        $astute = isset($_POST["main_2"]) ? $_POST["main_2"] : "0";
        $debts = isset($_POST["main_3"]) ? $_POST["main_3"] : "0";
        $asset_liability = isset($_POST["main_4"]) ? $_POST["main_4"] : "0";
        $cipc_data = isset($_POST["main_5"]) ? $_POST["main_5"] : "0";
                                        
        $main_client =  DB::table('client_authorizations')->updateOrInsert(
            ['client_id' => $userId, 'advisor_id' => $userId , 'client_type' => 'Main Client', 'client_reference_id' => $request->client_reference_id ],
            [
                'client_reference_id' => $request->client_reference_id, 
                'client_type' => 'Main Client', 
                'bank_statement' => $bank ,
                'policy_from_astute' => $astute ,
                'investment_debts' => $debts ,
                'assets_liabilites_data' => $asset_liability , 
                'cipc_data' => $cipc_data 
            ] 
        );
        // $clients_count = DB::table('clients')
        //             ->where('client_reference_id', '".$request->client_reference_id."')->count();
        //                     echo "?????????" . $clients_count;
                            
        $clients_count = DB::select(" select id from clients Where client_reference_id = '".$request->client_reference_id."' and client_type = 'Spouse'");
        // echo $request->client_reference_id .  DB::table('clients')
        
        //             ->where('client_reference_id', '".$request->client_reference_id."')->toSql();         
                    
                    // dd($clients_count);
        // echo "?????????" . count($clients_count);
        if(isset($clients_count) && count($clients_count) > 0) {
            foreach($clients_count as $client_count) {
                $spouseId = $client_count -> id;   
            }

                
                
            // die;
            // for($i=1; $i<=5; $i++) {
            //     echo $_POST["spouse_".$i]. '=='. $i . "<br/>";
            // }
            
            $bank = isset($_POST["spouse_1"]) ? $_POST["spouse_1"] : "0";
            $astute = isset($_POST["spouse_2"]) ? $_POST["spouse_2"] : "0";
            $debts = isset($_POST["spouse_3"]) ? $_POST["spouse_3"] : "0";
            $asset_liability = isset($_POST["spouse_4"]) ? $_POST["spouse_4"] : "0";
            $cipc_data = isset($_POST["spouse_5"]) ? $_POST["spouse_5"] : "0";
                                        
            $flight =  DB::table('client_authorizations')->updateOrInsert(
            ['client_id' => $spouseId, 'advisor_id' => $spouseId , 'client_type' => 'Spouse','client_reference_id' => $request->client_reference_id ],
            [
                'client_reference_id' => $request->client_reference_id, 
                'client_type' => 'Spouse', 
                'bank_statement' => $bank ,
                'policy_from_astute' => $astute ,
                'investment_debts' => $debts ,
                'assets_liabilites_data' => $asset_liability , 
                'cipc_data' => $cipc_data 
            ]
        );
        }
        //session()->flash('success', 'Authorisation data updated !');
        //return redirect()->back();  
        return redirect()->route('package', ['client_reference_id' => $request->client_reference_id,'client_type'=>'Main Client','default_package'=>'1']);
    }   
    
    public function checkMainClientVIN(Request $request){
        $main_vehicle_number = $request->main_vehicle_number;
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.nptracker.co.za/NPS-LIC/?vin=".$main_vehicle_number."&token=f62a2665174a21b9ac9c4f752e2b0496b50c-6197-11ed-b74f-a266",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"scan_string\"\r\n\r\nhttps://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTA9gNqQBmIUQ_bczC0mWsDeECqhU1hbchH5A&usqp=CAU\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
            "postman-token: e06c8d32-4d2f-ba59-60d0-f396fa5c4a75"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
          return json_encode(array('msg'=>'error','data'=>''));
        } else {
         return json_encode(array('msg'=>'success','data'=>$response));
        }
        
    }

    
}
?>