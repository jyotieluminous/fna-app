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

class IncomesExpensesController extends Controller
{

    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }


    public function createIncomeExpense(Request $request, $client_reference_id)
    {
       
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        /*Below Condition will check whether the user has Income and expenses or not.
        If yes, he/she will be redirected to update screen.
        Otherwise same screen will come*/
        $total_count = DB::table('budget')
                        ->where('client_reference_id', $client_reference_id)
                        ->count();
                        // dd($total_count);
        if($total_count > 0) {
            return \Redirect::route('fetchIncomeExpense', [ $client_reference_id ]);
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            //var_dump($getroleId[0]->groupId); die();
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
                // echo $getretirementRiskAclAccess; die();
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }


            $getRiskModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $riskModuleModuleId[0]->id . "'");
            if (!isset($getRiskModuleAclAccessId[0]->accessId)) {
                $getRiskModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $getRiskModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getRiskModuleAclAccessId[0]->accessId . "'");
                if (!isset($getRiskModuleAccessName[0]->name)) {
                    $getRiskModuleModuleIdAclAccess = "noAccess";
                } else {
                    $getRiskModuleModuleIdAclAccess = "Access";
                }
            }



            $dependantsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $dependantsModuleModuleId[0]->id . "'");
            if (!isset($dependantsModuleAclAccessId[0]->accessId)) {
                $dependantsModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $dependantsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$dependantsModuleAclAccessId[0]->accessId . "'");
                if (!isset($dependantsModuleAccessName[0]->name)) {
                    $dependantsModuleModuleIdAclAccess = "noAccess";
                } else {
                    $dependantsModuleModuleIdAclAccess = "Access";
                }
            }



            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }



            $liabilitiesModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $liabilitiesModuleModuleId[0]->id . "'");
            if (!isset($liabilitiesModuleAclAccessId[0]->accessId)) {
                $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $liabilitiesModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$liabilitiesModuleAclAccessId[0]->accessId . "'");
                if (!isset($liabilitiesModuleAccessName[0]->name)) {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($liabilitiesModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            $personalInfoModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($personalInfoModuleAclAccessId[0]->accessId)) {
                $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $personalInfoModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalInfoModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalInfoModuleAccessName[0]->name)) {
                    $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    if ($personalInfoModuleAccessName[0]->name == "no-access") {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    } else {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }
            }
        }
        $Module = 'Liabilities';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $roleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "' ");
        $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $roleId[0]->groupId . "' and moduleId = '" . $getModuleId[0]->id . "'");
        //var_dump($getAclAccessId); die();
        if (!isset($getAclAccessId[0]->accessId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        }
        $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
        if (!isset($getAccessName[0]->name)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            if ($getAccessName[0]->name == "no-access") {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
        }

        $income = DB::select("SELECT * FROM `personal_budget`");
        $clients = DB::select("SELECT * FROM `clients` WHERE client_reference_id = '" . $client_reference_id . "' ");
    //   dd($clients);
        $income_types = DB::table('income_expense_type')
                            ->where('income_expense_type','1')
                            ->get();
        $expense_types = DB::table('income_expense_type')
                            ->where('income_expense_type','2')
                            ->get();
                            
        $income_types1 = DB::select("SELECT
                                            *
                                        FROM
                                            `income_expense_type`
                                        LEFT JOIN income_expense_type_items ON income_expense_type.id = income_expense_type_items.income_expense_id
                                        WHERE
                                            income_expense_type = 1
                                        ORDER BY
                                            income_expense_type.id,
                                            income_expense_type_items.item_name");
        // dd($income_types1);
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => 'Income and Expense Module',
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expense Module Create Page",
            'date' => DB::raw('now()')
        ]); 

        return view('fna.createIncomeExpense', ['client_reference_id'=>$client_reference_id,'income_types1' => $income_types1, 'clients' => $clients, 'income_types' => $income_types, 'expense_types' => $expense_types, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'getroleId' => $getroleId, 'income' => $income, 'getAccessName' => $getAccessName, 'getAccessName' => $getAccessName, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }

    public function storeIncomeExpense(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $userId = $_SESSION['userId'];
        // $values = $request->all()->except(['_token']);
        // echo "<pre>";
        // print_r($request->all());
        // echo "</pre>";
        // $del = DB::select("Delete from `budget` Where client_reference_id = '".$_POST['client_reference_id']."'");
        // $del1 = DB::select('Truncate table `yearly_budget`');

        $currentYear = date("Y");
        //income of client
        if (isset($_POST['income_types'])) {
            $incomeTypesArr = count($_POST['income_types']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {

                $arrIncomeNames = explode('_', $_POST['income_names'][$i]);
                $id = $arrIncomeNames[0];
                $nameIncome = $arrIncomeNames[1];
                
                $arrIncomeTypes = explode('_', $_POST['income_types'][$i]);
                $type_id = $arrIncomeTypes[0];
                $type_name = $arrIncomeTypes[1];
                
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['client_id'],
                    'client_type' => $_POST['client_type'],
                    'income_expenses_type' => $_POST['income_expense_types'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,
                    'item_id' => $id,
                    'item_name' => $nameIncome,
                    'item_value' => ($_POST['income_amounts'][$i]),
                    'capture_date' => Date::now()
                ]);

                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['client_id'],
                        'client_type' => $_POST['client_type'],
                        'income_expenses_type' => $_POST['income_expense_types'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,
                        'item_id' => $id,
                        'item_name' => $nameIncome,
                        'item_value' => $_POST['income_amounts'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        
        //income of SPOUSE client
        if (isset($_POST['income_types_spouse'])) {
            $incomeTypesArr = count($_POST['income_types_spouse']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                $arrIncomeNames = explode('_', $_POST['income_names_spouse'][$i]);
                $id = $arrIncomeNames[0];
                $nameIncome = $arrIncomeNames[1];
                
                $arrIncomeTypes = explode('_', $_POST['income_types_spouse'][$i]);
                $type_id = $arrIncomeTypes[0];
                $type_name = $arrIncomeTypes[1];
                

                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['spouse_client_id'],
                    'client_type' => $_POST['spouse_client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_spouse'][$i],
                    // 'item_type' => $_POST['income_types_spouse'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                     
                    'item_id' => $id,
                    'item_name' => $nameIncome,
                    'item_value' => ($_POST['income_amounts_spouse'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['spouse_client_id'],
                        'client_type' => $_POST['spouse_client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_spouse'][$i],
                        // 'item_type' => $_POST['income_types_spouse'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                         
                        'item_id' => $id,
                        'item_name' => $nameIncome,
                        'item_value' => $_POST['income_amounts_spouse'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        

        //Expenses of client
        if (isset($_POST['expense_types'])) {
            $incomeTypesArr = count($_POST['expense_types']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                
                $arrExpenseTypes = explode('_', $_POST['expense_types'][$i]);
                $type_id = $arrExpenseTypes[0];
                $type_name = $arrExpenseTypes[1];
                // print_r($arrExpenseTypes);
                
                $arrExpenseNames = explode('_', $_POST['expense_names'][$i]);
                $id = $arrExpenseNames[0];
                $expenseName = $arrExpenseNames[1];
                
                // print_r($arrExpenseNames);
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['client_id'],
                    'client_type' => $_POST['client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_main'][$i],
                    // 'item_type' => $_POST['expense_types'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                    
                    'item_id' => $id,
                    'item_name' => $expenseName,
                    'item_value' => ($_POST['expense_amounts'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['client_id'],
                        'client_type' => $_POST['client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_main'][$i],
                        // 'item_type' => $_POST['expense_types'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                        
                        'item_id' => $id,
                        'item_name' => $expenseName,
                        'item_value' => $_POST['expense_amounts'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        // dd($_POST);
        
        //Expenses of SPOUSE client
        if (isset($_POST['expense_types_spouse'])) {
            $incomeTypesArr = count($_POST['expense_types_spouse']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                
                $arrExpenseTypes = explode('_', $_POST['expense_types_spouse'][$i]);
                $type_id = $arrExpenseTypes[0];
                $type_name = $arrExpenseTypes[1];
                
                $arrExpenseNames = explode('_', $_POST['expense_names_spouse'][$i]);
                $id = $arrExpenseNames[0];
                $expenseName = $arrExpenseNames[1];
                
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['spouse_client_id'],
                    'client_type' => $_POST['spouse_client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_spouse1'][$i],
                    // 'item_type' => $_POST['expense_types_spouse'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                     
                    'item_id' => $id,
                    'item_name' => $expenseName,
                    'item_value' => ($_POST['expense_amounts_spouse'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['spouse_client_id'],
                        'client_type' => $_POST['spouse_client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_spouse1'][$i],
                        // 'item_type' => $_POST['expense_types_spouse'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                         
                        'item_id' => $id,
                        'item_name' => $expenseName,
                        'item_value' => $_POST['expense_amounts_spouse'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => 'Income and Expense Module',
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expense Module Store Page",
            'date' => DB::raw('now()')
        ]); 
        //  dd($_POST);        
        return \Redirect::route('cashflow', ['client_reference_id' => $_POST['client_reference_id']]);
    }

    public function index(Request $request, $id, $client_reference_id, $type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';

        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");

        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        //var_dump($getroleId); die();
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($getAclAccessId[0]->accessId)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
            if (!isset($getAccessName[0]->name)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            } else {
                if ($getAccessName[0]->name == "no-access") {
                    header("location: https://fna2.phpapplord.co.za/public/noAccess");
                    exit;
                }
            }
        }

        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]);

        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '" . $client_reference_id . "'");
        return view('incomeExpense.incomeExpense', [
            'getAccessName' => $getAccessName,
            'client_owners' => $client_owners,
            'id' => $id,
            'client_reference_id' => $client_reference_id,
            'type' => $type
        ]);
    }

    public function store(Request $request)
    {
        /*
        <tr>
            <th>
                Income Type
            </th>
            <th>
                Income Name
            </th>
            <th>
                Income Value
            </th>
            
        </tr>
        <tr>
            <td>
                
                <select name="income_type_main_client[]">
                foreach($income_types as $income_type)
                {
                    <option value="$income_type->id">$income_type->name</option>
                }
                </select>
            </td>
            <td>
                <input type="text" name="income_name_main_client[]">
            </td>
            <td>
                <input type="number" name="income_value_main_client[]">
            </td>
            
        </tr>
        $count = count($_POST["income_type"]);
        for($i = 0; $i < $count; $i++)
        	{
        	use the insert that gives you back Id
        DB::select("INSERT into budget values (
        			null,
        			'".$_POST['client_reference_id']."',
        			$user_id,
        			$user_id,
        			$client_id,
        			'1',
        			'".$_POST['income_type_main_client_main_client'][$i]."', 
        			'".$_POST['income_name_main_client'][$i]."', 
        			'".$_POST['income_value_main_client'][$i]."',
        			NOW()
        			)");
        	}
        	for($j =1; $j <= 12; $j++)
    	{
        	for($i = 0; $i < $count; $i++)
        	{
        DB::select("INSERT into yearly_budget values (
        			null,
        			'".$_POST['client_reference_id']."',
        			$user_id,
        			$user_id,
        			'1',
        			'".$_POST['income_type'][$i]."', 
        			'".$_POST['income_name'][$i]."', 
        			'".$_POST['income_value'][$i]."',
        			'$j',
        			'".$currentYear."',
        			NOW()
        			)");
        	}
    	}
        */
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $incomeExpensesModule = 'IncomeExpensesModule';
        $currentYear = date("Y");
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);


        $income_expense_id = DB::table('income_expense_capture')->insertGetId(
            [
                'id' => null,
                'client_id' => $_POST['client_id'],
                'advisor_id' => $userId,
                'capture_advisor_id' => '1',
                'client_reference_id' => $_POST['client_reference_id'],
                'capture_date' => date('Y-m-d'),
                'updated_date' => NOW()
            ]
        );

        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses Store page, $userId created id=$income_expense_id row",
            'date' => DB::raw('now()')
        ]);
        $count = count($_POST["income_item_type"]);
        for ($j = 1; $j <= 12; $j++) {
            // echo $j . 'in loop = $j';
            for ($i = 0; $i < $count; $i++) {
                DB::select("INSERT into income_expense_yearly values (
        			null,
        			$income_expense_id,
        			'" . $_POST['client_reference_id'] . "', 
        			'" . $_POST['income_item_type'][$i] . "', 
        			'" . $_POST['income_type'][$i] . "', 
        			'" . $_POST['income_name'][$i] . "', 
        			'" . $_POST['income_value'][$i] . "',
        			'$j',
        			'" . $currentYear . "',
        			NOW()
        			)");
            }
        }

        $count = count($_POST["income_item_type"]);
        for ($j = 1; $j <= 12; $j++) {
            for ($i = 0; $i < $count; $i++) {
                DB::select("INSERT into income_expense_yearly values (
        			null, 
            		$income_expense_id,
            		'" . $_POST['client_reference_id'] . "', 
        			'" . $_POST['expenses_item_type'][$i] . "', 
        			'" . $_POST['expenses_type'][$i] . "', 
        			'" . $_POST['expenses_name'][$i] . "', 
        			'" . $_POST['expenses_value'][$i] . "', 
        			'$j',
        			'" . $currentYear . "',
        			NOW()
        			)");
            }
        }
        return redirect()->route('listIncomeExpense');
    }

    public function listIncomesExpenses()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';

        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");

        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        //var_dump($getroleId); die();
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($getAclAccessId[0]->accessId)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
            if (!isset($getAccessName[0]->name)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            } else {
                if ($getAccessName[0]->name == "no-access") {
                    header("location: https://fna2.phpapplord.co.za/public/noAccess");
                    exit;
                }
            }
        }

        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        /*DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses List page",
            'date' => DB::raw('now()')
        ]);
        */
        $count = 1;
        $listIncomeExpenseTotal = array();
        $getClients = DB::select("select * from clients where client_type = 'Main Client'");
        foreach ($getClients as $client_info) {
            //var_dump($client_info); die();
            $listIncomeExpenseTotal[$count]['first_name'] = $client_info->first_name;
            $listIncomeExpenseTotal[$count]['last_name'] = $client_info->last_name;
            $listIncomeExpenseTotal[$count]['client_reference_id'] = $client_info->client_reference_id;
            $listIncomeExpenseTotal[$count]['id'] = $client_info->id;
            $getbudgetIncome = DB::select("SELECT * FROM `budget` where client_reference_id = '" . $client_info->client_reference_id . "' and income_expenses_type = 1");
            if (!empty($getbudgetIncome)) {
                $getbudgetIncome = DB::select("SELECT SUM(item_value) as income_total  FROM `budget` where client_reference_id = '" . $client_info->client_reference_id . "' and income_expenses_type = 1");
                $listIncomeExpenseTotal[$count]['incomeTotal'] = $getbudgetIncome[0]->income_total;
                $listIncomeExpenseTotal[$count]['incomeTotalyear'] = $getbudgetIncome[0]->income_total * 12;
            } else {
                $listIncomeExpenseTotal[$count]['incomeTotal'] = 0.00;
                $listIncomeExpenseTotal[$count]['incomeTotalyear'] = 0.00;
            }
            $getbudgetExpense = DB::select("SELECT * FROM `budget` where client_reference_id = '" . $client_info->client_reference_id . "' and income_expenses_type = 2");
            if (!empty($getbudgetExpense)) {
                $getbudgetExpense = DB::select("SELECT SUM(item_value) as expense_total FROM `budget` where client_reference_id = '" . $client_info->client_reference_id . "' and income_expenses_type = 2");
                $listIncomeExpenseTotal[$count]['expenseTotal'] = $getbudgetExpense[0]->expense_total;
                $listIncomeExpenseTotal[$count]['expenseTotalyear'] = $getbudgetExpense[0]->expense_total * 12;
            } else {
                $listIncomeExpenseTotal[$count]['expenseTotal'] = 0.00;
                $listIncomeExpenseTotal[$count]['expenseTotalyear'] = 0.00;
            }
            //echo $listIncomeExpenseTotal[$count]['expenseTotal']; die();
            $listIncomeExpenseTotal[$count]['monthlydiff'] = $listIncomeExpenseTotal[$count]['incomeTotal'] - $listIncomeExpenseTotal[$count]['expenseTotal'];
            $count++;
        }
        //print_r("<pre>"); var_dump($listIncomeExpenseTotal); die();
        return view('budget.listIncomeExpense', ['getAccessName' => $getAccessName, 'budget' => $listIncomeExpenseTotal]);
    }

    public function viewIncomeExpense(Request $request, $id, $client_reference_id, $type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';

        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");

        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        //var_dump($getroleId); die();
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($getAclAccessId[0]->accessId)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
            if (!isset($getAccessName[0]->name)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            } else {
                if ($getAccessName[0]->name == "no-access") {
                    header("location: https://fna2.phpapplord.co.za/public/noAccess");
                    exit;
                }
            }
        }
        $incomeExpensesModule = 'IncomeExpensesModule';

        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $incomeExpensesModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expenses View page",
            'date' => DB::raw('now()')
        ]);
        $income_list = DB::select("SELECT MONTHNAME(concat(year,'-',month,'-',0)) as month_name, income_expense_yearly.* FROM `income_expense_capture` INNER JOIN income_expense_yearly ON income_expense_capture.id=income_expense_yearly.income_expense_capture_id where income_expense_capture.client_reference_id ='$client_reference_id' and income_expense_yearly.income_expenses_type = '1'");
        $expense_list = DB::select("SELECT MONTHNAME(concat(year,'-',month,'-',0)) as month_name, income_expense_yearly.* FROM `income_expense_capture` INNER JOIN income_expense_yearly ON income_expense_capture.id=income_expense_yearly.income_expense_capture_id where income_expense_capture.client_reference_id ='$client_reference_id' and income_expense_yearly.income_expenses_type = '2'");
        return view('incomeExpense.viewIncomeExpense', [
            'getAccessName' => $getAccessName,
            'income_list' => $income_list,
            'expense_list' => $expense_list,
            'id' => $id,
            'client_reference_id' => $client_reference_id,
            'type' => $type
        ]);
    }

    public function overview($client_reference_id, $client_type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $clientData = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', $client_type)
            ->first();

        $data['client_id'] = $clientData->id;
        $data['first_name'] = $clientData->first_name;
        $data['last_name'] = $clientData->last_name;
        $data['client_type'] = $clientData->client_type;


        $total_assets = DB::table('client_assets_liabilities')
            ->where('asset_liability_type', 1)
            ->where('client_reference_id', $client_reference_id)
            ->pluck('item_value')
            ->sum();

        $total_liabilities = DB::table('client_assets_liabilities')
            ->where('asset_liability_type', 2)
            ->where('client_reference_id', $client_reference_id)
            ->pluck('item_value')
            ->sum();

        $data['total_assets'] = $total_assets;
        $data['total_liabilities'] = $total_liabilities;
        $data['total_worth'] = $total_assets - $total_liabilities;

        $spouse = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', '!=', $client_type)
            ->first();

        $data['spouse_id'] = $spouse->id;
        return view('fna.overview', ['clientData' => $data, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type]);
    }

    public function fetchSingleIncome(Request $request, $client_reference_id, $id)
    {
        echo $client_reference_id . '===' . $id;
        $income_list = DB::table('income_expense_yearly')
            ->where('client_reference_id', $client_reference_id)
            ->where('id', $id)
            ->first();

        dd($income_list);
    }

    public function deleteSingleIncome()
    {
        $income_list = DB::table('income_expense_yearly')
            ->where('client_reference_id', $client_reference_id)
            ->where('id', $id)
            ->first();
        dd('fetchSingleIncome');
    }

    public function fetchSingleExpense()
    {
        dd('fetchSingleIncome');
    }
    public function deleteSingleExpense()
    {
        dd('fetchSingleIncome');
    }
   
    public function listIncomesExpensesDetails($client_reference_id, $id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $liabilitiesModule = 'Liabilities';
        $personalInfoModule = 'Personal Information';

        // $liabilitiesModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$liabilitiesModule'");
        $personalInfoModuleModuleId = DB::select("SELECT * FROM `modules` where name = '$personalInfoModule'");

        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        //var_dump($getroleId); die();
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                    Get Access Id to get Read/write access or Access Name
                */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($getAclAccessId[0]->accessId)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
            $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
            if (!isset($getAccessName[0]->name)) {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            } else {
                if ($getAccessName[0]->name == "no-access") {
                    header("location: https://fna2.phpapplord.co.za/public/noAccess");
                    exit;
                }
            }
        }

        return view('budget.listIncomesExpensesDetails', ['getAccessName' => $getAccessName]);
    }

    public function fetchIncomeExpense(Request $request, $client_reference_id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            //var_dump($getroleId[0]->groupId); die();
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
                // echo $getretirementRiskAclAccess; die();
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }


            $getRiskModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $riskModuleModuleId[0]->id . "'");
            if (!isset($getRiskModuleAclAccessId[0]->accessId)) {
                $getRiskModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $getRiskModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getRiskModuleAclAccessId[0]->accessId . "'");
                if (!isset($getRiskModuleAccessName[0]->name)) {
                    $getRiskModuleModuleIdAclAccess = "noAccess";
                } else {
                    $getRiskModuleModuleIdAclAccess = "Access";
                }
            }



            $dependantsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $dependantsModuleModuleId[0]->id . "'");
            if (!isset($dependantsModuleAclAccessId[0]->accessId)) {
                $dependantsModuleModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $dependantsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$dependantsModuleAclAccessId[0]->accessId . "'");
                if (!isset($dependantsModuleAccessName[0]->name)) {
                    $dependantsModuleModuleIdAclAccess = "noAccess";
                } else {
                    $dependantsModuleModuleIdAclAccess = "Access";
                }
            }



            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }



            $liabilitiesModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $liabilitiesModuleModuleId[0]->id . "'");
            if (!isset($liabilitiesModuleAclAccessId[0]->accessId)) {
                $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $liabilitiesModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$liabilitiesModuleAclAccessId[0]->accessId . "'");
                if (!isset($liabilitiesModuleAccessName[0]->name)) {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $liabilitiesModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                //echo $getRiskModuleModuleIdAclAccess; die();
            } else {
                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($liabilitiesModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            $personalInfoModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalInfoModuleModuleId[0]->id . "'");
            if (!isset($personalInfoModuleAclAccessId[0]->accessId)) {
                $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $personalInfoModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalInfoModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalInfoModuleAccessName[0]->name)) {
                    $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    if ($personalInfoModuleAccessName[0]->name == "no-access") {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    } else {
                        $personalInfoModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }
            }
        }
        $Module = 'Liabilities';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $roleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "' ");
        $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $roleId[0]->groupId . "' and moduleId = '" . $getModuleId[0]->id . "'");
        //var_dump($getAclAccessId); die();
        if (!isset($getAclAccessId[0]->accessId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        }
        $getAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getAclAccessId[0]->accessId . "'");
        if (!isset($getAccessName[0]->name)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            if ($getAccessName[0]->name == "no-access") {
                header("location: https://fna2.phpapplord.co.za/public/noAccess");
                exit;
            }
        }

        $getClientRows = DB::table('budget')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', 'Main Client')
                            ->where('income_expenses_type', '1')
                            ->get();
        $getClientExpenses = DB::table('budget')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', 'Main Client')
                            ->where('income_expenses_type', '2')
                            ->get();
        $getSpouseIncomes = DB::table('budget')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', 'Spouse')
                            ->where('income_expenses_type', '1')
                            ->get();
        $getSpouseExpenses = DB::table('budget')
                            ->where('client_reference_id', $client_reference_id)
                            ->where('client_type', 'Spouse')
                            ->where('income_expenses_type', '2')
                            ->get();

        $income = DB::select("SELECT * FROM `personal_budget`");
        $clients = DB::select("SELECT * FROM `clients` WHERE client_reference_id = '" . $client_reference_id . "' ");
        $client_type  = $clients[0]->client_type ?? "Main Client";
        $client_user_id  =  $clients[0]->user_id  ?? "Main Client";
        /*$client_bank_name = DB::select("SELECT * FROM `user_bank` WHERE `user_id` = '".$client_user_id."' ");*/
        $client_bank_name = DB::select("SELECT * FROM `user_bank`");
        $bank_name = $client_bank_name[0]->bank_name  ?? "0";

        $income_types = DB::table('income_expense_type')->where('income_expense_type','1')->get();
        $expense_types = DB::table('income_expense_type')->where('income_expense_type','2')->get();


        $income_names = DB::select('SELECT income_expense_type_items.id, income_expense_type_items.income_expense_id, income_expense_type_items.item_name 
                                    FROM `income_expense_type` 
                                    LEFT JOIN `income_expense_type_items` ON income_expense_type.id = income_expense_type_items.income_expense_id 
                                    WHERE `income_expense_type` = 1  and income_expense_type_items.id is not null
                                    ORDER BY `income_expense_name` DESC');
                                    
        $expense_names = DB::select('SELECT income_expense_type_items.id, income_expense_type_items.income_expense_id, income_expense_type_items.item_name 
                                    FROM `income_expense_type` 
                                    LEFT JOIN `income_expense_type_items` ON income_expense_type.id = income_expense_type_items.income_expense_id 
                                    WHERE `income_expense_type` = 2   and income_expense_type_items.id is not null
                                    ORDER BY `income_expense_name` DESC');
        
        $notes = DB::table('income_expense_notes')->where('user_id', $userId)->where('client_reference_id', $client_reference_id)->first();

        return view('budget.fetchIncomeExpense', [
            'userId' => $userId,
            'notes' => $notes,
            'client_reference_id' => $client_reference_id,
            'expense_names' => $expense_names,
            'income_names' => $income_names,
            'getClientRows' => $getClientRows,
            'getClientExpenses' => $getClientExpenses,
            'getSpouseIncomes' => $getSpouseIncomes,
            'getSpouseExpenses' => $getSpouseExpenses,
            'clients' => $clients,
            'income_types' => $income_types,
            'expense_types' => $expense_types,
            'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess,
            'getroleId' => $getroleId,
            'getAccessName' => $getAccessName,
            'getAccessName' => $getAccessName,
            '$getretirementRiskAclAccess' => $getretirementRiskAclAccess,
            'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess,
            'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess,
            'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess,
            'getretirementRiskAclAccess' => $getretirementRiskAclAccess,
            'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess,
            'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess,
            'client_type'=>$client_type,
            'bank_name'=>$bank_name
        ]);
        // SELECT * FROM `yearly_budget` WHERE `client_reference_id` LIKE 'fna000000000053'
    }
    
     public function cpbCreditScore(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $id_number = $_POST['id_number'];
        $client_type = $_POST['client_type'];
        $client_reference_id = $_POST['client_reference_id'];
        $creditScoreEnquiryData = DB::table('credit_score_enquiry')->insertGetId([
            'advisor_id' => $userId,
            'capture_advisor_id' => $userId,
            'client_reference_id' =>  $client_reference_id,
            'client_type' => $client_type,
            'id_number' => $id_number,
            'account_number' => '00001',
            'amount' => '100',
            'date' => Date::now()
        ]);
        return \Redirect::route('fetchIncomeExpense', [ $client_reference_id ]);
    }

    public function updateIncomeExpense(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $userId = $_SESSION['userId'];
        // $values = $request->all()->except(['_token']);
        // echo "<pre>";
        // print_r($request->all());
        // echo "</pre>";
        // $del = DB::select("Delete from `budget` Where client_reference_id = '".$_POST['client_reference_id']."'");
        // $del1 = DB::select('Truncate table `yearly_budget`');
        $del1 = DB::select("DELETE FROM `yearly_budget` WHERE `client_reference_id` = '" . $_POST['client_reference_id'] . "'");
        $del = DB::select("DELETE FROM `budget` WHERE `client_reference_id` = '" . $_POST['client_reference_id'] . "'");
        $currentYear = date("Y");
        //income of client
        if (isset($_POST['income_types'])) {
            $incomeTypesArr = count($_POST['income_types']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {

                $arrIncomeNames = explode('_', $_POST['income_names'][$i]);
                $id = $arrIncomeNames[0];
                $nameIncome = $arrIncomeNames[1];
                
                $arrIncomeTypes = explode('_', $_POST['income_types'][$i]);
                $type_id = $arrIncomeTypes[0];
                $type_name = $arrIncomeTypes[1];
                
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['client_id'],
                    'client_type' => $_POST['client_type'],
                    'income_expenses_type' => $_POST['income_expense_types'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,
                    'item_id' => $id,
                    'item_name' => $nameIncome,
                    'item_value' => ($_POST['income_amounts'][$i]),
                    'capture_date' => Date::now()
                ]);

                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['client_id'],
                        'client_type' => $_POST['client_type'],
                        'income_expenses_type' => $_POST['income_expense_types'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,
                        'item_id' => $id,
                        'item_name' => $nameIncome,
                        'item_value' => $_POST['income_amounts'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        
        //income of SPOUSE client
        if (isset($_POST['income_types_spouse'])) {
            $incomeTypesArr = count($_POST['income_types_spouse']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                $arrIncomeNames = explode('_', $_POST['income_names_spouse'][$i]);
                $id = $arrIncomeNames[0];
                $nameIncome = $arrIncomeNames[1];
                
                $arrIncomeTypes = explode('_', $_POST['income_types_spouse'][$i]);
                $type_id = $arrIncomeTypes[0];
                $type_name = $arrIncomeTypes[1];
                

                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['spouse_client_id'],
                    'client_type' => $_POST['spouse_client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_spouse'][$i],
                    // 'item_type' => $_POST['income_types_spouse'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                     
                    'item_id' => $id,
                    'item_name' => $nameIncome,
                    'item_value' => ($_POST['income_amounts_spouse'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['spouse_client_id'],
                        'client_type' => $_POST['spouse_client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_spouse'][$i],
                        // 'item_type' => $_POST['income_types_spouse'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                         
                        'item_id' => $id,
                        'item_name' => $nameIncome,
                        'item_value' => $_POST['income_amounts_spouse'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        

        //Expenses of client
        if (isset($_POST['expense_types'])) {
            $incomeTypesArr = count($_POST['expense_types']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                
                $arrExpenseTypes = explode('_', $_POST['expense_types'][$i]);
                $type_id = $arrExpenseTypes[0];
                $type_name = $arrExpenseTypes[1];
                // print_r($arrExpenseTypes);
                
                $arrExpenseNames = explode('_', $_POST['expense_names'][$i]);
                $id = $arrExpenseNames[0];
                $expenseName = $arrExpenseNames[1];
                
                // print_r($arrExpenseNames);
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['client_id'],
                    'client_type' => $_POST['client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_main'][$i],
                    // 'item_type' => $_POST['expense_types'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                    
                    'item_id' => $id,
                    'item_name' => $expenseName,
                    'item_value' => ($_POST['expense_amounts'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['client_id'],
                        'client_type' => $_POST['client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_main'][$i],
                        // 'item_type' => $_POST['expense_types'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                        
                        'item_id' => $id,
                        'item_name' => $expenseName,
                        'item_value' => $_POST['expense_amounts'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        // dd($_POST);
        
        //Expenses of SPOUSE client
        if (isset($_POST['expense_types_spouse'])) {
            $incomeTypesArr = count($_POST['expense_types_spouse']);
            for ($i = 0; $i < ($incomeTypesArr); $i++) {
                
                $arrExpenseTypes = explode('_', $_POST['expense_types_spouse'][$i]);
                $type_id = $arrExpenseTypes[0];
                $type_name = $arrExpenseTypes[1];
                
                $arrExpenseNames = explode('_', $_POST['expense_names_spouse'][$i]);
                $id = $arrExpenseNames[0];
                $expenseName = $arrExpenseNames[1];
                
                $budget_id = DB::table('budget')->insertGetId([
                    'client_reference_id' => $_POST['client_reference_id'],
                    'advisor_id' => $userId,
                    'advisor_capture_id' => $userId,
                    'client_id' => $_POST['spouse_client_id'],
                    'client_type' => $_POST['spouse_client_type'],
                    'income_expenses_type' => $_POST['income_expense_types_spouse1'][$i],
                    // 'item_type' => $_POST['expense_types_spouse'][$i],
                    'item_type_id' => $type_id,
                    'item_type' => $type_name,                     
                    'item_id' => $id,
                    'item_name' => $expenseName,
                    'item_value' => ($_POST['expense_amounts_spouse'][$i]),
                    'capture_date' => Date::now()
                ]);
                for ($j = 0; $j < 12; $j++) {
                    DB::table('yearly_budget')->insert([
                        'client_reference_id' => $_POST['client_reference_id'],
                        'advisor_id' => $userId,
                        'advisor_capture_id' => $userId,
                        'budget_id' => $budget_id,
                        'client_id' => $_POST['spouse_client_id'],
                        'client_type' => $_POST['spouse_client_type'],
                        'income_expenses_type' => $_POST['income_expense_types_spouse1'][$i],
                        // 'item_type' => $_POST['expense_types_spouse'][$i],
                        'item_type_id' => $type_id,
                        'item_type' => $type_name,                         
                        'item_id' => $id,
                        'item_name' => $expenseName,
                        'item_value' => $_POST['expense_amounts_spouse'][$i],
                        'month' => $j + 1,
                        'year' => $currentYear,
                        'capture_date' => Date::now()
                    ]);
                }
            }
        }
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        // dd($userRole);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => 'Income and Expense Module',
            'role' => $userRole[0]->name,
            'action' => "Landed on Income and Expense Module Store Page",
            'date' => DB::raw('now()')
        ]); 
        //  dd($_POST);        
        return \Redirect::route('cashflow', ['client_reference_id' => $_POST['client_reference_id']]);
    }
    
    public function getitemsIncomeAjax(Request $request, $id, $select_name = '') {
        $items = DB::select("SELECT id, item_name FROM `income_expense_type_items` WHERE income_expense_id = '".$id."'");
        $html ='';
        if(!empty($items))
        {
            $html='<select class="form-control budget--select getsubcatincome" name="'.$select_name.'[]">';
            foreach($items as $idata){
                $html.='<option value="'.$idata->id.'_'.$idata->item_name.'">'.$idata->item_name.'</option>';
            }
            $html.='</select>';
        }
        $arr = array('html'=>$html);
        return json_encode($arr);
        //return $items;
    }

}
