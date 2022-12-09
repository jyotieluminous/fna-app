<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Imports\BankStatementImport;
use Maatwebsite\Excel\Facades\Excel;



class BankStatementController extends Controller
{

public function storeBankLoginTransactions(Request $request) {

    $client_type = $request->client_type;
    $client_reference_id = $request->client_reference_id;

    $client = DB::table('clients')->where('client_reference_id', $client_reference_id)->where('client_type', $client_type)->first();


// income capture


foreach ($request->incomes as $transaction) {

    $categroy_name = $transaction['category']['title'];

    $income_expenses_type = 1;
    

        if(DB::table('budget')
                ->where('item_type', $transaction['full_title'])
                ->where('item_name', $categroy_name)
                ->where('income_expenses_type', $income_expenses_type)
                ->whereDate('capture_date', $transaction['transaction_date'])
                ->where('client_id', $client->id)
                ->count() == 0) {   
                    

                $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 1)->count();
                if($income_name_count > 0)
                {
                    $item_name = DB::table('income_expense_type')
                    ->where('income_expense_name', $categroy_name)
                    ->where('income_expense_type', 1)
                    ->first();

                }else {

                    $item_name_id = DB::table('income_expense_type')
                                    ->insertGetId([
                                        'income_expense_name' => $categroy_name,
                                        'income_expense_type' => 1
                                    ]);
                }
    

          


                    $budget_id = DB::table('budget')->insertGetId([
                        'client_reference_id' => $client_reference_id,
                        'advisor_id' => $client->advisor_id,
                        'advisor_capture_id' => $client->advisor_id,
                        'client_id' => $client->id,
                        'client_type' => $client->client_type,
                        'income_expenses_type' => $income_expenses_type,
                        'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                        'item_type' => $transaction['full_title'],
                        'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                        'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                        'item_value' => abs($transaction['amount']),
                        'capture_date' => $transaction['transaction_date']
                    ]);
        
                        DB::table('yearly_budget')->insert([
                            'client_reference_id' => $client->client_reference_id,
                            'advisor_id' => $client->advisor_id,
                            'advisor_capture_id' => $client->advisor_id,
                            'budget_id' => $budget_id,
                            'client_id' => $client->id,
                            'client_type' => $client->client_type,
                            'income_expenses_type' => $income_expenses_type,
                            'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_type' => $transaction['full_title'],
                            'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                            'item_value' => abs($transaction['amount']),
                            'month' => explode('-', $transaction['transaction_date'])[1],
                            'year' => explode('-', $transaction['transaction_date'])[0],
                            'capture_date' => $transaction['transaction_date']
                        ]);
                    
            
        }
    }

