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
use DB;
use DataTables;
use App\Astute;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use URL;

class SeedAnalyticsController extends Controller
{
    public function summary($client_reference_id)  
    {
        session_start();
        $_SESSION['client_reference_id']=$client_reference_id;
        return view('seedanalytics.summary');
    }
}