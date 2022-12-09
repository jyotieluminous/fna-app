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

class FnaController extends Controller
{
    private $login;
    private $userId;
    private $userType;
    private $roleId;
    public function __construct()
    {
        //$this->middleware('auth');
    }
    public function dashboard()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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


        $personal_details = DB::select("SELECT * FROM `personal_details`");
        //$personal_details = DB::select("SELECT * FROM `clients` where userId = '$userId' ");
        return view('fna.index', ['personal_details' => $personal_details, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }



    public function overview($client_reference_id, $client_type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $_SESSION['client_reference_id_sent'] = $client_reference_id;
        $clientData = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', $client_type)
            ->first();

        $checkSpouseExits = DB::select("SELECT *  FROM `clients` WHERE `client_type` = 'Spouse' AND `client_reference_id` = '" . $client_reference_id . "' ");
        $SpouseDataExits = 'No';
        if (!empty($checkSpouseExits) && sizeof($checkSpouseExits) > 0) {
            $SpouseDataExits = 'Yes';
        }

        $incomeData = DB::select("SELECT SUM(item_value) as income_total FROM `yearly_budget`  WHERE client_reference_id = '" . $client_reference_id . "' AND client_type = '" . $client_type . "' AND income_expenses_type = '1' AND year = '" . date('Y') . "' GROUP BY month");
        $expenseData = DB::select("SELECT SUM(item_value) as expense_total FROM `yearly_budget`  WHERE client_reference_id = '" . $client_reference_id . "' AND client_type = '" . $client_type . "' AND income_expenses_type = '2' AND year = '" . date('Y') . "' GROUP BY month");
        $monthData = DB::select("SELECT month FROM `yearly_budget`  WHERE client_reference_id = '" . $client_reference_id . "' AND client_type = '" . $client_type . "' AND income_expenses_type = '2' AND year = '" . date('Y') . "' GROUP BY month");
        $months = array(0 => 'Jan', 1 => 'Feb', 2 => 'Mar', 3 => 'Apr', 4 => 'May', 5 => 'Jun', 6 => 'Jul', 7 => 'Aug', 8 => 'Sep', 9 => 'Oct', 10 => 'Nov', 11 => 'Dec');
        $monthName = $incomeFinalData = $expenseFinalData = array();

        $reserveFunds = DB::select("SELECT asset_amount FROM `client_assets`  WHERE client_reference_id = '" . $client_reference_id . "' AND client_type = '" . $client_type . "' AND asset_type = '27' ");
        $reserveFundsData = '0';
        if (isset($reserveFunds) && sizeof($reserveFunds) > 0) {
            $reserveFundsData = $reserveFunds[0]->asset_amount;
        }
        $total_liabilities = DB::select("SELECT SUM(outstanding_balance) as total_liabilities_sum  FROM `client_liabilities_new` WHERE `client_reference_id` = '" . $client_reference_id . "'  AND `client_type` = '" . $client_type . "' ");
        $total_cash_depends = DB::select("SELECT SUM(asset_amount) as asset_amount_sum  FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `asset_type` = '7'");

        if (!empty($total_liabilities) && sizeof($total_liabilities) > 0) {
            $total_liabilities_sum = isset($total_liabilities[0]->total_liabilities_sum) ? $total_liabilities[0]->total_liabilities_sum : "0";
        }
        if (!empty($total_cash_depends) && sizeof($total_cash_depends) > 0) {
            $total_cash_depends_sum = isset($total_cash_depends[0]->asset_amount_sum) ? $total_cash_depends[0]->asset_amount_sum : "0";
        }

        $my_cashflow_needs = ($total_liabilities_sum + $total_cash_depends_sum);
        $reserveFundsDataMonths = '';
        if (isset($reserveFunds) && sizeof($reserveFunds) > 0) {
            $reserveFundsDataMonths = (int) ($reserveFundsData / $my_cashflow_needs);
        }
        if (isset($monthData)) {
            foreach ($monthData as $key => $value) {
                if (array_key_exists($key, $months)) {
                    array_push($monthName, "'" . $months[$key] . "'");
                }
            }
        }
        if (isset($incomeData)) {
            foreach ($incomeData as $imData) {
                array_push($incomeFinalData, (int)$imData->income_total);
            }
        }
        if (isset($expenseData)) {
            foreach ($expenseData as $eFData) {
                array_push($expenseFinalData, "-" . (int)$eFData->expense_total);
            }
        }

        $incomeFinalData = implode(",", $incomeFinalData);
        $expenseFinalData = implode(",", $expenseFinalData);
        $monthFinalName =  implode(",", $monthName);

        $data['client_id'] = $clientData->id;
        $data['first_name'] = $clientData->first_name;
        $data['last_name'] = $clientData->last_name;
        $data['client_type'] = $clientData->client_type;


        $total_assets = DB::table('client_assets')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', '=', $client_type)
            ->pluck('asset_amount')
            ->sum();
        // print_r($total_assets);
        $total_liabilities = DB::table('client_liabilities_new')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', '=', $client_type)
            ->pluck('outstanding_balance')
            ->sum();

        // print_r($total_liabilities);
        $data['total_assets'] = $total_assets;
        $data['total_liabilities'] = $total_liabilities;
        $data['total_worth'] = $total_assets - $total_liabilities;

        /*New Legacy Data*/
        $client_income = $total_liabilities_sum =  $total_cash_depends_sum = $my_cashflow_needs = $shortfall_death = $lump_sum_needed = 0;
        $current_assets_insurance = $emergencyfund_sum = $current_insurance_sum_final =  $my_asset_needs_sum_final = 0;
        $current_client_income = DB::select("SELECT SUM(item_value) as client_sum FROM `budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `income_expenses_type` = 1");
        $total_liabilities = DB::select("SELECT SUM(outstanding_balance) as total_liabilities_sum  FROM `client_liabilities_new` WHERE `client_reference_id` = '" . $client_reference_id . "'  AND `client_type` = '" . $client_type . "' ");
        $total_cash_depends = DB::select("SELECT SUM(asset_amount) as asset_amount_sum  FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `asset_type` = '7'");
        $total_asset_amount = DB::select("SELECT COALESCE(SUM(life_cover_cash_value),0) + COALESCE(SUM(life_cover_death),0) + COALESCE(SUM(life_cover_disability),0) + COALESCE(SUM(life_cover_dread_disease),0) + COALESCE(SUM(life_cover_impairment),0) as asset_amount_sum  FROM `client_insurances` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' group by client_reference_id 
                                            UNION
                                SELECT SUM(asset_amount) as asset_amount_sum FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        $emergencyfund = DB::select("SELECT SUM(asset_amount) as emergency_fund FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND asset_type=1 ");

        $current_insurance_sum = DB::select("SELECT COALESCE(SUM(life_cover_cash_value),0) + COALESCE(SUM(life_cover_death),0) + COALESCE(SUM(life_cover_disability),0) + COALESCE(SUM(life_cover_dread_disease),0) + COALESCE(SUM(life_cover_impairment),0) as current_insurance FROM `client_insurances` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        $my_asset_needs_sum = DB::select("SELECT SUM(asset_amount) as my_asset_needs FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        if (!empty($current_client_income) && sizeof($current_client_income) > 0) {
            $client_income = isset($current_client_income[0]->client_sum) ? $current_client_income[0]->client_sum : "0";
        }
        if (!empty($total_liabilities) && sizeof($total_liabilities) > 0) {
            $total_liabilities_sum = isset($total_liabilities[0]->total_liabilities_sum) ? $total_liabilities[0]->total_liabilities_sum : "0";
        }
        if (!empty($total_cash_depends) && sizeof($total_cash_depends) > 0) {
            $total_cash_depends_sum = isset($total_cash_depends[0]->asset_amount_sum) ? $total_cash_depends[0]->asset_amount_sum : "0";
        }
        if (!empty($total_asset_amount) && sizeof($total_asset_amount) > 0) {
            $current_assets_insurance = isset($total_asset_amount[0]->asset_amount_sum) ? $total_asset_amount[0]->asset_amount_sum : "0";
            $current_assets_insurance += isset($total_asset_amount[1]->asset_amount_sum) ? $total_asset_amount[1]->asset_amount_sum : "0";
        }
        if (!empty($emergencyfund) && sizeof($emergencyfund) > 0) {
            $emergencyfund_sum = isset($emergencyfund[0]->emergency_fund) ? $emergencyfund[0]->emergency_fund : "0";
        }
        if (!empty($current_insurance_sum) && sizeof($current_insurance_sum) > 0) {
            $current_insurance_sum_final = isset($current_insurance_sum[0]->current_insurance) ? $current_insurance_sum[0]->current_insurance : "0";
        }

        if (!empty($my_asset_needs_sum) && sizeof($my_asset_needs_sum) > 0) {
            $my_asset_needs_sum_final = isset($my_asset_needs_sum[0]->my_asset_needs) ? $my_asset_needs_sum[0]->my_asset_needs : "0";
        }

        $previousYearAmount = 0;
        $twelveMonthAmount = $client_income;
        $twelveMonthInterest = $twelveMonthAmount * 0.06;
        $previousYearAmount = $twelveMonthAmount + $previousYearAmount;
        $yearlyAmount = array();
        $count = 1;
        $oi = -1 * 1;
        //$oi = (int) $oi;
        $yearlyAmount[] = $previousYearAmount * pow(1.07, $oi);
        //echo $previousYearAmount * pow(1.07,$oi); die();
        for ($i = 1; $i < 10; $i++) {
            $oi = -1 * ($count + $i);
            $counts = $count + $i;
            $currentYearInterest = $previousYearAmount  * 0.06;
            $currentYearAmount = $previousYearAmount + $currentYearInterest;
            $previousYearAmount = $currentYearAmount;
            $yearlyAmount[] = $previousYearAmount * pow(1.07, $oi);
            //echo $counts."<br/>";
        }
        $finalAmount = 0;
        foreach ($yearlyAmount as $value) {
            $finalAmount += $value;
        }


        $my_cashflow_needs = ($total_liabilities_sum + $total_cash_depends_sum);
        $shortfall_death = ($my_cashflow_needs - $finalAmount);
        $lump_sum_needed = ($my_cashflow_needs + $finalAmount);
        $shortfall_severe_illness = ($my_asset_needs_sum_final - $current_insurance_sum_final);

        $spouse = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type', '!=', $client_type)
            ->first();
        if (!empty($spouse) && isset($spouse)) {
            $data['spouse_id'] = $spouse->id;
        }

        return view('fna.overview', [
            'reserveFundsDataMonths' => $reserveFundsDataMonths,
            'SpouseDataExits' => $SpouseDataExits,
            'current_client_income' => $finalAmount,
            'my_cashflow_needs' => $my_cashflow_needs,
            'shortfall_death' => $shortfall_death,
            'lump_sum_needed' => $lump_sum_needed,
            'client_type' => $client_type,
            'current_assets_insurance' => $current_assets_insurance,
            'emergencyfund_sum' => $emergencyfund_sum,
            'current_insurance_sum_final' => $current_insurance_sum_final,
            'my_asset_needs_sum_final' => $my_asset_needs_sum_final,
            'shortfall_severe_illness' => $shortfall_severe_illness,
            'clientData' => $data, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type, 'incomeData' => '[' . $incomeFinalData . ']', 'expenseData' => '[' . $expenseFinalData . ']', 'monthFinalName' => '[' . $monthFinalName . ']'
        ]);
    }

    function custom_number_format($n, $precision = 1)
    {
        if ($n < 900) {
            // Default
            $n_format = number_format($n);
        } else if ($n < 900000) {
            // Thausand
            $n_format = number_format($n / 1000, $precision) . 'K';
        } else if ($n < 900000000) {
            // Million
            $n_format = number_format($n / 1000000, $precision) . 'M';
        } else if ($n < 900000000000) {
            // Billion
            $n_format = number_format($n / 1000000000, $precision) . 'B';
        } else {
            // Trillion
            $n_format = number_format($n / 1000000000000, $precision) . 'T';
        }
        return $n_format;
    }


    public function insurance($client_reference_id, $client_type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $client_income = $total_liabilities_sum =  $total_cash_depends_sum = $my_cashflow_needs = $shortfall_death = $lump_sum_needed = $finalAmount = 0;
        $current_assets_insurance = $emergencyfund_sum = $current_insurance_sum_final =  $my_asset_needs_sum_final = 0;
        $current_client_income = DB::select("SELECT SUM(item_value) as client_sum FROM `budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `income_expenses_type` = 1");
        $total_liabilities = DB::select("SELECT SUM(outstanding_balance) as total_liabilities_sum  FROM `client_liabilities_new` WHERE `client_reference_id` = '" . $client_reference_id . "'  AND `client_type` = '" . $client_type . "' ");
        $total_cash_depends = DB::select("SELECT SUM(asset_amount) as asset_amount_sum  FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `asset_type` = '7'");
        $total_asset_amount = DB::select("SELECT COALESCE(SUM(life_cover_cash_value),0) + COALESCE(SUM(life_cover_death),0) + COALESCE(SUM(life_cover_disability),0) + COALESCE(SUM(life_cover_dread_disease),0) + COALESCE(SUM(life_cover_impairment),0) as asset_amount_sum  FROM `client_insurances` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' group by client_reference_id 
                                            UNION
                                SELECT SUM(asset_amount) as asset_amount_sum FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        $emergencyfund = DB::select("SELECT SUM(asset_amount) as emergency_fund FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND asset_type=1 ");

        $current_insurance_sum = DB::select("SELECT COALESCE(SUM(life_cover_cash_value),0) + COALESCE(SUM(life_cover_death),0) + COALESCE(SUM(life_cover_disability),0) + COALESCE(SUM(life_cover_dread_disease),0) + COALESCE(SUM(life_cover_impairment),0) as current_insurance FROM `client_insurances` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        $my_asset_needs_sum = DB::select("SELECT SUM(asset_amount) as my_asset_needs FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' ");

        $income_protector_needs = DB::select("SELECT SUM(asset_amount) as income_protector_needs_sum  FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND `asset_type` = '11'");

        $current_income_protector_needs = DB::select("SELECT SUM(asset_amount) as current_income_protector_needs_sum  FROM `client_assets` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `client_type` = '" . $client_type . "' AND  `asset_type` IN ('12','13','14','15','16','17','18','19','20')");

        if (!empty($current_client_income) && sizeof($current_client_income) > 0) {
            $client_income = isset($current_client_income[0]->client_sum) ? $current_client_income[0]->client_sum : "0";
        }
        if (!empty($total_liabilities) && sizeof($total_liabilities) > 0) {
            $total_liabilities_sum = isset($total_liabilities[0]->total_liabilities_sum) ? $total_liabilities[0]->total_liabilities_sum : "0";
        }
        if (!empty($total_cash_depends) && sizeof($total_cash_depends) > 0) {
            $total_cash_depends_sum = isset($total_cash_depends[0]->asset_amount_sum) ? $total_cash_depends[0]->asset_amount_sum : "0";
        }
        if (!empty($total_asset_amount) && sizeof($total_asset_amount) > 0) {
            $current_assets_insurance = isset($total_asset_amount[0]->asset_amount_sum) ? $total_asset_amount[0]->asset_amount_sum : "0";
            $current_assets_insurance += isset($total_asset_amount[1]->asset_amount_sum) ? $total_asset_amount[1]->asset_amount_sum : "0";
        }
        if (!empty($emergencyfund) && sizeof($emergencyfund) > 0) {
            $emergencyfund_sum = isset($emergencyfund[0]->emergency_fund) ? $emergencyfund[0]->emergency_fund : "0";
        }
        if (!empty($current_insurance_sum) && sizeof($current_insurance_sum) > 0) {
            $current_insurance_sum_final = isset($current_insurance_sum[0]->current_insurance) ? $current_insurance_sum[0]->current_insurance : "0";
        }

        if (!empty($my_asset_needs_sum) && sizeof($my_asset_needs_sum) > 0) {
            $my_asset_needs_sum_final = isset($my_asset_needs_sum[0]->my_asset_needs) ? $my_asset_needs_sum[0]->my_asset_needs : "0";
        }

        if (!empty($income_protector_needs) && sizeof($income_protector_needs) > 0) {
            $income_protector_needs = isset($income_protector_needs[0]->income_protector_needs_sum) ? $income_protector_needs[0]->income_protector_needs_sum : "0";
        }

        if (!empty($current_income_protector_needs) && sizeof($current_income_protector_needs) > 0) {
            $current_income_protector_needs = isset($current_income_protector_needs[0]->current_income_protector_needs_sum) ? $current_income_protector_needs[0]->current_income_protector_needs_sum : "0";
        }

        /*$previousYearAmount = 0;
        	$twelveMonthAmount = $client_income ;
        	$twelveMonthInterest = $twelveMonthAmount * 0.06;
        	$previousYearAmount = $twelveMonthAmount + $previousYearAmount;
        	$yearlyAmount = array();
        	$count = 1;
        	$oi = -1 * 1;
        	//$oi = (int) $oi;
        	$yearlyAmount[] = $previousYearAmount * pow(1.07,$oi);
        	//echo $previousYearAmount * pow(1.07,$oi); die();
        	for($i = 1; $i < 10; $i++)
        	{    
        		$oi = -1 * ($count + $i); 
                $counts = $count + $i;
        		$currentYearInterest = $previousYearAmount  * 0.06;
        		$currentYearAmount = $previousYearAmount + $currentYearInterest;
        		$previousYearAmount = $currentYearAmount;
        		$yearlyAmount[] = $previousYearAmount * pow(1.07,$oi);
        		//echo $counts."<br/>";
        	}
        	$finalAmount = 0;
        	foreach($yearlyAmount as $value)
        	{
        		$finalAmount += $value;
        	}*/
        $finalAmount = $client_income;

        $my_cashflow_needs = ($total_liabilities_sum + $total_cash_depends_sum);
        $shortfall_death = ($my_cashflow_needs - $finalAmount);
        $lump_sum_needed = ($my_cashflow_needs + $finalAmount);

        //$income_protector_needs = $shortfall_surplus ='10000';
        $shortfall_death_right =   $lump_sum_needed - $current_assets_insurance;
        $severe_illness_shortfall = $my_asset_needs_sum_final - $current_insurance_sum_final;
        $death = array(
            (int) $my_cashflow_needs,
            (int) $lump_sum_needed,
            (int) $finalAmount,
            (int) $current_assets_insurance,
            (int) $shortfall_death,
            (int) $shortfall_death_right
        );
        sort($death);

        $temporary_disability_sickness  = array(
            (int) $my_cashflow_needs,
            (int) $finalAmount,
            (int) $emergencyfund_sum,
            (int) $shortfall_death
        );
        sort($temporary_disability_sickness);

        $permanent_disability = array(
            (int) $my_cashflow_needs,
            (int) $lump_sum_needed,
            (int) $current_client_income,
            (int) $current_assets_insurance,
            (int) $shortfall_death,
            (int) $current_income_protector_needs,
            (int) $income_protector_needs
        );
        sort($permanent_disability);

        $severe_illness  = array(
            (int) $my_asset_needs_sum_final,
            (int) $current_insurance_sum_final,
            (int) $severe_illness_shortfall
        );
        sort($severe_illness);

        $death_min = min($death);
        $death_max = max($death);

        $temporary_disability_sickness_min = min($temporary_disability_sickness);
        $temporary_disability_sickness_max = max($temporary_disability_sickness);

        $permanent_disability_min = min($permanent_disability);
        $permanent_disability_max = max($permanent_disability);

        $dread_disease_opts_min = min($severe_illness);
        $dread_disease_opts_max = max($severe_illness);

        $length_severe_illness = count($severe_illness);
        $half_length_severe_illness = $length_severe_illness / 2;
        $dread_disease_opts_median_index = (int) $half_length_severe_illness;
        $dread_disease_opts_median = (int)$severe_illness[$dread_disease_opts_median_index];


        $length = count($death);
        $half_length = $length / 2;
        $median_index = (int) $half_length;
        $death_median = (int)$death[$median_index];


        $length_temporary = count($temporary_disability_sickness);
        $half_length_temporary = $length_temporary / 2;
        $temporary_median = (int) $half_length_temporary;
        $temporary_disability_sickness_median = (int)$temporary_disability_sickness[$temporary_median];


        $length_permanent_disability = count($permanent_disability);
        $half_length_temporary_permanent_disability = $length_permanent_disability / 2;
        $temporary_permanent_disability = (int) $half_length_temporary_permanent_disability;
        $temporary_permanent_disability_median = (int)$permanent_disability[$temporary_permanent_disability];


        $checkSpouseExits = DB::select("SELECT *  FROM `clients` WHERE `client_type` = 'Spouse' AND `client_reference_id` = '" . $client_reference_id . "' ");
        $SpouseDataExits = 'No';
        if (!empty($checkSpouseExits) && sizeof($checkSpouseExits) > 0) {
            $SpouseDataExits = 'Yes';
        }


        return view('fna.insurance', [
            'SpouseDataExits' => $SpouseDataExits,
            'client_reference_id' => $client_reference_id,
            'current_client_income' => $finalAmount,
            'my_cashflow_needs' => $my_cashflow_needs,
            'shortfall_death' => $shortfall_death,
            'lump_sum_needed' => $lump_sum_needed,
            'client_type' => $client_type,
            'current_assets_insurance' => $current_assets_insurance,
            'emergencyfund_sum' => $emergencyfund_sum,
            'current_insurance_sum_final' => $current_insurance_sum_final,
            'my_asset_needs_sum_final' => $my_asset_needs_sum_final,
            'shortfall_death_right' => $shortfall_death_right,
            'death_min' => (int)$death_min,
            'death_max' => (int)$death_max,
            'death_median' => $death_median,
            'temporary_disability_sickness_min' => $temporary_disability_sickness_min,
            'temporary_disability_sickness_max' => $temporary_disability_sickness_max,
            'temporary_disability_sickness_median' => $temporary_disability_sickness_median,
            'permanent_disability_min' => $permanent_disability_min,
            'permanent_disability_max' => $permanent_disability_max,
            'temporary_permanent_disability_median' => $temporary_permanent_disability_median,
            'dread_disease_opts_min' => $dread_disease_opts_min,
            'dread_disease_opts_max' => $dread_disease_opts_max,
            'dread_disease_opts_median' => $dread_disease_opts_median,
            'death' => $death,
            'temporary_disability_sickness' => $temporary_disability_sickness,
            'permanent_disability' => $permanent_disability,
            'severe_illness' => $severe_illness,
            'income_protector_sum' => $income_protector_needs,
            'current_income_protector_needs' => $current_income_protector_needs,
            'severe_illness_shortfall' => $severe_illness_shortfall
        ]);
    }

    public function cashflow($client_reference_id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '" . $userId . "')");
        $bankTransaction = DB::select("SELECT transaction_date,transaction_amount,transaction_description FROM `bank_transaction_details` INNER JOIN bank_transaction ON bank_transaction_details.bank_transaction_id = bank_transaction.id where client_reference_id = '" . $client_reference_id . "' ");
        //dd($bankTransaction);
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => 'CashFlow',
            'role' => $userRole[0]->name,
            'action' => "Landed on Assets/Liabilities Create Page",
            'date' => DB::raw('now()')
        ]);
        $month = date('m');
        $monthlyIncomeData = $monthExpensesData = array();
        $monthlyIncome = DB::select("SELECT SUM(item_value) as monthly_income_sum,item_type  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 1 GROUP BY item_type");
        if (isset($monthlyIncome)) {
            foreach ($monthlyIncome as $key =>  $monthInData) {
                $monthlyIncomeData[$key]['monthly_income_sum'] = (int)$monthInData->monthly_income_sum;
                $monthlyIncomeData[$key]['monthly_income_label'] = $monthInData->item_type;
            }
        }
        $monthlyIncomeFinalData = json_encode($monthlyIncomeData);

        $monthlyExpenses  = DB::select("SELECT SUM(item_value) as monthly_expenses_sum,item_type  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 2 GROUP BY item_type");

        if (isset($monthlyExpenses)) {
            foreach ($monthlyExpenses as $key =>  $monthExData) {
                $monthExpensesData[$key]['monthly_expenses_sum'] = (int)$monthExData->monthly_expenses_sum;
                $monthExpensesData[$key]['monthly_expenses_label'] = $monthExData->item_type;
            }
        }
        $monthExpensesFinalData = json_encode($monthExpensesData);
        $monthlyDebtLoans = DB::select("SELECT SUM(item_value) as monthlyDebtLoans FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 2  AND item_type = 'Debt Loans' GROUP BY item_type");
        $monthlyLifeInsurances = DB::select("SELECT SUM(item_value) as monthlyLifeInsurance  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 2  AND item_type = 'Life Insurance' GROUP BY item_type");
        $monthlyCash = DB::select("SELECT SUM(item_value) as monthlyCash  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 1  AND item_type = 'Emergency Funds' AND item_type = 'Cash' GROUP BY item_type");

        $Emergency_Funds = DB::select("SELECT SUM(item_value) as monthlyCash  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 1  AND item_type = 'Emergency Funds' GROUP BY item_type");
        $Cash = DB::select("SELECT SUM(item_value) as monthlyCash  FROM `yearly_budget` WHERE `client_reference_id` = '" . $client_reference_id . "' AND `month` = '" . $month . "' AND income_expenses_type = 1  AND item_type = 'Cash' GROUP BY item_type");
        $EmergencyFundsData = isset($Emergency_Funds[0]->monthlyCash) ? $Emergency_Funds[0]->monthlyCash : "0";
        $CashData = isset($Cash[0]->monthlyCash) ? $Cash[0]->monthlyCash : "0";

        $monthlyCash = $EmergencyFundsData + $CashData;
        $monthlyDebtLoan = isset($monthlyDebtLoans[0]) ? $monthlyDebtLoans[0]->monthlyDebtLoans : "0.00";
        $monthlyLifeInsurance = isset($monthlyLifeInsurances[0]) ? $monthlyLifeInsurances[0]->monthlyLifeInsurance : "0.00";
        $monthlyTotalCash = isset($monthlyCash) ? $monthlyCash : "0.00";

        //get transaction details
        $transactionId = $transactionsDetails = '';
        $transactions = DB::table('bank_transaction')->where('client_reference_id', $client_reference_id)->get();

        if (sizeof($transactions)) {
            $transactionId = $transactions[0]->id;
            $transactionsDetails = DB::table('bank_transaction_details')->where('bank_transaction_id', $transactionId)->get();
        }



        return view('fna.cashflow', [
            'client_reference_id' => $client_reference_id,
            'monthlyIncomeFinalData' => $monthlyIncomeFinalData,
            'monthExpensesData' => $monthExpensesFinalData,
            'monthlyDebtLoan' => $monthlyDebtLoan,
            'monthlyLifeInsurance' => $monthlyLifeInsurance,
            'monthlyCash' => $monthlyTotalCash,
            'bankTransaction' => $bankTransaction,
            'transactionsDetails' => $transactionsDetails
        ]);
    }

    public function whatamiworth($client_reference_id, $client_type)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

        $assetGrandTotal = DB::select("SELECT SUM(`asset_amount`) as asset_total FROM `client_assets`  WHERE client_reference_id = '" . $client_reference_id . "' and client_type = '" . $client_type . "'");
        $assetTotal = DB::select("SELECT
                                        SUM(asset_amount) AS asset_total,
                                        name as asset_type
                                    FROM
                                        `client_assets`
                                    LEFT JOIN asset_liability_types ON asset_type = asset_liability_types.id AND asset_liability_type = 1
                                    WHERE
                                        client_reference_id = '" . $client_reference_id . "'  and client_type = '" . $client_type . "' 
                                    GROUP BY
                                        asset_type
                                    ORDER BY NAME ASC");
        $liabilitiesGrandTotal = DB::select("SELECT SUM(`outstanding_balance`) as liability_total FROM `client_liabilities_new` WHERE client_reference_id = '" . $client_reference_id . "'  and client_type = '" . $client_type . "'");
        // $liabilitiesTotal = DB::select("SELECT SUM(outstanding_balance) as liability_total, liability_type FROM `client_liabilities_new` WHERE client_reference_id = '". $client_reference_id ."'  and client_type = '".$client_type."' GROUP BY liability_type ");       

        $liabilitiesTotal = DB::select("SELECT
                                        SUM(outstanding_balance) AS liability_total,
                                        name as liability_type
                                    FROM
                                        `client_liabilities_new`
                                    LEFT JOIN asset_liability_types ON liability_type = asset_liability_types.id AND asset_liability_type = 2
                                    WHERE
                                        client_reference_id = '" . $client_reference_id . "'  and client_type = '" . $client_type . "' 
                                    GROUP BY
                                        liability_type
                                    ORDER BY name ASC");

        return view(
            'fna.whatamiworth',
            [
                'client_reference_id' => $client_reference_id,
                'client_type' => $client_type,
                'assetTotal' => $assetTotal,
                'assetGrandTotal' => $assetGrandTotal,
                'liabilitiesGrandTotal' => $liabilitiesGrandTotal,
                'liabilitiesTotal' => $liabilitiesTotal
            ]
        );
    }
    public function storeBankStatement(Request $request)
    {

        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        request()->validate([
            'file'  => 'required|mimes:doc,docx,pdf,txt|max:10000',
        ]);
        $client_reference_id = $request->client_reference_id;
        if ($files = $request->file('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $location = storage_path() . '/public/documents/';
            $file->move($location, $filename);
            $filepath = url('files/' . $filename);
            $bank_id = DB::table('bank_transaction')->insertGetId([
                'advisor_id' => $userId,
                'advisor_capture_id' => '',
                'client_id' => $userId,
                'client_reference_id' => $client_reference_id,
                'date' => date("y-m-d"),
                'capture_date' => date("y-m-d"),
                'file_name' => $filename
            ]);

            $filepath = $location . $filename;
            $file = fopen($filepath, "r");
            $importData_arr = array();
            $i = 0;
            BankstatementsAuth($filepath,$client_reference_id);
            // while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
            // 	$num = count($filedata);
            // 	// Skip first row (Remove below comment if you want to skip the first row)
            // 	if ($i == 0) {
            // 		$i++;
            // 		continue;
            // 	}
            // 		for ($c = 0; $c < $num; $c++) {
            // 		$importData_arr[$i][] = $filedata[$c];
            // 	}
            // 	$i++;
            // }

            // foreach ($importData_arr as $importData) {
            //     $transaction_date = date("y-m-d", strtotime($importData[0]));
            //     $transaction_amount = $importData[1];
            //     $transaction_description = $importData[2];
            //     DB::table('bank_transaction_details')->insertGetId([
            //         'transaction_date' => $transaction_date, 
            //         'transaction_amount' =>$transaction_amount,
            //         'transaction_description' => $transaction_description,
            //         'bank_transaction_id' => $bank_id
            //     ]);
            // }
            return Response()->json([
                "success" => true,
                "file" => $filename
            ]);
        }
        return Response()->json(["success" => false, "file" => '']);
    }
    function BankstatementsAuth($filepath,$client_reference_id)
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
        //echo $response;
        echo "</br>";
        $token = json_decode($response);
        $getToken = $token->data->auth->token;
        echo $getToken;
        UploadBankstatements($getToken, $filepath,$client_reference_id);
    }


    function UploadBankstatements($token, $filePath,$client_reference_id)
    {
        //get token from previous call. 
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
            CURLOPT_POSTFIELDS => array('0' => new CURLFILE($filePath), 'operations' => '{ "query": "query ($file: Upload!, $password: String) { parseBankStatement(file: $file, password: $password) {bank_alias, bank_name, begin_date, end_date, debit_total, credit_total, statement_type, statement_number, opening_balance, closing_balance, account {id, available_balance, current_balance, credit_line,account_holder, account_number, title, credit_line}, salary{id, amount, full_title}, transactions {id, account_id, full_title, title, amount, service_fee, transaction_date, running_balance, order, category {id, title}, currency}}}", "variables": { "file": null, "password": ""} }', 'map' => '{ "0": ["variables.file"]}'),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data',
                'Authorization: Bearer ' . $token . ''
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
        $getProductSectorSet = json_decode($response);
        $ProductSectors = $getProductSectorSet->data->parseBankStatement->salary;
        //Salary
        $salaryamount = $getProductSectorSet->data->parseBankStatement->salary->amount;
        $salarytitle = $getProductSectorSet->data->parseBankStatement->salary->full_title;

       //Loop through all transactions
       $transactions = $getProductSectorSet->data->parseBankStatement->transactions;
       //insert into income and expenses. 
       foreach ($transactions as $value) {
        //  "id": "a473c72b57bddb8067bae1e53c24559e",
        //         "account_id": "9bd0cb7f6adfe0fffb4b098901523191",
        //         "full_title": "Magband Debiet Brolink Aic012033428013083",
        //         "title": "Magband Debiet Brolink Aic",
        //         "amount": -1412.06,
        //         "service_fee": 0,
        //         "transaction_date": "2022-07-02",
        //         "running_balance": 5026.62,
        //         "order": 4,
        //         "category": {
        //             "id": 19,
        //             "title": "Other Expenses"
        //         },
        //         "currency": "ZAR"
         
        }
       //
        
    echo $ProductSectors;
    }

    public function storeIncomeExpense(Request $request)
    {
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
        //return \Redirect::route('cashflow', ['client_reference_id' => $_POST['client_reference_id']]);
    }
    public function overview_list()
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

        $clients = DB::table('clients')
            ->where('client_type', 'Main Client')
            ->get()
            ->map(function ($client) {

                $clientData['id'] = $client->id;
                $clientData['first_name'] = $client->first_name;
                $clientData['last_name'] = $client->last_name;
                $clientData['client_reference_id'] = $client->client_reference_id;

                $total_assets = DB::table('client_assets_liabilities')
                    ->where('asset_liability_type', 1)
                    ->where('client_reference_id', $client->client_reference_id)
                    ->pluck('item_value')
                    ->sum();
                $total_liabilities = DB::table('client_assets_liabilities')
                    ->where('asset_liability_type', 2)
                    ->where('client_reference_id', $client->client_reference_id)
                    ->pluck('item_value')
                    ->sum();

                $clientData['total_assets'] = $total_assets;
                $clientData['total_liabilities'] = $total_liabilities;
                $clientData['total_worth'] = $total_assets - $total_liabilities;

                return $clientData;
            });
        // dd($clientData);
        return view('fna.overview_index', ['getroleId' => $getroleId, 'clients' => $clients, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName, 'getAccessName' => $getAccessName, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }

    public function overviewListAjax(Request $request)
    {
        $clients = DB::table('clients')
            ->where('client_type', 'Main Client')
            ->get()
            ->map(function ($client) {

                $clientData['id'] = $client->id;
                $clientData['first_name'] = $client->first_name;
                $clientData['last_name'] = $client->last_name;
                $clientData['client_reference_id'] = $client->client_reference_id;

                $total_assets = DB::table('client_assets_liabilities')
                    ->where('asset_liability_type', 1)
                    ->where('client_reference_id', $client->client_reference_id)
                    ->pluck('item_value')
                    ->sum();
                $total_liabilities = DB::table('client_assets_liabilities')
                    ->where('asset_liability_type', 2)
                    ->where('client_reference_id', $client->client_reference_id)
                    ->pluck('item_value')
                    ->sum();

                $clientData['total_assets'] = $total_assets;
                $clientData['total_liabilities'] = $total_liabilities;
                $clientData['total_worth'] = $total_assets - $total_liabilities;

                return $clientData;
            });

        if ($request->ajax()) {
            $data = $clients;
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    //   $btn = '<a href="javascript:void(0)" class="edit btn btn-primary btn-sm">View</a>';
                    // $btn = '<a href="https://fna2.phpapplord.co.za/public/clientEdit/'.$row->client_reference_id.'"><i class="fa fa-eye"></i></a>';
                    $btn = '<a  data-rowid="' . $row['id'] . '" href="https://fna2.phpapplord.co.za/public/overview/' . $row['client_reference_id'] . '/Main Client"><i class="fa fa-eye"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('fna.overview_index');
    }

    public function getClients(Request $request)
    {
        // $clients = DB::table('clients');
        //                 ->where('client_type','Main Client')
        //                 ->get()
        //                 ->map(function($client) {

        //                     $clientData['id'] = $client->id;
        //                     $clientData['first_name'] = $client->first_name;
        //                     $clientData['last_name'] = $client->last_name;
        //                     $clientData['client_reference_id'] = $client->client_reference_id;

        //                     $total_assets = DB::table('client_assets_liabilities')
        //                                         ->where('asset_liability_type', 1)
        //                                         ->where('client_reference_id', $client->client_reference_id)
        //                                         ->pluck('item_value')
        //                                         ->sum();
        //                     $total_liabilities = DB::table('client_assets_liabilities')
        //                                                 ->where('asset_liability_type', 2)
        //                                                 ->where('client_reference_id', $client->client_reference_id)
        //                                                 ->pluck('item_value')
        //                                                 ->sum();

        //                     $clientData['total_assets'] = $total_assets;
        //                     $clientData['total_liabilities'] = $total_liabilities;
        //                     $clientData['total_worth'] = $total_assets - $total_liabilities;

        //                     return $clientData;
        //                 });
        if ($request->ajax()) {
            $data = DB::select('select * from clients');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<a href="javascript:void(0)" class="edit btn btn-primary btn-sm">View</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('fna.clientOverviewList');
        // return view($clients);

    }

    public function personal()
    {
        header('Content-Type: application/json');
        $astute = new Astute("Abrie89", "Kawasaki@1234567", "84FB23D7-8B98-4DAD-A2E2-E7F0895000EC");
        //$astute->GetContentProviderSet("280"); die();
        //$astute->getMessageHeaders($SectorCode); die();
        $astute->CCPRequest();
        die();
        $getProductSectorSet = $astute->getProductSectorSet();
        $getProductSectorSet = json_decode($getProductSectorSet);
        $ProductSectors = $getProductSectorSet->Result->Data->ProductSector;
        foreach ($ProductSectors as $ProductSector) {
            echo "Product Sector Sector Code is " . $ProductSector->SectorCode . " and the value is " . $ProductSector->Value . "\n";
        }
        die();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $personal_details = DB::select("SELECT * FROM `personal_details`");
        //$personal_details = DB::select("SELECT * FROM `clients` where userId = '$userId' ");
        return view('fna.index', ['personal_details' => $personal_details, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }





    public function company()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $companyInfo = DB::select("SELECT * FROM `companies` where userId = '$userId' ");
        return view('fna.company', ['userId' => $userId, 'companyInfo' => $companyInfo, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function companyUpdate(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        DB::select("UPDATE companies set name = '" . $_POST['name'] . "', registrationNo = '" . $_POST['regNumber'] . "', address = '" . $_POST['address'] . "',bankName = '" . $_POST['bankName'] . "', phone = '" . $_POST['phone'] . "', bankNo = '" . $_POST['BankAccountNo'] . "', branchCode = '" . $_POST['bankCode'] . "', accountType = '" . $_POST['accountType'] . "' where userId = '$userId'");
        $request->session()->flash('success', 'Company Info Updated Successfully');
        return redirect()->back();
    }
    public function companyCreateForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $company_id = DB::table('companies')->insertGetId([
            'id' => null,
            'name' => $_POST['name'],
            'registrationNo' => $_POST['regNumber'],
            'address' => $_POST['address'],
            'adminEmail' => $_POST['adminEmail'],
            'billingEmail' => $_POST['billingEmail'],
            'phone' => $_POST['phone'],
            'bankName' => $_POST['bankName'],
            'bankNo' => $_POST['BankAccountNo'],
            'branchCode' => $_POST['bankCode'],
            'accountType' => $_POST['accountType'],
            'userId' => $userId
        ]);
        DB::select("INSERT into companyUsers values('$userId', '$company_id')");
        $request->session()->flash('success', 'Company Info Created Successfully');
        return redirect()->back();
    }
    public function clients()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $personal_details = DB::select("SELECT * FROM `clients`");
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.clients', ['personal_details' => $personal_details, 'userId' => $userId, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    //company advisor
    public function companyAdvisorView()
    {

        return view('fna.componyAdvisor');
    }

    public function storeCompanyAdvisor(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'company_email' => 'required|email',
            'company_phone' => 'required',
            'company_address' => 'required',
            'astude_code' => 'required',
            'advisor_name' => 'required',
            'advisor_surname' => 'required',
            'advisor_email' => 'required|email',
            'advisor_phone' => 'required',
            'advisor_gender' => 'required',
            'advisor_idNo' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $user_id = DB::table('users')->insertGetId([
            'email' => $request->advisor_email,
            'name' => $request->advisor_name,
            'surname' => $request->advisor_surname,
            'idNumber' => $request->advisor_idNo,
            'type' => 'Main Advisor',
            'phone' => $request->advisor_phone,
            'gender' => $request->advisor_gender,
            'dob' => $request->advisor_dob,
            'active_status' => true,
            'password' => Hash::make($request->password)

        ]);

        $company_id = DB::table('company')->insertGetId([
            'name' => $request->company_name,
            'address' => $request->company_address,
            'billing_email' => $request->company_email,
            'reception_phone' => $request->company_phone
        ]);

        $reqion_id = DB::table('company_regions')->insertGetId([
            'region_name' => $request->company_address,
            'region_address' => $request->company_address,
            'is_active' => true,
            'company_id' => $company_id

        ]);

        $company_id = DB::table('advisors')->insertGetId([
            'company_id' => $company_id,
            'user_id' => $user_id,
            'is_active' => true,
            'region_id' => $reqion_id,
            'parent_id' => $company_id,
            'type' => 'Main Advisor'
        ]);

        DB::table('permissions')->insert([
            'groupId' => 14,
            'userId' => $user_id,
        ]);

        $request->session()->flash('success', 'Company Advisor created successfully');

        return redirect()->route('login');
    }

    public function companyAdvisorPlanView()
    {
        return view('fna.companyAdvisorPlan');
    }


    public function userGroup()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $user_groups = DB::select("SELECT * FROM `user_groups` order by id desc");
        $userId = $_SESSION['userId'];
        $userType = $_SESSION['userType'];
        $modules = "user Roles";
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
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.userGroup', ['user_groups' => $user_groups, 'userId' => $userId, 'userType' => $userType, 'modules' => $modules, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function userGroupUpdate(Request $request)
    {
        DB::select("UPDATE user_groups set name = '" . $_POST['name'] . "' where id = '" . $_POST['group_id'] . "'");
        $request->session()->flash('success', 'User Group Name Updated Successfully');
        return redirect()->back();
    }
    public function userGroupCreateForm(Request $request)
    {
        DB::select("INSERT into user_groups values(null, '" . $_POST['name'] . "')");
        $request->session()->flash('success', 'User Group Created Successfully');
        return redirect()->back();
    }
    public function userGroupDelete(Request $request)
    {
        DB::select("DELETE from user_groups  where id = '" . $_POST['group_id'] . "' ");
        $request->session()->flash('success', 'User Group Deleted Successfully');
        return redirect()->back();
    }




    public function accessGroup()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $access = DB::select("SELECT * FROM `access` order by id desc");
        $userId = $_SESSION['userId'];
        return view('fna.access', ['access' => $access, 'userId' => $userId,  'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function acl()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $access = DB::select("SELECT * FROM `access` order by id desc");
        $acl = DB::select("SELECT * FROM `acl` order by id desc");
        $roles = DB::select("SELECT * FROM `user_groups` order by id desc");
        $modules = DB::select("SELECT * FROM `modules` order by name asc");
        $userId = $_SESSION['userId'];
        $userType = $_SESSION['userType'];
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
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.acl', ['acl' => $acl, 'access' => $access, 'roles' => $roles, 'userId' => $userId, 'modules' => $modules, 'getroleId' => $getroleId, 'userType' => $userType, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function accessUpdate(Request $request)
    {
        DB::select("UPDATE access set name = '" . $_POST['name'] . "' where id = '" . $_POST['access_id'] . "'");
        $request->session()->flash('success', 'Access Updated Successfully');
        return redirect()->back();
    }
    public function aclUpdate(Request $request)
    {
        //echo "UPDATE acl set roleId = '".$_POST['roleId']."', moduleId = '".$_POST['moduleId']."', accessId = '".$_POST['accessId']."' where id = '".$_POST['acl_id']."'"; die();
        DB::select("UPDATE acl set roleId = '" . $_POST['roleId'] . "', moduleId = '" . $_POST['moduleId'] . "', accessId = '" . $_POST['accessId'] . "' where id = '" . $_POST['acl_id'] . "'");
        $request->session()->flash('success', 'Access Control List Updated Successfully');
        return redirect()->back();
    }
    public function accessCreateForm(Request $request)
    {
        DB::select("INSERT into access values(null, '" . $_POST['name'] . "')");
        $request->session()->flash('success', 'Access Created Successfully');
        return redirect()->back();
    }

    public function aclCreateForm(Request $request)
    {
        DB::select("INSERT into acl values(null, '" . $_POST['roleId'] . "', '" . $_POST['moduleId'] . "',  '" . $_POST['accessId'] . "')");
        return redirect()->route('acl');
    }
    public function accessDelete(Request $request)
    {
        DB::select("DELETE from access  where id = '" . $_POST['access_id'] . "' ");
        $request->session()->flash('success', 'Access Deleted Successfully');
        return redirect()->back();
    }

    public function aclDelete(Request $request)
    {
        DB::select("DELETE from acl  where id = '" . $_POST['acl_id'] . "' ");
        $request->session()->flash('success', 'Access Control List Item Deleted Successfully');
        return redirect()->back();
    }



    public function userCreate()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $userType = $_SESSION['userType'];
        $roles = DB::select("SELECT * FROM `user_groups` order by id desc");
        $rolesResctricts = DB::select("SELECT * FROM `user_groups` where name NOT IN ('Super Admin (App)','Admin (App)') order by id desc");

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $companies = DB::select("SELECT * FROM `companies` order by id desc");
        return view('fna.userCreate', ['userId' => $userId, 'roles' => $roles, 'companies' => $companies, 'rolesResctricts' => $rolesResctricts, 'userType' => $userType,  'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function userUpdate($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $userType = $_SESSION['userType'];
        if ($userType == "App Administrator") {
            $companies = DB::select("SELECT * FROM `companies` order by id desc");
        } else {
            $companies = "none";
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $roles = DB::select("SELECT * FROM `user_groups` order by id desc");
        $userInfo = DB::select("SELECT * FROM `users` where id = '$id' ");
        $rolesId = DB::select("SELECT * FROM `permissions` where userId = '$id' ");
        $rolesResctricts = DB::select("SELECT * FROM `user_groups` where name NOT IN ('Super Admin (App)','Admin (App)') order by id desc");
        return view('fna.userUpdate', ['userId' => $userId, 'userInfo' => $userInfo, 'roles' => $roles, 'rolesId' => $rolesId, 'companies' => $companies, 'rolesResctricts' => $rolesResctricts, 'userType' => $userType, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function userCreateForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $company_id = $_POST['company'];
        $userId = DB::table('users')->insertGetId([
            'id' => null,
            'email' => $_POST['email'],
            'password' => md5($_POST['password']),
            'name' => $_POST['name'],
            'surname' => $_POST['surname'],
            'idNumber' => $_POST['idNumber'],
            'type' => $_POST['userType'],
            'phone' => $_POST['phone'],
            'gender' => $_POST['gender'],
            'dob' => $_POST['day'] . "-" . $_POST['month'] . "-" . $_POST['year']
        ]);
        if ($_POST['userType'] == "Company User") {
            DB::select("INSERT into companyUsers values('$userId', '$company_id')");
        }
        $group_id = $_POST['roleId'];
        DB::select("INSERT into permissions values('$group_id', '$userId')");
        $request->session()->flash('success', 'Company Info Created Successfully');
        return redirect()->back();
    }
    public function userUpdateForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        //var_dump($_POST); die(); 
        $userId = $_SESSION['userId'];
        $company_id = $_POST['company'];
        DB::select("UPDATE users set email = '" . $_POST['email'] . "', name = '" . $_POST['name'] . "',surname = '" . $_POST['surname'] . "',idNumber = '" . $_POST['idNumber'] . "', type = '" . $_POST['userType'] . "',phone = '" . $_POST['phone'] . "', gender = '" . $_POST['gender'] . "', dob = '" . $_POST['day'] . "-" . $_POST['month'] . "-" . $_POST['year'] . "' where id = '" . $_POST['user_id'] . "' ");
        DB::select("DELETE from companyUsers  where userId = '" . $_POST['user_id'] . "' ");
        if ($_POST['userType'] == "Company User") {
            DB::select("DELETE from companyUsers  where userId = '" . $_POST['user_id'] . "' ");
            DB::select("INSERT into companyUsers values('$userId', '$company_id')");
        }
        $group_id = $_POST['roleId'];
        DB::select("DELETE from permissions  where userId = '" . $_POST['user_id'] . "' ");
        DB::select("INSERT into permissions values('$group_id', '" . $_POST['user_id'] . "')");
        return redirect()->route('usersList');
    }
    public function usersList()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $access = DB::select("SELECT * FROM `access` order by id desc");
        $acl = DB::select("SELECT * FROM `acl` order by id desc");
        $roles = DB::select("SELECT * FROM `user_groups` order by id desc");
        $modules = DB::select("SELECT * FROM `modules` order by name asc");
        $userId = $_SESSION['userId'];
        $userType = $_SESSION['userType'];
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
            /*
                Get Access Id to get Read/write access or Access Name
            */
            /*
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
            */
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $userId = $_SESSION['userId'];
        $users = DB::select("SELECT * FROM `users`");
        $userType = $_SESSION['userType'];
        return view('fna.usersList', ['userId' => $userId, 'users' => $users, 'userType' => $userType, 'acl' => $acl, 'access' => $access, 'roles' => $roles, 'modules' => $modules, 'userType' => $userType, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function dependants()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $dependantsModuleModuleId[0]->id . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.dependant', ['getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function assetList()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $assets = DB::select("SELECT * FROM `assets`");
        return view('fna.assetList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function noAccess()
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
            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.noAccess', ['getroleId' => $getroleId, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function dependantList()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $Module = 'Dependants';
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

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $dependants = DB::select("SELECT * FROM `depedants`");
        return view('fna.dependants', ['getroleId' => $getroleId, 'dependants' => $dependants, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }

    public function updateDependant($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $dependantInfo = DB::select("SELECT * FROM `depedants` where id = '$id' ");
        return view('fna.updateDependants', ['dependantInfo' => $dependantInfo, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function updateAssets($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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


        $assets = DB::select("SELECT * FROM `assets` where id = '$id' ");
        $owners = DB::select("SELECT * FROM `personal_details`");

        return view('fna.updateAssets', ['assets' => $assets,  'owners' => $owners, 'id' => $id, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function updateLiabilities($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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


        $liabilities = DB::select("SELECT * FROM `liabilities` where id = '$id' ");
        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.updateLiabilities', ['liabilities' => $liabilities,  'owners' => $owners, 'id' => $id, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function logins(Request $request)
    {

        $email = $request->input("email");
        $password = $request->input("password");
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
            CURLOPT_POSTFIELDS => array('email' => $email, 'password' => $password),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        //("<pre>"); var_dump($response->success); die();
        if (isset($response->success)) {
            session_start();
            $_SESSION['login'] = 'yes';
            $_SESSION['token'] = $response->success->message->token;
            $_SESSION['userId'] = $response->success->message->data->user_id;
            $_SESSION['userType'] = $response->success->message->data->user_type; ///$users[0]->type;
            $_SESSION['group_id'] = $response->success->message->data->group_id;
            $_SESSION['roleId'] = $response->success->message->data->group_id;

            if ($response->success->message->data->group_id == 16) {
                $client = DB::table('clients')->where('user_id', $response->success->message->data->user_id)->first();

                $_SESSION['client_reference_id'] = $client->client_reference_id;
                $_SESSION['client_type'] = $client->client_type;

                return redirect()->route('clientEdit', ['id' => $client->client_reference_id]);
            }

            return redirect()->route('clientList');
        } else {
            return redirect()->back()->with('message', 'Wrong Login Credential');
        }

        /*
        $users = DB::select("SELECT * FROM `users` where email = '".$_POST['email']."' and password = '".md5($_POST['password'])."'"); 
        session_start();
        if(!empty($users))
        {
            $getroleId = DB::select("SELECT * FROM `permissions` where userId = '".$users[0]->id."'");
            $_SESSION['login'] = 'yes';
            $_SESSION['userId'] = $users[0]->id;
            $_SESSION['userType'] = $users[0]->type;
            $_SESSION['roleId'] = $getroleId[0]->groupId;
            return redirect()->route('fna');
        }
        else
        {
           return redirect()->back()->with('message', 'Wrong Login Credential');  
        }
        */
    }
    public function liabilities()
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

        $liabilities = DB::select("SELECT * FROM `liabilities`");
        return view('fna.liabilities', ['getroleId' => $getroleId, 'liabilities' => $liabilities, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName, 'getAccessName' => $getAccessName, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function income()
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
        // Get Role Id Of User
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        return view('fna.income', ['personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function createIncome()
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
        //var_dump($_SESSION); die();
        $income = DB::select("SELECT * FROM `personal_budget`");
        return view('fna.createIncome', ['personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, 'getroleId' => $getroleId, 'income' => $income, 'getAccessName' => $getAccessName, 'getAccessName' => $getAccessName, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function createAsset()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.createAsset', ['owners' => $owners, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function createAssetForm(Request $request)
    {
        $count = count($_POST["desc"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into assets values (null, '" . $_POST['at'][$i] . "', '" . $_POST['desc'][$i] . "', '" . $_POST['cmv'][$i] . "', '" . $_POST['opp'][$i] . "', '" . $_POST['ai'][$i] . "', '" . $_POST['IR'][$i] . "', '" . $_POST['DP'][$i] . "', '" . $_POST['o'][$i] . "')");
        }
        return redirect()->route('assetList');
    }
    public function createDependantForm(Request $request)
    {

        // DB::select("INSERT into depedants (id, related_to, name, surname, idNumber, dob, gender, age ) values
        // (null, '".$_POST['dependant_type']."', '".$_POST['dependant_first_name']."', '".$_POST['dependant_last_name']."', '', 
        // '".$_POST['dependant_year']."-".$_POST['dependant_month']."-".$_POST['dependant_day']."', '".$_POST['dependant_gender']."', '".$_POST['dependant_age']."')"); 
        //   return redirect()->route('dependantList');

        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        echo $count = count($_POST['dependant_type']);
        for ($j = 0; $j < $count; $j++) {
            DB::select("INSERT into dependants values (
    			null,
     			'" . $userId . "', 
    			'fna0000002',
    			'1',
    			'" . $_POST['dependant_type'][$j] . "',
    			'" . $_POST['dependant_first_name'][$j] . "', 
    			'" . $_POST['dependant_last_name'][$j] . "', 
    			'" . $_POST['dependant_year'][$j] . "-" . $_POST['dependant_month'][$j] . "-" . $_POST['dependant_day'][$j] . "', 
    			'" . $_POST['dependant_gender'][$j] . "', 
    			'" . $_POST['dependant_age'][$j] . "')
    			");
        }
        return redirect()->route('clientList');
    }
    public function createDependantForm_old(Request $request)
    {
        DB::select("INSERT into depedants values (null, '" . $_POST['related_to'] . "', '" . $_POST['name'] . "', '" . $_POST['surname'] . "', '" . $_POST['idNumber'] . "', '" . $_POST['dependant_year'] . "-" . $_POST['dependant_month'] . "-" . $_POST['dependant_day'] . "', '" . $_POST['dependant_gender'] . "')");
        return redirect()->route('dependantList');
    }
    public function updateDependantForm(Request $request)
    {
        DB::select("UPDATE depedants set related_to = '" . $_POST['dependant_type'] . "', name = '" . $_POST['dependant_first_name'] . "', surname = '" . $_POST['dependant_last_name'] . "', idNumber = '" . $_POST['idNumber'] . "', dob = '" . $_POST['dependant_year'] . "-" . $_POST['dependant_month'] . "-" . $_POST['dependant_day'] . "', gender = '" . $_POST['dependant_gender'] . "', age = '" . $_POST['dependant_age'] . "' where id = '" . $_POST['dependant_id'] . "'");
        return redirect()->route('dependantList');
    }
    public function createIncomeForm(Request $request)
    {

        DB::select("INSERT into personal_budget values (null, '" . $_POST['salary1'] . "', '" . $_POST['salary2'] . "', '" . $_POST['rent_income'] . "', '" . $_POST['acounting_fees'] . "', '" . $_POST['accounts'] . "', '" . $_POST['bank_charges'] . "', '" . $_POST['rentIncome'] . "', '" . $_POST['rent'] . "', '" . $_POST['cell_phone'] . "', '" . $_POST['chemist'] . "', '" . $_POST['Cleaning_Service'] . "', '" . $_POST['clothing'] . "', '" . $_POST['credit_car'] . "', '" . $_POST['credit_card'] . "', '" . $_POST['dstv'] . "', '" . $_POST['entertainment'] . "', '" . $_POST['groceries'] . "', '" . $_POST['funeral_policies'] . "', '" . $_POST['garden_maintenance'] . "', '" . $_POST['garden_service'] . "', '" . $_POST['holidays'] . "')");
        $request->session()->flash('success', 'Income/Personal Budget Created Successfully');
        return redirect()->back();
    }

    public function createPersonalInfoForm(Request $request)
    {
        DB::select("INSERT into personal_details values (null, '" . $_POST['title'] . "', '" . $_POST['fname'] . "', '" . $_POST['sname'] . "', '" . $_POST['mname'] . "', '" . $_POST['nname'] . "', '" . $_POST['idNumber'] . "', '" . $_POST['gender'] . "', '" . $_POST['nationality'] . "', '" . $_POST['cob'] . "', '" . $_POST['pob'] . "', '" . $_POST['hl'] . "', '" . $_POST['ms'] . "', '" . $_POST['dom'] . "', '" . $_POST['qualification'] . "', '" . $_POST['employer'] . "', '" . $_POST['tax_number'] . "', '" . $_POST['gms'] . "', '', '', '" . $_POST['smoker_status'] . "', '" . $_POST['hobbies'] . "', '" . $_POST['title1'] . "', '" . $_POST['fname1'] . "', '" . $_POST['sname1'] . "', '" . $_POST['mname1'] . "', '" . $_POST['nname1'] . "', '" . $_POST['idNumber1'] . "', , '" . $_POST['gender1'] . "''" . $_POST['nationality1'] . "', '" . $_POST['cob1'] . "', '" . $_POST['pob1'] . "', '" . $_POST['hl1'] . "', '" . $_POST['ms1'] . "', '" . $_POST['dom1'] . "', '" . $_POST['qualification1'] . "', '" . $_POST['employer1'] . "', '" . $_POST['tax_number1'] . "', '" . $_POST['gms1'] . "', '', '', '" . $_POST['smoker_status1'] . "', '" . $_POST['hobbies1'] . "')");
        $request->session()->flash('success', 'Personal Information Created Successfully');
        return redirect()->back();
    }

    public function createClientInfoForm(Request $request)
    {
        session_start();
        $userId = $_SESSION['userId'];
        DB::select("INSERT into clients values (null, '$userId', 'Primary Cloud', " . $_POST['title'] . "', " . $_POST['fname'] . "', '" . $_POST['sname'] . "', '" . $_POST['mname'] . "', '" . $_POST['nname'] . "', '" . $_POST['idNumber'] . "', '" . $_POST['gender'] . "', '" . $_POST['nationality'] . "', '" . $_POST['cob'] . "', '" . $_POST['pob'] . "', '" . $_POST['hl'] . "', '" . $_POST['ms'] . "', '" . $_POST['dom'] . "', '" . $_POST['qualification'] . "', '" . $_POST['employer'] . "', '" . $_POST['tax_number'] . "', '" . $_POST['gms'] . "', '" . $_POST['smoker_status'] . "', '" . $_POST['hobbies'] . "')");
        DB::select("INSERT into clients values (null, '$userId', 'Spouse', " . $_POST['title1'] . "', " . $_POST['fname1'] . "', '" . $_POST['sname1'] . "', '" . $_POST['mname1'] . "', '" . $_POST['nname1'] . "', '" . $_POST['idNumber1'] . "', '" . $_POST['gender1'] . "', '" . $_POST['nationality1'] . "', '" . $_POST['cob1'] . "', '" . $_POST['pob1'] . "', '" . $_POST['hl1'] . "', '" . $_POST['ms1'] . "', '" . $_POST['dom1'] . "', '" . $_POST['qualification1'] . "', '" . $_POST['employer1'] . "', '" . $_POST['tax_number1'] . "', '" . $_POST['gms1'] . "', '" . $_POST['smoker_status1'] . "', '" . $_POST['hobbies1'] . "')");
        $request->session()->flash('success', 'Personal Information Created Successfully');
        return redirect()->back();
    }

    public function updatePersonalInfoForm(Request $request)
    {
        DB::select("update personal_details set title = '" . $_POST['title'] . "', fname = '" . $_POST['fname'] . "', sname = '" . $_POST['sname'] . "', mname = '" . $_POST['mname'] . "', nname = '" . $_POST['nname'] . "', idNumber = '" . $_POST['idNumber'] . "', gender = '" . $_POST['gender'] . "',nationality = '" . $_POST['nationality'] . "', cob = '" . $_POST['cob'] . "', pob = '" . $_POST['pob'] . "', hl = '" . $_POST['hl'] . "', hl = '" . $_POST['ms'] . "', hl = '" . $_POST['dom'] . "', qualification = '" . $_POST['qualification'] . "', employer = '" . $_POST['employer'] . "', tax_number = '" . $_POST['tax_number'] . "', gms = '" . $_POST['gms'] . "', smoker_status = '" . $_POST['smoker_status'] . "', hobbies = '" . $_POST['hobbies'] . "', title1 = '" . $_POST['title1'] . "', fname1 = '" . $_POST['fname1'] . "', sname1 = '" . $_POST['sname1'] . "', mname1 = '" . $_POST['mname1'] . "', nname1 = '" . $_POST['nname1'] . "', idNumber1 = '" . $_POST['idNumber1'] . "', gender1 = '" . $_POST['gender1'] . "', nationality1 = '" . $_POST['nationality1'] . "', cob1 = '" . $_POST['cob1'] . "', pob1 = '" . $_POST['pob1'] . "', hl1 = '" . $_POST['hl1'] . "', ms1 = '" . $_POST['ms1'] . "', dom1 = '" . $_POST['dom1'] . "', qualification1 = '" . $_POST['qualification1'] . "', employer1 = '" . $_POST['employer1'] . "', tax_number1 = '" . $_POST['tax_number1'] . "', gms1 = '" . $_POST['gms1'] . "', smoker_status1 = '" . $_POST['smoker_status1'] . "', hobbies1 = '" . $_POST['hobbies1'] . "' where id = '" . $_POST['personal_details_id'] . "' ");
        $request->session()->flash('success', 'Personal Information Updated Successfully');
        return redirect()->back();
    }

    public function updateIncomeForm(Request $request)
    {
        //var_dump($_POST); die();
        DB::select("UPDATE  personal_budget set salary1 = '" . $_POST['salary1'] . "', salary2 = '" . $_POST['salary2'] . "', rent_income = '" . $_POST['rent_income'] . "', acounting_fees = '" . $_POST['acounting_fees'] . "', accounts = '" . $_POST['accounts'] . "', bank_charges = '" . $_POST['bank_charges'] . "', rentIncome = '" . $_POST['rentIncome'] . "', rent = '" . $_POST['rent'] . "', cell_phone = '" . $_POST['cell_phone'] . "', chemist = '" . $_POST['chemist'] . "', Cleaning_Service = '" . $_POST['Cleaning_Service'] . "', clothing = '" . $_POST['clothing'] . "', credit_car = '" . $_POST['credit_car'] . "', credit_card = '" . $_POST['credit_card'] . "', dstv = '" . $_POST['dstv'] . "', entertainment = '" . $_POST['entertainment'] . "', groceries = '" . $_POST['groceries'] . "', funeral_policies = '" . $_POST['funeral_policies'] . "', garden_maintenance = '" . $_POST['garden_maintenance'] . "', garden_service = '" . $_POST['garden_service'] . "', holidays = '" . $_POST['holidays'] . "' where id = '" . $_POST['income_id'] . "' ");
        $request->session()->flash('success', 'Income/Personal Budget Updated Successfully');
        return redirect()->back();
    }
    public function createLiabilityForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $count = count($_POST["desc"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into liabilities values (null, '" . $_POST['lt'][$i] . "', '" . $_POST['desc'][$i] . "', '" . $_POST['oda'][$i] . "', '" . $_POST['coa'][$i] . "', '" . $_POST['ia'][$i] . "', '" . $_POST['mi'][$i] . "', '" . $_POST['dodi'][$i] . "', '" . $_POST['o'][$i] . "')");
        }
        return redirect()->route('liabilities');
    }
    public function createLiability()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.createLiability', ['owners' => $owners, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function logout()
    {
        session_start();
        unset($_SESSION['login']);
        session_destroy();
        session_unset();
        Session::flush();
        //var_dump($_SESSION); die();
        header("location: https://fna2.phpapplord.co.za/public/");
        exit;
    }

    public function objectives()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $Module = 'Risk Objectives';
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

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $objectives = DB::select("SELECT * FROM `risk_objectives_detaills`");

        // dd($personalBudgetModuleAclAccessIdModuleIdAclAccess);
        return view('fna.objectives', ['getroleId' => $getroleId, 'objectives' => $objectives, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess]);
    }
    public function retirementObjectives()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $Module = 'Retirement Risks Objectives';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "' ");
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
        $objectives = DB::select("SELECT * FROM `retirement_objectives`");
        return view('fna.retirementObjectives', ['getroleId' => $getroleId, 'objectives' => $objectives]);
    }
    public function createObjectives()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $Module = 'Risk Objectives';
        $getModuleId = DB::select("SELECT * FROM `modules` where name = '$Module'");
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "' ");
        $roleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "' ");
        $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $roleId[0]->groupId . "' and moduleId = '" . $getModuleId[0]->id . "'");
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


        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {

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

                /*  
                Get Retirement Access For Menu 
            */
                $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
                if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                    if (!isset($getretirementAccessName[0]->name)) {
                        $getretirementRiskAclAccess = "noAccess";
                    } else {
                        $getretirementRiskAclAccess = "Access";
                    }
                }

                /*  
                Get Risk Access For Menu
            */
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


                /*  
                Get Dependant Access For Menu
            */
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


                /*  
                Get Asset Module Access For Menu
            */
                $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
                if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                    if (!isset($assetsModuleAccessName[0]->name)) {
                        $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    } else {
                        $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }


                /*  
                Get Liabilities Module Access For Menu
            */
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

                /*  
                Get Personal Budjet Module Access For Menu
            */
                $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
                if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {

                    $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                    if (!isset($personalBudgetModuleAccessName[0]->name)) {
                        $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                    } else {
                        $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                    }
                }

                /*  
                Get Personal Information Module Access For Menu
            */
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
        }
        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.createObjectives', ['getroleId' => $getroleId, 'owners' => $owners, 'getAccessName' => $getAccessName, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function createRetirementObjectives()
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.createRetirementObjectives', ['owners' => $owners,  'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function createObjectivesForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $objective_id = DB::table('risk_objectives_detaills')->insertGetId([
            'id' => null,
            'dread' => $_POST['dread'],
            'notes' => $_POST['notes'],
            'affordability' => $_POST['affordability'],
            'sp_to_quote' => $_POST['sp_to_quote']
        ]);
        $count = count($_POST["objective_for"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into risk_objectives values (null, '1', $objective_id,'" . $_POST['objective_for'][$i] . "', '" . $_POST['lump_sum'][$i] . "', '" . $_POST['monthly'][$i] . "', '" . $_POST['until_age'][$i] . "', '" . $_POST['objective_type'][$i] . "')");
        }
        return redirect()->route('objectives');
    }


    public function createRetirementObjectivesForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $objective_id = DB::table('retirement_objectives')->insertGetId([
            'id' => null,
            'retirement_age' => $_POST['age'],
            'other_income' => $_POST['o_income'],
            'retirement_income' => $_POST['r_income'],
            'received_rental' => $_POST['r_rental'],
            'fsp_to_quote' => $_POST['fsp_to_quote'],
            'product' => $_POST['product']
        ]);
        $count = count($_POST["other_expense_name"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into retirement_objectives_expenses values (null, $objective_id,'" . $_POST['other_expense_name'][$i] . "', '" . $_POST['other_expense_amount'][$i] . "')");
        }
        return redirect()->route('retirementObjectives');
    }

    public function updateObjectivesForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $objective_id = DB::select("UPDATE risk_objectives_detaills set dread = '" . $_POST['dread'] . "',  notes = '" . $_POST['notes'] . "', affordability = '" . $_POST['affordability'] . "', sp_to_quote = '" . $_POST['sp_to_quote'] . "' where id = '" . $_POST['objective_id'] . "'");
        $count = count($_POST["objective_for"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into risk_objectives values (null, '1', '" . $_POST['objective_id'] . "','" . $_POST['objective_for'][$i] . "', '" . $_POST['lump_sum'][$i] . "', '" . $_POST['monthly'][$i] . "', '" . $_POST['until_age'][$i] . "', '" . $_POST['objective_type'][$i] . "')");
        }
        return redirect()->route('objectives');
    }
    public function updateRetirementObjectivesForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $objective_id = DB::select("UPDATE retirement_objectives set retirement_age = '" . $_POST['age'] . "',  other_income = '" . $_POST['o_income'] . "', retirement_income = '" . $_POST['r_income'] . "', received_rental = '" . $_POST['r_rental'] . "', fsp_to_quote = '" . $_POST['fsp_to_quote'] . "', product = '" . $_POST['product'] . "' where id = '" . $_POST['objective_id'] . "'");
        $count = count($_POST["other_expense_name"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into retirement_objectives_expenses values (null, $objective_id,'" . $_POST['other_expense_name'][$i] . "', '" . $_POST['other_expense_amount'][$i] . "')");
        }
        return redirect()->route('retirementObjectives');
    }
    public function editRiskObjective($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $risk_objectives = DB::select("SELECT * FROM `risk_objectives` where objective_id = '$id' ");
        $risk_objectives_details = DB::select("SELECT * FROM `risk_objectives_detaills` where id = '$id' ");
        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.editRiskObjective', ['risk_objectives' => $risk_objectives, 'risk_objectives_details' => $risk_objectives_details, 'owners' => $owners, 'id' => $id, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function editRetirementObjective($id)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }

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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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

        $retirement_objectives_expenses = DB::select("SELECT * FROM `retirement_objectives_expenses` where retirement_objectives_id = '$id' ");
        $retirement_objectives = DB::select("SELECT * FROM `retirement_objectives` where id = '$id' ");
        $owners = DB::select("SELECT * FROM `personal_details`");
        return view('fna.editRetirementObjective', ['retirement_objectives_expenses' => $retirement_objectives_expenses, 'retirement_objectives' => $retirement_objectives, 'owners' => $owners, 'id' => $id, 'getroleId' => $getroleId, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }
    public function deleteObjectives($id)
    {
        DB::select("DELETE FROM `risk_objectives` where objective_id = '$id' ");
        DB::select("DELETE FROM `risk_objectives_detaills` where id = '$id' ");
        return redirect()->route('objectives');
    }
    public function deleteDependant($id)
    {
        DB::select("DELETE FROM `depedants` where id = '$id' ");
        return redirect()->route('dependantList');
    }

    public function deleteRetirementObjectives($id)
    {
        DB::select("DELETE FROM `retirement_objectives_expenses` where retirement_objectives_id = '$id' ");
        DB::select("DELETE FROM `retirement_objectives` where id = '$id' ");
        return redirect()->route('retirementObjectives');
    }
    public function updateAsset(Request $request)
    {
        // var_dump($_POST);die();
        DB::select("UPDATE assets set type = '" . $_POST['at'] . "', description = '" . $_POST['desc'] . "', market_value = '" . $_POST['cmv'] . "', purchase_value = '" . $_POST['opp'] . "', improve = '" . $_POST['ai'] . "', income_receive = '" . $_POST['IR'] . "', date = '" . $_POST['DP'] . "', owners =  '" . $_POST['o'] . "' where id = '" . $_POST['asset_id'] . "'");
        return redirect()->route('assetList');
    }

    public function updateLiability(Request $request)
    {
        // var_dump($_POST);die();
        DB::select("UPDATE liabilities set type = '" . $_POST['lt'] . "', description = '" . $_POST['desc'] . "', debt_amount = '" . $_POST['oda'] . "', outstanding_amount = '" . $_POST['coa'] . "', interest_rate = '" . $_POST['ir'] . "', installment = '" . $_POST['IR'] . "', date = '" . $_POST['DP'] . "', owners =  '" . $_POST['o'] . "' where id = '" . $_POST['liability_id'] . "'");
        return redirect()->route('liabilities');
    }
    public function deleteAssetitem($id)
    {

        DB::select("DELETE FROM `assets` where id = '$id' ");
        return redirect()->route('updateAssets', ['id' => $id]);
    }
    public function deleteRiskObjectivesItem($id, $objectiveId)
    {
        //echo "SELECT * FROM `risk_objectives` where id = '$id'"; die();
        DB::select("DELETE FROM `risk_objectives` where id = '$id' ");
        $risk_objectives_details = DB::select("SELECT * FROM `risk_objectives` where objective_id = '$objectiveId' ");
        //var_dump($risk_objectives_details); die();
        if (empty($risk_objectives_details)) {
            DB::select("DELETE FROM `risk_objectives_detaills` where id = '$objectiveId' ");
            return redirect()->route('objectives');
        }
        return redirect()->route('editRiskObjective', ['id' => $objectiveId]);
    }
    public function deleteRetirementObjectivesItem($id, $objectiveId)
    {
        //echo "SELECT * FROM `risk_objectives` where id = '$id'"; die();
        DB::select("DELETE FROM `retirement_objectives_expenses` where id = '$id' ");
        $retirement_objectives_expenses = DB::select("SELECT * FROM `retirement_objectives_expenses` where retirement_objectives_id = '$objectiveId' ");
        //var_dump($risk_objectives_details); die();
        if (empty($retirement_objectives_expenses)) {
            DB::select("DELETE FROM `retirement_objectives` where id = '$objectiveId' ");
            return redirect()->route('retirementObjectives');
        }
        return redirect()->route('editRetirementObjective', ['id' => $objectiveId]);
    }
    public function delete($id)
    {

        DB::select("DELETE FROM `assets` where id = '$id' ");
        return redirect()->route('assetList');
    }
    public function deleteLiability($id)
    {
        DB::select("DELETE FROM `liabilities` where id = '$id' ");
        return redirect()->route('liabilities');
    }

    public function createClientAssets()
    {
        // $client_assets = DB::select("SELECT * FROM `client_assets`");
        // return view('fna.clientAssestList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        $client_assets = DB::select("SELECT * FROM `client_assets`");
        return view('fna.createClientAssets', ['getroleId' => $getroleId, 'client_assets' => $client_assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function clientAssetsList()
    {
        // $client_assets = DB::select("SELECT * FROM `client_assets`");
        // return view('fna.clientAssestList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
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
        $getroleId = DB::select("SELECT * FROM `permissions` where userId = '" . $userId . "'");
        if (!isset($getroleId[0]->groupId)) {
            header("location: https://fna2.phpapplord.co.za/public/noAccess");
            exit;
        } else {
            /*
                Get Access Id to get Read/write access or Access Name
            */
            $getAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
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

            /*  
                Get Retirement Access For Menu 
            */
            $getretirementRiskAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $retirementRiskModuleModuleId[0]->id . "'");
            if (!isset($getretirementRiskAclAccessId[0]->accessId)) {
                $getretirementRiskAclAccess = "noAccess";
            } else {
                $getretirementAccessName = DB::select("SELECT * FROM `access` where id = '" . @$getretirementRiskAclAccessId[0]->accessId . "'");
                if (!isset($getretirementAccessName[0]->name)) {
                    $getretirementRiskAclAccess = "noAccess";
                } else {
                    $getretirementRiskAclAccess = "Access";
                }
            }

            /*  
                Get Risk Access For Menu
            */
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


            /*  
                Get Dependant Access For Menu
            */
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


            /*  
                Get Asset Module Access For Menu
            */
            $assetsModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $assetsModuleModuleId[0]->id . "'");
            if (!isset($assetsModuleAclAccessId[0]->accessId)) {
                $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {
                $assetsModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$assetsModuleAclAccessId[0]->accessId . "'");
                if (!isset($assetsModuleAccessName[0]->name)) {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $assetsModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }


            /*  
                Get Liabilities Module Access For Menu
            */
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

            /*  
                Get Personal Budjet Module Access For Menu
            */
            $personalBudgetModuleAclAccessId = DB::select("SELECT * FROM `acl` where roleId = '" . $getroleId[0]->groupId . "' and moduleId = '" . $personalBudgetModuleModuleModuleId[0]->id . "'");
            if (!isset($personalBudgetModuleAclAccessId[0]->accessId)) {
                $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
            } else {

                $personalBudgetModuleAccessName = DB::select("SELECT * FROM `access` where id = '" . @$personalBudgetModuleAclAccessId[0]->accessId . "'");
                if (!isset($personalBudgetModuleAccessName[0]->name)) {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "noAccess";
                } else {
                    $personalBudgetModuleAclAccessIdModuleIdAclAccess = "Access";
                }
            }

            /*  
                Get Personal Information Module Access For Menu
            */
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
        // $assets = DB::select("SELECT * FROM `assets`");
        // return view('fna.assetList', ['getroleId' => $getroleId, 'assets' => $assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess'=>$personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess'=>$personalBudgetModuleAclAccessIdModuleIdAclAccess,'getRiskModuleModuleIdAclAccess'=> $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess'=>$dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess'=>$getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess'=>$assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess'=>$liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName'=>$getAccessName]);
        $client_assets = DB::select("SELECT * FROM `client_assets`");
        return view('fna.clientAssetsList', ['getroleId' => $getroleId, 'client_assets' => $client_assets, 'personalInfoModuleAclAccessIdModuleIdAclAccess' => $personalInfoModuleAclAccessIdModuleIdAclAccess, '$getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'personalBudgetModuleAclAccessIdModuleIdAclAccess' => $personalBudgetModuleAclAccessIdModuleIdAclAccess, 'getRiskModuleModuleIdAclAccess' => $getRiskModuleModuleIdAclAccess, 'dependantsModuleModuleIdAclAccess' => $dependantsModuleModuleIdAclAccess, 'getretirementRiskAclAccess' => $getretirementRiskAclAccess, 'assetsModuleAclAccessIdModuleIdAclAccess' => $assetsModuleAclAccessIdModuleIdAclAccess, 'liabilitiesModuleAclAccessIdModuleIdAclAccess' => $liabilitiesModuleAclAccessIdModuleIdAclAccess, 'getAccessName' => $getAccessName]);
    }

    public function createClientAssetsForm(Request $request)
    {
        session_start();
        if (empty($_SESSION['login'])) {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $count = count($_POST["desc"]);
        for ($i = 0; $i < $count; $i++) {
            DB::select("INSERT into `client_assets` values (null, '" . $_POST['lt'][$i] . "', '" . $_POST['desc'][$i] . "', '" . $_POST['oda'][$i] . "', '" . $_POST['coa'][$i] . "', '" . $_POST['ia'][$i] . "', '" . $_POST['mi'][$i] . "', '" . $_POST['dodi'][$i] . "', '" . $_POST['o'][$i] . "')");
        }
        return redirect()->route('clientAssetsList');
    }

    public function updateClientAssets(Request $request)
    {
        // var_dump($_POST);die();
        DB::select("UPDATE `client_assets` set type = '" . $_POST['lt'] . "', description = '" . $_POST['desc'] . "', debt_amount = '" . $_POST['oda'] . "', outstanding_amount = '" . $_POST['coa'] . "', interest_rate = '" . $_POST['ir'] . "', installment = '" . $_POST['IR'] . "', date = '" . $_POST['DP'] . "', owners =  '" . $_POST['o'] . "' where id = '" . $_POST['liability_id'] . "'");
        return redirect()->route('clientAssetsList');
    }

    public function deleteClientAssets($id)
    {
        DB::select("DELETE FROM `client_assets` where id = '$id' ");
        return redirect()->route('clientAssetsList');
    }
}