        // expense capture

foreach ($request->expenses as $transaction) {

    $categroy_name = $transaction['category']['title'];

    $income_expenses_type = 2;
    

        if(DB::table('budget')
                ->where('item_type', $transaction['full_title'])
                ->where('item_name', $categroy_name)
                ->where('income_expenses_type', $income_expenses_type)
                ->whereDate('capture_date', $transaction['transaction_date'])
                ->where('client_id', $client->id)
                ->count() == 0) {   
                    

                $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 2)->count();
                if($income_name_count > 0)
                {
                    $item_name = DB::table('income_expense_type')
                    ->where('income_expense_name', $categroy_name)
                    ->where('income_expense_type', 2)
                    ->first();

                }else {

                    $item_name_id = DB::table('income_expense_type')
                                    ->insertGetId([
                                        'income_expense_name' => $categroy_name,
                                        'income_expense_type' => 2
                                    ]);
                }
           



                    $budget_id = DB::table('budget')->insertGetId([
                        'client_reference_id' => $client_reference_id,
                        'advisor_id' => $client->advisor_id,
                        'advisor_capture_id' => $client->advisor_id,
                        'client_id' => $client->id,
                        'client_type' => $client->client_type,
                        'income_expenses_type' => $income_expenses_type,
                        'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                        'item_type' => $transaction['full_title'],
                        'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                        'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                        'item_value' => abs($transaction['amount']),
                        'capture_date' => $transaction['transaction_date']
                    ]);
        
                        DB::table('yearly_budget')->insert([
                            'client_reference_id' => $client->client_reference_id,
                            'advisor_id' => $client->advisor_id,
                            'advisor_capture_id' => $client->advisor_id,
                            'budget_id' => $budget_id,
                            'client_id' => $client->id,
                            'client_type' => $client->client_type,
                            'income_expenses_type' => $income_expenses_type,
                            'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_type' => $transaction['full_title'],
                            'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                            'item_value' => abs($transaction['amount']),
                            'month' => explode('-', $transaction['transaction_date'])[1],
                            'year' => explode('-', $transaction['transaction_date'])[0],
                            'capture_date' => $transaction['transaction_date']
                        ]);
                    
            
        }

        
        
    
    }

    return response()->json(['status' => 'success']);
        
    }
    
    
    public function download_csv_template() {

        return response()->download((public_path()."/bank_statement/StatementImportTemplate.csv"));

    }

    public function bank_csv_upload(Request $request) {
        session_start();


        if (empty($_SESSION['login'])) {
            header("location: http://127.0.0.1:8000/");
            exit;
        }

        $request->validate([
            'file'  => 'required|mimes:csv,txt|max:10000'
        ]);

        if ($request->hasFile('file')) {

            $data = Excel::toArray(new BankStatementImport, $request->file);

            $data = collect($data)->flatten(1);

            $first_key = $data->keys()->first();

            $transactions = $data->forget($first_key)->values();

            $transactions = $transactions->map(function($collection) {
                return [
                    'full_title' => $collection[0],
                    'transaction_date' => $collection[1],
                    'post_date' => $collection[2],
                    'amount' => $collection[3],
                    'service_fee' => $collection[4],
                    'running_balance' => $collection[5],
                    'transaction_type_id' => $collection[6],
                    'currency' => $collection[7],
                    'order' => $collection[8],
                    'id' => $collection[9],
                    'account_id' => $collection[10],
                    'title' => $collection[11]
                ];
            });

            // $transactions = json_encode($transactions);
            
            // dd($transactions);
            $data = '';

            foreach($transactions as $transaction)
            {
                $data .= '{full_title:\"'.$transaction['full_title'].'\",transaction_date:\"'.$transaction['transaction_date'].'\",post_date:\"'.$transaction['post_date'].'\",amount:'.$transaction['amount'].',service_fee:'.$transaction['service_fee'].',running_balance:'.$transaction['running_balance'].',transaction_type_id:'.$transaction['transaction_type_id'].',currency:\"'.$transaction['currency'].'\",order:'.$transaction['order'].',id:\"'.$transaction['id'].'\",account_id:\"'.$transaction['account_id'].'\",title:\"'.$transaction['title'].'\"},';
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/category/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "query": "query {categorizeBankTransactions(input:['.$data.']) {transactions {id, account_id, category{id, title}, amount, full_title, title, transaction_date, currency, running_balance, service_fee, order}  salary{id, category{id, title}, amount, full_title, title, transaction_date, currency}  }}",
                "variables": {}
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '. $this->auth(),
                'Content-Type: application/json'
            ),
            ));
        
            $response = curl_exec($curl);
        
            curl_close($curl);

            DB::table('integrationData')->insert([
                'ClientReference' => $request->client_reference_id,
                'jsonobject' => json_encode($response),
                'Type' => 'Bank Statement API'
                ]);
            
            $transactions = json_decode($response);

        //insert into income and expenses. 
       foreach ($transactions->data->categorizeBankTransactions->transactions as $transaction) {

        $categroy_name = $transaction->category->title;

        if($transaction->amount > 0)
        {
            $income_expenses_type = 1;

        }else {

            $income_expenses_type = 2;
        }


        
        $client = DB::table('clients')->where('client_type', $request->client_type)->where('client_reference_id', $request->client_reference_id)->first();

            if(DB::table('budget')
                    ->where('item_type', $transaction->full_title)
                    ->where('item_name', $categroy_name)
                    ->where('income_expenses_type', $income_expenses_type)
                    ->whereDate('capture_date', $transaction->transaction_date)
                    ->where('client_id', $client->id)
                    ->count() == 0) {   
                        
                        if($transaction->amount > 0)
                        {

                            $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 1)->count();
                            if($income_name_count > 0)
                            {
                                $item_name = DB::table('income_expense_type')
                                ->where('income_expense_name', $categroy_name)
                                ->where('income_expense_type', 1)
                                ->first();

                            }else {

                                $item_name_id = DB::table('income_expense_type')
                                                ->insertGetId([
                                                    'income_expense_name' => $categroy_name,
                                                    'income_expense_type' => 1
                                                ]);
                            }
               

                        }else {

                            $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 2)->count();
                            if($income_name_count > 0)
                            {
                                $item_name = DB::table('income_expense_type')
                                ->where('income_expense_name', $categroy_name)
                                ->where('income_expense_type', 2)
                                ->first();

                            }else {

                                $item_name_id = DB::table('income_expense_type')
                                                ->insertGetId([
                                                    'income_expense_name' => $categroy_name,
                                                    'income_expense_type' => 2
                                                ]);
                            }

                        }


                        $budget_id = DB::table('budget')->insertGetId([
                            'client_reference_id' => $client->client_reference_id,
                            'advisor_id' => $client->advisor_id,
                            'advisor_capture_id' => $client->advisor_id,
                            'client_id' => $client->id,
                            'client_type' => $client->client_type,
                            'income_expenses_type' => $income_expenses_type,
                            'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_type' => $transaction->full_title,
                            'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                            'item_value' => abs($transaction->amount),
                            'capture_date' => $transaction->transaction_date
                        ]);
            
                            DB::table('yearly_budget')->insert([
                                'client_reference_id' => $client->client_reference_id,
                                'advisor_id' => $client->advisor_id,
                                'advisor_capture_id' => $client->advisor_id,
                                'budget_id' => $budget_id,
                                'client_id' => $client->id,
                                'client_type' => $client->client_type,
                                'income_expenses_type' => $income_expenses_type,
                                'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                                'item_type' => $transaction->full_title,
                                'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                                'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                                'item_value' => abs($transaction->amount),
                                'month' => explode('-', $transaction->transaction_date)[1],
                                'year' => explode('-', $transaction->transaction_date)[0],
                                'capture_date' => $transaction->transaction_date
                            ]);
                        
                
            }
        
    }

         return redirect()->back()->withSuccess('Bank statement statement successfully uploaded');
          
        }

    }

    public function bank_login($client_reference, $client_type) {

        session_start();

        if (empty($_SESSION['login'])) {
            header("location: http://127.0.0.1:8000/");
            exit;
        }


        return view('bank_login.bank_login')->with(['client_reference_id' => $client_reference, 'client_type' => $client_type]);
    }
        public function bank_login_test($client_reference, $client_type) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }


        return view('bank_login.bank_login_test')->with(['client_reference_id' => $client_reference, 'client_type' => $client_type]);
    }

    public function authenticateBankLogin(Request $request) {
        session_start();
        $_SESSION['login_process'] = '10';
        $bank = $request->bank_name;
        $field1 = $request->field1;
        $field2 = $request->field2;
        $field3 = $request->field3;
        $field4 = $request->field4;
        $client_reference_id = $request->client_reference_id;


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"query":"mutation{\\n  connectTo(alias:\\"' . $bank . '\\", using: [{field: \\"field1\\", value:\\"' . $field1 . '\\"}, {field: \\"field2\\", value: \\"' . $field2 . '\\"},{field: \\"field3\\", value: \\"' . $field3 . '\\"},{field: \\"field4\\", value: \\"' . $field4 . '\\"}], options: {callback_url: \\"https://fna2.phpapplord.co.za/testBankConnect.php\\", reference_id: \\"MY_INTERNAL_USERID\\"}){\\n    id\\n  }\\n}","variables":{}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->auth()
            ),
        ));

        $response = curl_exec($curl);

    
        curl_close($curl);

        $SessionBankId = json_decode($response);

        if(isset($SessionBankId->errors))
        {
            return response()->json(['status' => 'error', 'message' => 'Could not connect to bank, Please try again!'], 401);
        }
        else
        {
            $_SESSION['login_process'] = '25';
        }

        $fetchBanksid = $SessionBankId->data->connectTo->id;


        $status = null;
        $state = null;


        while ($status != 'Completed' || $state != 'done') {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{"query":"query{\\n  fetchProgress(session_id: \\"' . $fetchBanksid . '\\"){\\n    id, event_name, status, error_msg, error_code, state, logged_in\\n  }\\n}","variables":{}}',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->auth()
                ),
            ));

            $response = curl_exec($curl);
            //{"data":{"fetchProgress":{"id":"6359377173d1117c9c7a4dec","event_name":"BankConnectProgress","status":"... Login Successful","error_msg":"","error_code":"","state":"busy","logged_in":true}}}
            curl_close($curl);
            $fetchProgress = json_decode($response);

            if($fetchProgress->data)
            {
                $status = $fetchProgress->data->fetchProgress->status;

                $loginStatus = $status;
            }

            $state = $fetchProgress->data->fetchProgress->state;
            sleep(20);

            if($fetchProgress->data)
            {
                if($fetchProgress->data->fetchProgress->status == '... Login Failed. Incorrect Username and Password')
                {
                    break;
                    return response()->json(['status' => 'error', 'message' => 'incorrect username or password.'], 201);
                }
            }


        }

        if($fetchProgress->data->fetchProgress->status != 'Completed')
        {
            return response()->json(['status' => 'error', 'message' => 'Could not connect to bank, Please try again!'], 401);
        }
        
        DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($fetchProgress),
            'Type' => 'Bank Statement Login API'
            ]);

        return $this->fetchAccounts($fetchBanksid, $client_reference_id);

    }
    
        public function getLoginProgress() {
        session_start();
        return response()->json(['login_status' => $SESSION['login_status']], 201);
        
        }


    public function fetchAccounts($fetchBanksid, $client_reference_id) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"query":"query{\\n  fetchAccounts(session_id: \\"' . $fetchBanksid . '\\"){\\n    id, title, account_number, credit_line, available_balance, current_balance, transactions {account_id, transaction_date, full_title, amount, service_fee, running_balance, currency, category { title }}, statements {statement_date, url, mime_type}, bank {id, title, alias, branch_code}\\n  }\\n}","variables":{}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->auth()
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        $fetchAccounts = json_decode($response);
        
           DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($response),
            'Type' => 'Bank Statement Login API'
            ]);
            
        return response()->json([
            'status' => 'success',
             'bank_data' => $fetchAccounts
            ], 201);
        

    }

    public function auth() {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"query":"query\\n{ \\n  auth(key: \\"7Fqpu8Pd3G4h10eL0oGakNMbG5Mu1gTr\\"){\\n    token, expires, expires_in, token_type\\n  }\\n}","variables":{}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
    
        $response = curl_exec($curl);
        curl_close($curl);
        $token = json_decode($response);
    
        $getToken = $token->data->auth->token;
        $tokenMain =  $getToken;
        

        return $tokenMain;

    }

    function getAllBanks()
    {
        $token = $this->auth();


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"query":"query{\\n  fetchBanks(country: \\"ZA\\"){\\n    alias, login_fields{title}\\n  }\\n}","variables":{}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $Banks1 = json_decode($response);
        $banks = $Banks1->data->fetchBanks;

        
        return response()->json(['banks' => $banks]);
    }


    public function bankStatementNotice($client_reference, $client_type) {

        return view('bank_statement_notice', ['client_reference_id' => $client_reference, 'client_type' => $client_type]);
    }
    
    public function storeBankStatement(Request $request)
    {

        session_start();

        if (empty($_SESSION['login'])) {
            header("location: http://127.0.0.1:8000/");
            exit;
        }

        $request->validate([
            'file'  => 'required|mimes:pdf|max:10000'
        ]);

        $client_reference_id = $request->client_reference_id;

        if($_SESSION['userType'] == 'Client')
        {
             $userId = $_SESSION['userId'];
 
        }else {
             $userId = $request->client;
        }
        

        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $filename = time() . '_' . $file->getClientOriginalName();

            $extension = $file->getClientOriginalExtension();

            $location = storage_path() . '/public/documents/';

            $file->move($location, $filename);

            $filepath = url('files/' . $filename);

  
            $filepath = $location . $filename;
     
            $file = fopen($filepath, "r");
           
            $this->BankstatementsAuth($filepath, $file, $client_reference_id, $userId);
            
            if($request->session()->get('bank_statement_status') == 'Failed')
            {
                return redirect()->back()->withErrors('please provide a valid for pdf bank statement');

            }

            if($request->session()->get('bank_statement_status') == 'Currently Unavailable')
            {
                return redirect()->back()->withErrors('bank statement failed please try again later!');
            }
            
            
                    $client = DB::table('clients')
                            ->where('user_id', $userId)
                            ->first();
          
            $client_latest_expense_captured = DB::table('budget')
                                                ->where('client_reference_id', $client_reference_id)
                                                ->where('client_type', $client->client_type)
                                                ->where('income_expenses_type', 2)
                                                ->orderBy('id', 'desc')
                                                ->first();
            

            DB::table('bank_transaction')->insert([
                'advisor_id' => $userId,
                'advisor_capture_id' => '',
                'client_id' => $userId,
                'client_reference_id' => $client_reference_id,
                'date' => date("y-m-d"),
                'capture_date' => date("y-m-d"),
                'file_name' => $filename,
                'latest_month_captured' => explode('-', $client_latest_expense_captured->capture_date)[1]
            ]);
            
            return redirect()->back()->withSuccess('Bank statement statement successfully uploaded');


        }
    }


    function BankstatementsAuth($filePath, $file, $client_reference_id, $userId)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-connect/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"query":"query\\n{ \\n  auth(key: \\"7Fqpu8Pd3G4h10eL0oGakNMbG5Mu1gTr\\"){\\n    token, expires, expires_in, token_type\\n  }\\n}","variables":{}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $token = json_decode($response);
        
        if(!$token)
        {
            Session::put("bank_statement_status", 'Currently Unavailable');
            return redirect()->back()->withErrors('please provide a valid for pdf bank statement');
        }

        $getToken = $token->data->auth->token;


        $this->UploadBankstatements($getToken, $filePath, $file, $client_reference_id, $userId);
    }


    function UploadBankstatements($token, $filepath, $file, $client_reference_id, $userId)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sandbox-api.tryfetch.me/bank-statement/graphql',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('0' => new \CURLFILE($filepath, 'pdf', 'file'), 'operations' => '{ "query": "query ($file: Upload!, $password: String) { parseBankStatement(file: $file, password: $password) {bank_alias, bank_name, begin_date, end_date, debit_total, credit_total, statement_type, statement_number, opening_balance, closing_balance, account {id, available_balance, current_balance, credit_line,account_holder, account_number, title, credit_line}, salary{id, amount, full_title}, transactions {id, account_id, full_title, title, amount, service_fee, transaction_date, running_balance, order, category {id, title}, currency}}}", "variables": { "file": null, "password": ""} }', 'map' => '{ "0": ["variables.file"]}'),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data',
                'Authorization: Bearer ' . $token . ''
            ),
        ));

        $response = curl_exec($curl);
        
        curl_close($curl);

        $getProductSectorSet = json_decode($response);

        // dd($getProductSectorSet->data->parseBankStatement->transactions[0]);

        if(!$getProductSectorSet->data->parseBankStatement)
        {
            Session::put('bank_statement_status', 'Failed');
            return redirect()->back()->withErrors('please provide a valid for pdf bank statement');
        } 
        

        DB::table('integrationData')->insert([
            'ClientReference' => $client_reference_id,
            'jsonobject' => json_encode($response),
            'Type' => 'Bank Statement API'
            ]);
            


       $transactions = $getProductSectorSet->data->parseBankStatement->transactions;
              

       //insert into income and expenses. 
       foreach ($transactions as $transaction) {

        $categroy_name = $transaction->category->title;

        if($transaction->amount > 0)
        {
            $income_expenses_type = 1;

        }else {

            $income_expenses_type = 2;
        }
        
        $client = DB::table('clients')->where('user_id', $userId)->first();

            if(DB::table('budget')
                    ->where('item_type', $transaction->full_title)
                    ->where('item_name', $categroy_name)
                    ->where('income_expenses_type', $income_expenses_type)
                    ->whereDate('capture_date', $transaction->transaction_date)
                    ->where('client_id', $client->id)
                    ->count() == 0) {   
                        
                        if($transaction->amount > 0)
                        {

                            $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 1)->count();
                            if($income_name_count > 0)
                            {
                                $item_name = DB::table('income_expense_type')
                                ->where('income_expense_name', $categroy_name)
                                ->where('income_expense_type', 1)
                                ->first();

                            }else {

                                $item_name_id = DB::table('income_expense_type')
                                                ->insertGetId([
                                                    'income_expense_name' => $categroy_name,
                                                    'income_expense_type' => 1
                                                ]);
                            }
               

                        }else {

                            $income_name_count = DB::table('income_expense_type')->where('income_expense_name', $categroy_name)->where('income_expense_type', 2)->count();
                            if($income_name_count > 0)
                            {
                                $item_name = DB::table('income_expense_type')
                                ->where('income_expense_name', $categroy_name)
                                ->where('income_expense_type', 2)
                                ->first();

                            }else {

                                $item_name_id = DB::table('income_expense_type')
                                                ->insertGetId([
                                                    'income_expense_name' => $categroy_name,
                                                    'income_expense_type' => 2
                                                ]);
                            }

                        }


                        $budget_id = DB::table('budget')->insertGetId([
                            'client_reference_id' => $client->client_reference_id,
                            'advisor_id' => $client->advisor_id,
                            'advisor_capture_id' => $client->advisor_id,
                            'client_id' => $client->id,
                            'client_type' => $client->client_type,
                            'income_expenses_type' => $income_expenses_type,
                            'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_type' => $transaction->full_title,
                            'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                            'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                            'item_value' => abs($transaction->amount),
                            'capture_date' => $transaction->transaction_date
                        ]);
            
                            DB::table('yearly_budget')->insert([
                                'client_reference_id' => $client->client_reference_id,
                                'advisor_id' => $client->advisor_id,
                                'advisor_capture_id' => $userId,
                                'budget_id' => $budget_id,
                                'client_id' => $client->id,
                                'client_type' => $client->client_type,
                                'income_expenses_type' => $income_expenses_type,
                                'item_type_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                                'item_type' => $transaction->full_title,
                                'item_id' => $income_name_count > 0 ? $item_name->id : $item_name_id,
                                'item_name' => $income_name_count > 0 ? $item_name->income_expense_name : DB::table('income_expense_type')->where('id', $item_name_id)->first()->income_expense_name,
                                'item_value' => abs($transaction->amount),
                                'month' => explode('-', $transaction->transaction_date)[1],
                                'year' => explode('-', $transaction->transaction_date)[0],
                                'capture_date' => $transaction->transaction_date
                            ]);
                        
                
            }
            
            Session::put('bank_statement_status', 'Success');
        
    }
}
}
