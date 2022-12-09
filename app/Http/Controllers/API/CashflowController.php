<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class CashflowController extends Controller
{

    public function updateClientTransaction(Request $request) {

        $transaction_id = $request->id;
        $category_id = $request->item_id;
        $transaction_name = $request->item_name;
        $transaction_value = $request->value;

        $category = DB::table('income_expense_type')->where('id', $category_id)->first();

        DB::table('budget')->where('id', $transaction_id)->update([
            'item_type_id' => $category->id,
            'item_id' => $category->id,
            'item_name' => $category->income_expense_name,
            'item_type' => $transaction_name,
            'item_value' => $transaction_value
        ]);

        DB::table('yearly_budget')->where('id', $transaction_id)->update([
            'item_type_id' => $category->id,
            'item_id' => $category->id,
            'item_name' => $category->income_expense_name,
            'item_type' => $transaction_name,
            'item_value' => $transaction_value
        ]);


        return response()->json(['status' => 'success'], 201);
    }

    public function deleteClientTransactions(Request $request) {
        

        foreach($request->items as $item)
        {

            DB::table('budget')->where('id', $item)->where('client_id', $request->client_id)->delete();

            DB::table('yearly_budget')->where('budget_id', $item)->where('client_id', $request->client_id)->delete();

        }

        return response()->json(['status' => 'success'],201);

    }

    public function addClientExpense(Request $request) {

        $client = DB::table('clients')->where('id', $request->client_id)->first();

        $expenses = $request->expenses;

        foreach($expenses as $expense)
        {

            $budget_id = DB::table('budget')->insert([
                'client_reference_id' => $client->client_reference_id,
                'advisor_id' => $client->advisor_id,
                'client_id' => $client->id,
                'client_type' => $client->client_type,
                'advisor_capture_id' => $client->advisor_id,
                'income_expenses_type' => 2,
                'item_type_id' => $expense['item_id'],
                'item_type' => $expense['item_name'],
                'item_id' => $expense['item_id'],
                'item_name' => DB::table('income_expense_type')->where('id', $expense['item_id'])->first()->income_expense_name,
                'item_value' => $expense['item_value'],
                'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$request->month.$request->year 00:00:00")
            ]);
            
            DB::table('yearly_budget')->insert([
                'client_reference_id' => $client->client_reference_id,
                'advisor_id' => $client->advisor_id,
                'advisor_capture_id' => $client->advisor_id,
                'budget_id' => $budget_id,
                'client_id' => $client->id,
                'client_type' => $client->client_type,
                'income_expenses_type' => 2,
                'item_type_id' => $expense['item_id'],
                'item_type' => $expense['item_name'],
                'item_id' => $expense['item_id'],
                'item_name' => DB::table('income_expense_type')->where('id', $expense['item_id'])->first()->income_expense_name,
                'item_value' => $expense['item_value'],
                'month' => $request->month,
                'year' => $request->year,
                'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$request->month.$request->year 00:00:00")
            ]);
        }

        return response()->json(['status' => 'success'],201);

    }

    public function addClientIncome(Request $request) {

        $client = DB::table('clients')->where('id', $request->client_id)->first();

        $incomes = $request->incomes;

        
        foreach($incomes as $income)
        {

            $budget_id = DB::table('budget')->insert([
                'client_reference_id' => $client->client_reference_id,
                'advisor_id' => $client->advisor_id,
                'client_id' => $client->id,
                'client_type' => $client->client_type,
                'advisor_capture_id' => $client->advisor_id,
                'income_expenses_type' => 1,
                'item_type_id' => $income['item_id'],
                'item_type' => $income['item_name'],
                'item_id' => $income['item_id'],
                'item_name' => DB::table('income_expense_type')->where('id', $income['item_id'])->first()->income_expense_name,
                'item_value' => $income['item_value'],
                'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$request->month.$request->year 00:00:00")
            ]);
            
            DB::table('yearly_budget')->insert([
                'client_reference_id' => $client->client_reference_id,
                'advisor_id' => $client->advisor_id,
                'advisor_capture_id' => $client->advisor_id,
                'budget_id' => $budget_id,
                'client_id' => $client->id,
                'client_type' => $client->client_type,
                'income_expenses_type' => 1,
                'item_type_id' => $income['item_id'],
                'item_type' => $income['item_name'],
                'item_id' => $income['item_id'],
                'item_name' => DB::table('income_expense_type')->where('id', $income['item_id'])->first()->income_expense_name,
                'item_value' => $income['item_value'],
                'month' => $request->month,
                'year' => $request->year,
                'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$request->month.$request->year 00:00:00")
            ]);
        }

        return response()->json(['status' => 'success'],201);

    }

    public function getClients($client_reference_id, $month, $year) {
        $income_categories = DB::table('income_expense_type')->where('income_expense_type', 1)->get();
        $expense_categories = DB::table('income_expense_type')->where('income_expense_type', 2)->get();


        $clients = DB::table('clients')
                        ->where('client_reference_id', $client_reference_id)
                        ->get()
                        ->take(2)
                        ->map(function($client) use($month, $year, $income_categories, $expense_categories){
                            $client->incomes = DB::table('budget')
                                                ->where('client_id', $client->id)
                                                ->where('income_expenses_type', 1)
                                                ->orderBy('id', 'desc')
                                                ->get()
                                                ->filter(function($income) use($month, $year){

                                                    $capture_date = explode(' ', $income->capture_date)[0];

                                                    if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
                                                    {
                                                        return $income;
                                                    }

                                                    return false;
                                                                                                   
                                                })
                                                ->values()
                                                ->map(function($income) {
                                                        $data['id'] = $income->id;
                                                        $data['item_name'] = $income->item_name;
                                                        $data['item_type'] = $income->item_type;
                                                        $data['item_id'] = $income->item_id;
                                                        $data['item_value'] = $income->item_value;
                                                        return $data;
                                                });

                            $client->incomes = $client->incomes->unique('item_name')->map(function($unique_income) use($client){
                                $unique_income['transactions'] = $client->incomes->where('item_name', $unique_income['item_name'])->values();
                                $unique_income['item_value'] = $client->incomes->where('item_name', $unique_income['item_name'])->sum('item_value');
                    
                                return $unique_income;
                            });

                          

                            $diffIncomes = $income_categories->filter(function($income_category) use($client) {
                   
                                if($client->incomes->pluck('item_name')->contains($income_category->income_expense_name))
                                {
                                    return false;
                                }

                                return true;

                            })->values()->map(function($income) {
                                $data['item_name'] = $income->income_expense_name;
                                $data['item_type'] = "";
                                $data['item_id'] = $income->id;
                                $data['item_value'] = 0.00;
                                $data['transactions'] = [];

                                return $data;
                            })->take(count($client->incomes) - 10);


                            $client->incomes = $client->incomes->merge($diffIncomes);
                                                       

                            $client->expenses = DB::table('budget')
                            ->where('client_id', $client->id)
                            ->where('income_expenses_type', 2)
                            ->orderBy('id', 'desc')
                            ->get()
                            ->filter(function($expense) use($month, $year){

                                $capture_date = explode(' ', $expense->capture_date)[0];

                                if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
                                {
                                    return $expense;
                                }
                            })
                            ->values()
                            ->map(function($expense) {
                                $data['id'] = $expense->id;
                                $data['item_name'] = $expense->item_name;
                                $data['item_type'] = $expense->item_type;
                                $data['item_id'] = $expense->item_id;
                                $data['item_value'] = $expense->item_value;
                                return $data;

                            });

                            $client->expenses = $client->expenses->unique('item_name')->map(function($unique_expense) use($client){
                                $unique_expense['transactions'] = $client->expenses->where('item_name', $unique_expense['item_name'])->values();
                                $unique_expense['item_value'] = $client->expenses->where('item_name', $unique_expense['item_name'])->sum('item_value');
                    
                                return $unique_expense;
                            });

                            $diffExpenses = $expense_categories->filter(function($expense_category) use($client) {
                   
                                if($client->expenses->pluck('item_name')->contains($expense_category->income_expense_name))
                                {
                                    return false;
                                }

                                return true;

                            })->values()->map(function($expense) {
                                $data['item_name'] = $expense->income_expense_name;
                                $data['item_type'] = "";
                                $data['item_id'] = $expense->id;
                                $data['item_value'] = 0.00;
                                $data['transactions'] = [];

                                return $data;
                            })->take(count($client->expenses) - 10);

                            $client->expenses = $client->expenses->merge($diffExpenses);

                            return $client;
                        });


        return response()->json(['clients' => $clients],201);
    }
    // public function getClients($client_reference_id, $month, $year) {

    //     $clients = DB::table('clients')
    //                     ->where('client_reference_id', $client_reference_id)
    //                     ->get()
    //                     ->take(2)
    //                     ->map(function($client) use($month, $year){
    //                         $client->incomes = DB::table('budget')
    //                                             ->where('client_id', $client->id)
    //                                             ->where('income_expenses_type', 1)
    //                                             ->get()
    //                                             ->filter(function($income) use($month, $year){

    //                                                 $capture_date = explode(' ', $income->capture_date)[0];

    //                                                 if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
    //                                                 {
    //                                                     return $income;
    //                                                 }

    //                                                 return false;
                                                                                                   
    //                                             })
    //                                             ->values()
    //                                             ->map(function($income) {
    //                                                   $data['id'] = $income->id;
    //                                                     $data['item_name'] = $income->item_name;
    //                                                     $data['item_type'] = $income->item_type;
    //                                                     $data['item_id'] = $income->item_id;
    //                                                     $data['item_value'] = $income->item_value;
    //                                                     $data['is_stored'] = true;
    //                                                     return $data;
    //                                             });

    //                         $client->incomes = $client->incomes->unique('item_name')->map(function($unique_income) use($client){
    //                             $unique_income['transactions'] = $client->incomes->where('item_name', $unique_income['item_name']);
    //                             $unique_income['item_value'] = $client->incomes->where('item_name', $unique_income['item_name'])->sum('item_value');
                    
    //                             return $unique_income;
    //                         })->values();

    //                         $client->expenses = DB::table('budget')
    //                         ->where('client_id', $client->id)
    //                         ->where('income_expenses_type', 2)
    //                         ->get()
    //                         ->filter(function($expense) use($month, $year){

    //                             $capture_date = explode(' ', $expense->capture_date)[0];

    //                             if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
    //                             {
    //                                 return $expense;
    //                             }
    //                         })
    //                         ->values()
    //                         ->map(function($expense) {

    //                             $data['id'] = $expense->id;
    //                             $data['item_name'] = $expense->item_name;
    //                             $data['item_type'] = $expense->item_type;
    //                             $data['item_id'] = $expense->item_id;
    //                             $data['item_value'] = $expense->item_value;
    //                             $data['is_stored'] = true;
    //                             return $data;

    //                         });

    //                         $client->expenses = $client->expenses->unique('item_name')->map(function($unique_expense) use($client){
    //                             $unique_expense['transactions'] = $client->expenses->where('item_name', $unique_expense['item_name']);
    //                             $unique_expense['item_value'] = $client->expenses->where('item_name', $unique_expense['item_name'])->sum('item_value');
                    
    //                             return $unique_expense;
    //                         })->values();

    //                         return $client;
    //                     });


    //     return response()->json(['clients' => $clients],201);
    // }

    public function getCashflowCategories() {
        $income_types = DB::table('income_expense_type')->where('income_expense_type', 1)->get();
        $expense_types = DB::table('income_expense_type')->where('income_expense_type', 2)->get();

        return response()->json(['income_types' => $income_types, 'expense_types' => $expense_types], 201);
    }

    public function getCashflowNames() {

        $income_names = DB::select('SELECT income_expense_type_items.id, income_expense_type_items.income_expense_id, income_expense_type_items.item_name 
                                    FROM `income_expense_type` 
                                    LEFT JOIN `income_expense_type_items` ON income_expense_type.id = income_expense_type_items.income_expense_id 
                                    WHERE `income_expense_type` = 1 
                                    ORDER BY `income_expense_name` DESC');
                                    
        $expense_names = DB::select('SELECT income_expense_type_items.id, income_expense_type_items.income_expense_id, income_expense_type_items.item_name 
                                    FROM `income_expense_type` 
                                    LEFT JOIN `income_expense_type_items` ON income_expense_type.id = income_expense_type_items.income_expense_id 
                                    WHERE `income_expense_type` = 2 
                                    ORDER BY `income_expense_name` DESC');

        return response()->json(['income_names' => $income_names, 'expense_names' => $expense_names], 201);

    }

    public function updateClientCashflow(Request $request, $client_reference_id, $month, $year)
    {
       
        $clients = $request->all();


        foreach($clients as $client)
        {
          
            $clientObj = DB::table('clients')->where('id', $client['id'])->first();

           DB::table('budget')->where('client_id', $client['id'])->get()
                        ->filter(function($budget) use($month, $year){

                             $capture_date = explode(' ', $budget->capture_date)[0];

                            if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
                            {
                                return $budget;
                            }

                        })->each(function($item) {
                            DB::table('budget')->where('id', $item->id)->delete();
                        });
      
            DB::table('yearly_budget')->where('client_id', $client['id'])->get()
            ->filter(function($yearly_budget) use($month, $year){

                    $capture_date = explode(' ', $yearly_budget->capture_date)[0];

                if((explode('-', $capture_date)[0] == $year) && (explode('-', $capture_date)[1] == $month))
                {
                    return $yearly_budget;
                }

            })->each(function($item) {
                DB::table('budget')->where('id', $item->id)->delete();
            });
            
            foreach($client['incomes'] as $income)
            {

                $budget_id = DB::table('budget')->insert([
                    'client_reference_id' => $clientObj->client_reference_id,
                    'advisor_id' => $clientObj->advisor_id,
                    'client_id' => $clientObj->id,
                    'client_type' => $clientObj->client_type,
                    'advisor_capture_id' => $clientObj->advisor_id,
                    'income_expenses_type' => 1,
                    'item_type_id' => $income['item_id'],
                    'item_type' => DB::table('income_expense_type')->where('id', $income['item_id'])->first()->income_expense_name,
                    'item_id' => $income['item_id'],
                    'item_name' => $income['item_name'],
                    'item_value' => $income['item_value'],
                    'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$month.$year 00:00:00")
                ]);
                
                DB::table('yearly_budget')->insert([
                    'client_reference_id' => $clientObj->client_reference_id,
                    'advisor_id' => $clientObj->advisor_id,
                    'advisor_capture_id' => $clientObj->advisor_id,
                    'budget_id' => $budget_id,
                    'client_id' => $clientObj->id,
                    'client_type' => $clientObj->client_type,
                    'income_expenses_type' => 1,
                    'item_type_id' => $income['item_id'],
                    'item_type' => DB::table('income_expense_type')->where('id', $income['item_id'])->first()->income_expense_name,
                    'item_id' => $income['item_id'],
                    'item_name' => $income['item_name'],
                    'item_value' => $income['item_value'],
                    'month' => $month,
                    'year' => $year,
                    'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$month.$year 00:00:00")
                ]);
            }

            foreach($client['expenses'] as $expense)
            {

                $budget_id = DB::table('budget')->insert([
                    'client_reference_id' => $clientObj->client_reference_id,
                    'advisor_id' => $clientObj->advisor_id,
                    'client_id' => $clientObj->id,
                    'client_type' => $clientObj->client_type,
                    'advisor_capture_id' => $clientObj->advisor_id,
                    'income_expenses_type' => 2,
                    'item_type_id' => $expense['item_id'],
                    'item_type' => DB::table('income_expense_type')->where('id', $expense['item_id'])->first()->income_expense_name,
                    'item_id' => $expense['item_id'],
                    'item_name' => $expense['item_name'],
                    'item_value' => $expense['item_value'],
                    'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$month.$year 00:00:00")
                ]);
                
                DB::table('yearly_budget')->insert([
                    'client_reference_id' => $clientObj->client_reference_id,
                    'advisor_id' => $clientObj->advisor_id,
                    'advisor_capture_id' => $clientObj->advisor_id,
                    'budget_id' => $budget_id,
                    'client_id' => $clientObj->id,
                    'client_type' => $clientObj->client_type,
                    'income_expenses_type' => 2,
                    'item_type_id' => $expense['item_id'],
                    'item_type' => DB::table('income_expense_type')->where('id', $expense['item_id'])->first()->income_expense_name,
                    'item_id' => $expense['item_id'],
                    'item_name' => $expense['item_name'],
                    'item_value' => $expense['item_value'],
                    'month' => $month,
                    'year' => $year,
                    'capture_date' => \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', "01.$month.$year 00:00:00")
                ]);
            }
            
        }

        return response()->json(['message' => 'success'], 201);
    }

    public function cpbCreditScore(Request $request)
    {
        $userId = $request->user_id;
        $id_number = $request->id_number;
        $client_type = $request->client_type;
        $client_reference_id = $request->client_reference_id;

        DB::table('credit_score_enquiry')->insert([
            'advisor_id' => $userId,
            'capture_advisor_id' => $userId,
            'client_reference_id' =>  $client_reference_id,
            'client_type' => $client_type,
            'id_number' => $id_number,
            'account_number' => '00001',
            'amount' => '100',
            'date' => Date::now()
        ]);
        
        return response()->json(['status' => 'success'], 201);
    }
}
