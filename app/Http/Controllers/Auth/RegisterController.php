<?php

namespace App\Http\Controllers\Auth;

use App\Role;
use App\User;
use Exception;
use App\Company;
use App\BankDocument;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\SMSCampaignConfiguration;
use App\EmailCampaignConfiguration;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'id_number' => ['required', 'integer'],
            'cell_number' => ['required'],
            'address' => ['required'],
      
        ]);
    }

    public function registerBusiness(Request $request)
    {

            $company = new Company();
            $company->account_type = $request->request_type == 'C' ? 'Business' : 'Individual';
            $company->company_name = $request->company_name;
            $company->company_street = $request->company_street;
            $company->company_city = $request->company_city;
            $company->company_code = $request->company_code;
            $company->vat_number = $request->vat_number;
            $company->finance_email = $request->finance_email;
            $company->operation_email = $request->operation_email;
            $company->company_telephone = str_replace(' ', '',$request->company_telephone);
            $company->company_cell = str_replace(' ', '',$request->company_cell);
            $company->bank = $request->bank;
            $company->bank_holder_ini = $request->bank_holder_ini;
            $company->bank_holder = $request->bank_holder;
            $company->bank_account_type = $request->bank_accout_type == 'C' ? 'Cheque' : 'Savings';
            $company->account_number = $request->account_number;
            $company->branch_code = $request->branch_code;  
            
            $company->save();
                
            if($request->hasFile('bank_document'))
            {
                $file = $request->file('bank_document');
               
                $path = Storage::disk('public')->putFileAs('bank_document', $file, $company->id . '.' . $file->guessExtension());
                
                $document = new BankDocument();
                $document->path = $path;
                $company->bank_document()->save($document);
            }
            
            $user = new User();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->id_number = $request->id_number;
            $user->cell_number = str_replace(' ', '',$request->cellNumber);
            $user->street = $request->street;
            $user->city_town = $request->city;
            $user->code = $request->code;
            $user->company_id = $company->id;
            $user->save();

            $role = Role::where('name', 'Company Admin')->get();
            
            $user->roles()->attach($role);
            
            //add company email configurations
            $email_config = new EmailCampaignConfiguration();
            $email_config->is_1st_reminder_enabled = true;
            $email_config->is_2nd_reminder_enabled = true;
            $email_config->is_handover_enabled = true;
            $email_config->is_letter_of_demand_enabled = true;
            $email_config->is_final_notice_enabled = true;
            $email_config->company_id = $company->id;
            $email_config->save();

            //add company sms configurations
            $sms_config = new SMSCampaignConfiguration();
            $sms_config->is_1st_reminder_enabled = true;
            $sms_config->is_2nd_reminder_enabled = true;
            $sms_config->is_handover_enabled = true;
            $sms_config->is_letter_of_demand_enabled = true;
            $sms_config->is_final_notice_enabled = true;
            $sms_config->company_id = $company->id;
            $sms_config->save();
            
            $request->session()->flash('success', 'Business Registered Successfully');

            return redirect()->route('login');      

    }
    
    public function validate_account(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        $bankHolder = explode(" ", $request->bankHolder);
        $surname = ucwords($bankHolder[1]);
        $initials = strtoupper($bankHolder[0]);

        $url = 'https://www.easyavs.co.za:7220/AVSService.svc/PartnerServices/RunAVSRequest';

        $xml = "<SRQ>
        <CR>
        <U>PieterBen</U>
        <P>f68668e2c4d695fdf8fd2a486b8453bbbde9f038d1f477943cbb6bfe28e2a9cb</P>
        </CR>
        <RL>
        <R>
        <CR>Myreference02</CR> 
        <RT>".$request->requestType."</RT> 
        <AT>".$request->accountType."</AT> 
        <IT>SID</IT>
        <IN>".$initials."</IN>
        <N>".$surname."</N> 
        <ID>".$request->id_number."</ID>
        <TX></TX>
        <BC>".$request->branchCode."</BC>
        <AN>".$request->accountNumber."</AN>
        <PN>0710121602</PN>
        <EM>demuenatorrr@gmail.com</EM>
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
        //print_r("<pre>");//var_dump($myimage["ARL"]->AR->RQV); die(); 
        //print_r("<pre>");//var_dump($myimage["ARL"]->AR->RQV); die(); 
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

     public function validate_accounts($bankHolder, $bankHolderIniValue, $accountNumber, $bankCode, $idNumber, $requestType, $bankAccountType)
    {
    
        $surname = strtoupper($bankHolder);
        $initials = strtoupper($bankHolderIniValue);
        $bankAccountType = strtoupper($bankAccountType);
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
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
 
        $user =  User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'id_number' => $data['id_number'],
            'cell_number' => $data['cell_number'],
            'address' => $data['address']
        ]);

        $company = new Company();
        $company->account_type = $data['account_type'];
        $company->company_name = $data['company_name'];
        $company->company_address = $data['company_address'];
        $company->vat_number = $data['vat_number'];
        $company->finance_email = $data['finance_email'];
        $company->operation_email = $data['operation_email'];
        $company->company_telephone = $data['company_telephone'];
        $company->company_cell = $data['company_cell'];
        $company->bank = $data['bank'];
        $company->bank_holder = $data['bank_holder'];
        $company->account_number = $data['account_number'];
        $company->branch_code = $data['branch_code'];  

        $user->company()->save($company);
        return $user;
    }
    
    public function validateUniqueEmail($email)
    {
        $users = User::where('email', '=', $email)->get();

        if(count($users) > 0)
        {
            return response()->json(['message' => 'Invalid'],200);

        }else{
            return response()->json(['message' => 'Valid'],200);
        }
    }
}
