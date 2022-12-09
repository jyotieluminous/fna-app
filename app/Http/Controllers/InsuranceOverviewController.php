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
use Illuminate\Support\Facades\Hash;
use DB;
use Mail;
use App\Mail\SendUserMail;
use Intervention\Image\Facades\Image as InterventionImage;
use Illuminate\Support\Facades\Storage;

use DataTables;

class InsuranceOverviewController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    public function index($client_reference_id)  
    {
        $insuranceCategory = DB::select("SELECT * FROM insurance_category"); 
        $insuranceCashflow = DB::select("SELECT * FROM insurance_cashflow"); 
        return view('insurance.InsuranceOverview',[
            'client_reference_id'=>$client_reference_id,
            'insuranceCategory'=>$insuranceCategory,
            'insuranceCashflow'=>$insuranceCashflow
            ]);
    }
    public function SaveInsuranceOverview(Request $request,$client_reference_id)  
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        if (isset($_POST['insurance_overview'])) {
            $insuranceOverview = count($_POST['insurance_overview']);
            
            for ($i = 0; $i < ($insuranceOverview); $i++) {
                $insurance_overview = $_POST['insurance_overview'][$i];
                $insurance_sub_overview = $_POST['insurance_sub_overview'][$i];
                $amount = $_POST['amount'][$i];
                $lastUserMainID = DB::table('insurance_data')->insertGetId(
                    [
                        'advisor_id' => $userId, 
                        'capture_advisor_id'=> $userId, 
                        'insurance_overview' => $insurance_overview, 
                        'insurance_sub_overview' => $insurance_sub_overview, 
                        'amount' => $amount,
                        'client_reference_id' => $client_reference_id
                    ]);
            }
        }
        return redirect()->route('EditnsuranceOverview', $client_reference_id);
    }
    public function EditnsuranceOverview($client_reference_id)  
    {
        $insuranceCategory = DB::select("SELECT * FROM insurance_category"); 
        $insuranceCashflow = DB::select("SELECT * FROM insurance_cashflow");
        $insuranceData = DB::select("SELECT * FROM `insurance_data` INNER JOIN insurance_category ON insurance_category.id =insurance_data.`insurance_overview`
            INNER JOIN insurance_cashflow ON insurance_cashflow.id =insurance_data.`insurance_sub_overview` WHERE client_reference_id = '".$client_reference_id."'");
        return view('insurance.insuranceUpdateView',[
            'client_reference_id'=>$client_reference_id,
            'insuranceCategory'=>$insuranceCategory,
            'insuranceCashflow'=>$insuranceCashflow,
            'insuranceData'=>$insuranceData
            ]);
    }
     public function UpdateInsuranceOverview(Request $request,$client_reference_id)  
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        if (isset($_POST['insurance_overview'])) {
            DB::select("Delete FROM insurance_data where client_reference_id = '$client_reference_id' ");
            $insuranceOverview = count($_POST['insurance_overview']);
            for ($i = 0; $i < ($insuranceOverview); $i++) {
                $insurance_overview = $_POST['insurance_overview'][$i];
                $insurance_sub_overview = $_POST['insurance_sub_overview'][$i];
                $amount = $_POST['amount'][$i];
                $lastUserMainID = DB::table('insurance_data')->insertGetId(
                    [
                        'advisor_id' => $userId, 
                        'capture_advisor_id'=> $userId, 
                        'insurance_overview' => $insurance_overview, 
                        'insurance_sub_overview' => $insurance_sub_overview, 
                        'amount' => $amount,
                        'client_reference_id' => $client_reference_id
                    ]);
            }
        }
        return redirect()->route('index', $client_reference_id);
    }
}