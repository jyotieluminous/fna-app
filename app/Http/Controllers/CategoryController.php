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
use Illuminate\Support\Facades\Date;
use DB;

class CategoryController extends Controller
{

    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    
    public function index($client_reference_id,$client_type) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $types = DB::select("SELECT * , CASE WHEN income_expense_type = 1 THEN 'Income' ELSE 'Expense' END as type FROM `income_expense_type` ORDER BY `income_expense_type`.`id` DESC ");
        return view('category.listCategory', ['types' => $types,'client_reference_id'=>$client_reference_id,'client_type'=>$client_type]);
    }
    public function create() {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        return view('category.createCategory');
    }
    public function edit(Request $request, $id) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $types = DB::select("SELECT * , CASE WHEN income_expense_type = 1 THEN 'Income' ELSE 'Expense' END as type FROM `income_expense_type` WHERE id = '".$id."' ");
        // dd($types);
        return view('category.editCategory', [
            'types' => $types,
            'id' => $id
        ]);
    } 
    public function view(Request $request, $id) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }     
        $types = DB::select("SELECT * , CASE WHEN income_expense_type = 1 THEN 'Income' ELSE 'Expense' END as type FROM `income_expense_type` WHERE id = '".$id."' ");
        $types_items = DB::select("SELECT
                                income_expense_name,
                                income_expense_type_items.id as id,
                                income_expense_id,
                                item_name,
                                CASE WHEN income_expense_type = 1 THEN 'Income' ELSE 'Expense'
                            END AS type
                            FROM
                                `income_expense_type`
                            INNER JOIN `income_expense_type_items` ON income_expense_type.id = income_expense_id
                            WHERE
                                income_expense_type.id = '".$id."'
                            ORDER BY
                                `income_expense_type`.`income_expense_name` ASC");
                                // dd($types);
        return view('category.viewCategory', [
            'types' => $types,
            'types_items' => $types_items,
            'id' => $id
        ]);   
    }
    public function update(Request $request) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $request->validate([
                      'income_expense_name' => 'required|unique:income_expense_type,income_expense_name,' . $request->id
                   ],
                   [
                      'income_expense_name.required'=> 'Category name is required.',
                      'income_expense_name.unique'=> 'Category name has already been taken.'
                   ]);
        $affected = DB::table('income_expense_type')
              ->where('id', $_POST['id'])
              ->update([
                  'income_expense_name' => $_POST['income_expense_name'],
                  'income_expense_type' => $_POST['income_types']      
              ]);
        return \Redirect::route('listIncomeExpenseTypes',['client_reference_id'=>$_SESSION['client_reference_id_sent'],'client_type'=>'Main Client']);

    }   
    public function store(Request $request) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }  
        // print_r($_SESSION);
        $request->validate([
                              'income_expense_name' => 'required|unique:income_expense_type,income_expense_name'
                           ],
                           [
                              'income_expense_name.required'=> 'Category name is required.',
                              'income_expense_name.unique'=> 'Category name has already been taken.'
                           ]);
        $affected = DB::table('income_expense_type')
          ->insert([
              'income_expense_name' => $request->income_expense_name,
              'income_expense_type' => $request->income_types     
          ]);
        return \Redirect::route('listIncomeExpenseTypes',['client_reference_id'=>$_SESSION['client_reference_id_sent'],'client_type'=>'Main Client']);
    }
    public function delete() {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $affected = DB::table('income_expense_type')
              ->where('id', $_POST['id'])
              ->delete();
        return \Redirect::route('listIncomeExpenseTypes',['client_reference_id'=>$_SESSION['client_reference_id_sent'],'client_type'=>'Main Client']);
    }  
    public function updateItems() {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }        
        $affected = DB::table('income_expense_type_items')
              ->where('income_expense_id', $_POST['income_expense_id'])
              ->delete();
        if (isset($_POST['item_names'])) {
            $item_names_arr = count($_POST['item_names']);
            if($item_names_arr > 0 ) {            
                for ($i = 0; $i < ($item_names_arr); $i++) {
                    $budget_id = DB::table('income_expense_type_items')->insert([
                        'income_expense_id' => $_POST['income_expense_id'],
                        'item_name' => $_POST['item_names'][$i]
                    ]);
                } 
            }
        }    
        return \Redirect::route('listIncomeExpenseTypes',['client_reference_id'=>$_SESSION['client_reference_id_sent'],'client_type'=>'Main Client']);        
    }
    public function listIncomeExpenseTypesAjax($client_reference_id,$client_type) {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        if ($request->ajax()) {
            $types = DB::select("SELECT * , CASE WHEN income_expense_type = 1 THEN 'Income' ELSE 'Expense' END as type FROM `income_expense_type` ORDER BY `income_expense_type`.`id` DESC ");
                return Datatables::of($clients)
                    ->addIndexColumn()
                    ->make(true);            
        }
        
        return view('category.listCategory', ['types' => $types,'client_reference_id'=>$client_reference_id,'client_type'=>$client_type]);
    }   
}    