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

class ClientLiabilitiesController extends Controller
{
    public function createClientLiabilitiesNew ($client_reference_id , $client_type) {
    //  var_dump('gets here');
    //  dd();
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];

        $client_owners = DB::select("SELECT * FROM clients WHERE client_reference_id = '".$client_reference_id."'");
        
            
        $client_owners_merge = DB::table('clients')
            ->where('client_reference_id', $client_reference_id)
            ->where('client_type','Spouse')
            ->get()
            ->map(function($client) {
                $data['id'] = $client->id;
                $data['first_name'] = $client->first_name;
                $data['last_name'] = $client->last_name;
                $data['type'] = $client->client_type;
                $data['is_type'] = 'Client';
                return $data;
            });
        $client_dependents = DB::table('dependants')
            ->where('client_reference_id', $client_reference_id)
            ->get()
            ->map(function($dependant) {
                $data['id'] = $dependant->id;
                $data['first_name'] = $dependant->first_name;
                $data['last_name'] = $dependant->last_name;
                $data['type'] = $dependant->dependant_type;
                $data['is_type'] = 'Dependant';
                return $data;
        });
        $client_beneficiary = collect($client_owners_merge)->merge(collect($client_dependents));
        
        $liability_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 2)->orderBy('id','DESC')->get();
        $my_assets = DB::table('client_assets')
                        ->where('client_reference_id', '=', $client_reference_id)
                        ->orderBy('id','DESC')
                        ->get(['asset_description','id', 'client_type']);
        $client_expenses  = DB::table('income_expense_type')
                        ->where('income_expense_type', '=', '2')
                        ->orderBy('income_expense_name')
                        ->get();        
    	return view('fna.createClientLiabilitiesNew',[ 
            'client_expenses' => $client_expenses,
    	    'client_assets' => $my_assets,
    	    'liability_types' => $liability_types,
    	    'client_reference_id' => $client_reference_id, 'client_type' => $client_type, 'client_owners' => $client_owners, 'client_beneficiary' => $client_beneficiary]); 
    }
    
    public function storeClientLiablilities(Request $request)
    {
    
        $request->validate([
            // 'liability_type' => 'required',
            // 'liability_sub_type' => 'required',
            'policy_number' => 'required',
            'outstanding_balance' => 'required',
            // 'under_advice' => 'required',
            // 'type_of_business' => 'required',
            // 'original_balance' => 'required',
            // 'loan_application_amount' => 'required',
            //'limit' => 'required|', //numeric',
            // 'principal_repaid' => 'required',
            // 'last_updated_by' => 'required',
            // 'interest_rate_type' => 'required',
            // 'interest_rate_pa' => 'required|numeric',
            
            // 'repayment_amount' => 'required',
            // 'repayment_frequency' => 'required',
            //'select_asset_type' => 'required',
            
            ]);
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $personalInfoModule = "Liabilities Module";
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        // dd($request->all());
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on Liabilities Save page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]); 
        $liabilitiesId = DB::table('client_liabilities_new')->insertGetId(
		[
			'client_type' => $_POST['client_type'],
			'advisor_id' => $_POST['advisor_id'],
			'capture_advisor_id' => $_POST['capture_advisor_id'],
			'client_reference_id' => $_POST['client_reference_id'],
			'liability_type' => isset($_POST['liability_type']) ? $_POST['liability_type'] : "",
			'liability_sub_type' => isset($_POST['liability_sub_type'])  ? $_POST['liability_sub_type'] : "" ,
			'policy_number' => $_POST['policy_number'],
			'outstanding_balance' => intval(preg_replace('/[^\d.]/', '', $_POST['outstanding_balance'])),
			'under_advice' => $_POST['under_advice'],
			'type_of_business' => '-',
			'original_balance' => intval(preg_replace('/[^\d.]/', '', $_POST['original_balance'])),
			'loan_application_amount' => intval(preg_replace('/[^\d.]/', '', $_POST['loan_application_amount'])),
			'thelimit' => intval(preg_replace('/[^\d.]/', '', $_POST['limit'])),
			'principal_repaid' => intval(preg_replace('/[^\d.]/', '', $_POST['principal_repaid'])),
			'last_updated_by' => $_POST['last_updated_by'],
			'interest_rate_type' => $_POST['interest_rate_type'],
			'interest_rate_pa' => $_POST['interest_rate_pa'],
			
			'repayment_amount' => intval(preg_replace('/[^\d.]/', '', $_POST['repayment_amount'])),
			'repayment_frequency' => $_POST['repayment_frequency'],
			//'select_asset_type' => $_POST['select_asset_type']
			 'select_asset_type' => (isset($_POST['select_asset_type']) ? $_POST['select_asset_type'] : '0'),
             'expense_type' => (isset($_POST['expense_type']) ? $_POST['expense_type'] : '0')
		]);
        
        
        if(isset($_POST['asset_owners_id'])){
            $owner_count = count($_POST['asset_owners_id']); 
            if($owner_count >0) {
                    for($i = 0; $i < $owner_count; $i++)
                    {
                        $saveclientAssetsBeneficiary = DB::table('client_liabilities_ownership')->insert([
                        	'owner_id' => $_POST['asset_owners_id'][$i],
                        	'type' => $_POST['owners_item_type'][$i],
                        	'client_reference_id' => $_POST['owners_client_reference_id'][$i],
                        	'liabilities_id' => $liabilitiesId,
                        	'percentage' => $_POST['asset_allocation_owners'][$i]
                        ]);
                    }
             }
        }
        $client_reference_id= $_POST['client_reference_id'];
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
                                    
         $result =  DB::table('client_assets')
                            ->selectRaw('client_assets.id,
                                    client_assets.asset_description,
                                    client_assets.asset_amount,
                                    client_assets.apply_to_event,
                                    asset_liability_types.name,
                                    client_assets.client_type,
                                    client_assets.client_reference_id')
                            ->leftJoin('asset_liability_types', 'asset_type', '=', 'asset_liability_types.id')
                            ->where('client_reference_id', $client_reference_id)
                            ->simplePaginate(10);
        $result2 =  DB::table('client_liabilities_new')
                            ->selectRaw('client_liabilities_new.id,
                                    client_liabilities_new.liability_sub_type,
                                    client_liabilities_new.policy_number,
                                    client_liabilities_new.outstanding_balance,
                                    client_liabilities_new.under_advice,
                                    asset_liability_types.name,
                                    client_liabilities_new.client_type,
                                    client_liabilities_new.client_reference_id')
                            ->leftJoin('asset_liability_types', 'liability_type', '=', 'asset_liability_types.id')
                            ->where('client_reference_id', $client_reference_id)
                            ->simplePaginate(10);
                            
        //$result2 = DB::select("SELECT client_assets_liabilities.id, client_assets_liabilities.item_type, client_assets_liabilities.item_name, client_assets_liabilities.item_value, client_assets_liabilities.date_purchased, client_assets_liabilities.owners_id , concat(clients.first_name, ' ',clients.last_name) as owners_id FROM `client_assets_liabilities` INNER JOIN clients On owners_id = clients.id where client_assets_liabilities.asset_liability_type = '2' and client_assets_liabilities.client_reference_id = '".$client_reference_id."'");
        
        $clientType = DB::select("SELECT client_type from clients where client_reference_id = '". $client_reference_id ."'");
        $client_type= ($clientType[0]->client_type);
        
        
        $client_owners = DB::select("SELECT * FROM `clients` where client_reference_id = '". $client_reference_id ."'");
        $asset_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 1)->orderBy('id','DESC')->get();
        $liability_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 2)->orderBy('id','DESC')->get();
        
        // return view('fna.updateDeleteAssetsLiabilities',[ 'result' => $result,'result2' => $result2, 'client_reference_id' => $client_reference_id, 'client_type' => $client_type , 'getAccessName'=>$getAccessName,'clientLiabilities'=>$client_owners,'client_owners'=>$client_owners])->with($result);
        // return view('fna.updateDeleteAssetsLiabilities',[ 
        //     'asset_types' => $asset_types,
        //     'liability_types' => $liability_types,
        //     'result' => $result,'result2' => $result2,'client_type' => $client_type, 'client_reference_id' => $client_reference_id,'client_owners'=>$client_owners, 'type' => 'liabilities']);
        return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id , 'client_type' => 'Main Client', 'type' => 'liabilities' ]);            
    	//return redirect()->route('clientLiabilitiesList'); 
    }
    
    public function storeClientLiabilitiesNew(Request $request)
    {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $liabilitiesId = DB::table('client_liabilities_new')->insertGetId(
        [
            'client_type' => $_POST['client_type'],
            'advisor_id' => $_POST['advisor_id'],
            'capture_advisor_id' => $_POST['capture_advisor_id'],
            'client_reference_id' => $_POST['client_reference_id'],
            'liability_type' => $_POST['liability_type'],
            'liability_sub_type' => $_POST['liability_sub_type'],
            'policy_number' => $_POST['policy_number'],
            'outstanding_balance' => intval(preg_replace('/[^\d.]/', '', $_POST['outstanding_balance'])),
            'under_advice' => $_POST['under_advice'],
            'type_of_business' => '-',
        ]);
        return redirect()->route('whatamiworth',['client_reference_id' => $_POST['client_reference_id'] , 'client_type' => 'Main Client' ]);
    }
    
public function editLiabilityNew($clientReff, $id){
    session_start();
    if(empty($_SESSION['login']))
    {
        header("location: https://fna2.phpapplord.co.za/public/");
        exit;
    }
    $userId = $_SESSION['userId'];
 
    $liability = DB::select("SELECT * FROM client_liabilities_new  WHERE id = '".$id."' AND client_reference_id = '".$clientReff."'");
    $liabilityOwners = DB::select("SELECT id, first_name, last_name FROM `clients` where client_reference_id=  '".$clientReff."'");
    $owners = DB::select("SELECT * FROM client_liabilities_ownership");
    $query_owner = DB::select("SELECT * FROM client_liabilities_ownership WHERE client_reference_id = '".$clientReff."' AND liabilities_id = '".$id."'");
        // print_r($query_owner);
        $arrOwners = array();
        for($i=0; $i<count($query_owner); $i++) {
            $client_owner = DB::table('clients')
                            ->where('id', $query_owner[$i]->owner_id)
                            ->get()
                            ->map(function($client) {
                                $data['id'] =  $client->id;
                                $data['first_name'] = $client->first_name;
                                $data['last_name'] = $client->last_name;
                                $data['type'] = $client->client_type;
                                $data['percentage'] = $client->client_type;
                                return $data;
                            });
                            
            array_push($arrOwners,$client_owner);
        }
        
       
    
     $client_owners = DB::table('clients')
            ->where('client_reference_id', $clientReff)
            ->get()
            ->map(function($client) {
                $data['id'] = $client->id;
                $data['first_name'] = $client->first_name;
                $data['last_name'] = $client->last_name;
                $data['type'] = $client->client_type;
                $data['is_type'] = 'Client';
                return $data;
            });
            
    $liability_owners = DB::select("SELECT `client_liabilities_ownership`.id, owner_id, liabilities_id, percentage, first_name, last_name , 
        client_type , type FROM `client_liabilities_ownership` 
        left join clients on clients.id = owner_id WHERE client_liabilities_ownership.`client_reference_id`  = '".$clientReff."' AND liabilities_id = '".$id."'");
    $clientOwners = DB::select("SELECT * FROM clients WHERE client_reference_id = '".$clientReff."'"); 
    
    $liability_types = DB::table('asset_liability_types')->where('asset_liability_type', '=', 2)->orderBy('id','DESC')->get();
    $client_liabilities_owners = DB::table('client_liabilities_ownership')->where('liabilities_id', $id)->get();
    
    $my_assets = DB::table('client_assets')
                ->where('client_reference_id', '=', $clientReff)
                ->orderBy('id','DESC')
                ->get(['asset_description','id', 'client_type']);
    $client_expenses  = DB::table('income_expense_type')
                ->where('income_expense_type', '=', '2')
                ->orderBy('income_expense_name')
                ->get();
                
    $liability = isset($liability[0]) ? $liability[0] : "";
   
    

    return view('fna.editLiablilityNew',['client_expenses' => $client_expenses, 'client_assets' => $my_assets, 'liability_types' => $liability_types, 'liability'=>$liability,'asset_owners' => $liability_owners, 'client_reference_id'=>$clientReff, 'client_owners'=>$client_owners, 'liability_owners' => $arrOwners]);
}


public function update_Liability(Request $request){
     $request->validate([
            
            'liability_type' => 'required',
            'liability_sub_type' => 'required',
            'policy_number' => 'required',
            'outstanding_balance' => 'required|numeric',
            // 'under_advice' => 'required',
            'type_of_business' => 'required',
            'original_balance' => 'required|numeric',
            'loan_application_amount' => 'required|numeric',
            //'limit' => 'required' , //|numeric',
            'principal_repaid' => 'required|numeric',
            'last_updated_by' => 'required',
            'interest_rate_type' => 'required',
            'interest_rate_pa' => 'required|numeric',
            
            'repayment_amount' => 'required|numeric',
            'repayment_frequency' => 'required',
            //'select_asset_type' => 'required',
            
            ]);
    
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $personalInfoModule = "Liabilities Module";
        $userRole = DB::select("SELECT name FROM `user_groups` WHERE `id` in  (SELECT groupId FROM `permissions` where userId = '".$userId."')");
        // dd($userRole);
        // dd($request->all());
        DB::table('audit')->insert([
            'id' => null,
            'user' => $userId,
            'module' => $personalInfoModule,
            'role' => $userRole[0]->name,
            'action' => "Landed on liabilities Update page",
            'action_details' => json_encode($request->all()),
            'date' => DB::raw('now()')
        ]); 
        $user_id = 1;
        $id = $_POST["liability_id"]; 
        $client_reference_id =$_POST["client_reference_id"]; 
        $query = DB::select("UPDATE client_liabilities_new SET 
            client_type = '".$_POST['client_type']."',
        	advisor_id='".$_POST["advisor_id"]."', 
        	capture_advisor_id='".$_POST['capture_advisor_id']."', 
        	client_reference_id='".$_POST['client_reference_id']."', 
        	liability_type='".$_POST['liability_type']."', 
        	liability_sub_type='".$_POST['liability_sub_type']."', 
        	policy_number='".$_POST['policy_number']."', 
        	outstanding_balance='".$_POST['outstanding_balance']."', 
        	under_advice='".$_POST['under_advice']."', 
        	type_of_business='".$_POST['type_of_business']."', 
        	original_balance='".$_POST['original_balance']."', 
        	loan_application_amount='".$_POST['loan_application_amount']."', 
        	thelimit='".$_POST['thelimit']."', principal_repaid='".$_POST['principal_repaid']."', 
        	last_updated_by='".$_POST['last_updated_by']."', 
        	interest_rate_type='".$_POST['interest_rate_type']."', 
        	interest_rate_pa='".$_POST['interest_rate_pa']."', 
        	
        	repayment_amount='".$_POST['repayment_amount']."', 
        	repayment_frequency='".$_POST['repayment_frequency']."', 
        	select_asset_type='".(isset($_POST['select_asset_type']) ? $_POST['select_asset_type'] : '0')."',
            expense_type =  '".(isset($_POST['expense_type']) ? $_POST['expense_type'] : '0')."'
        	where client_reference_id ='$client_reference_id' AND id = '$id'"); 
  
        
        if(isset($_POST["asset_owners_id"])){
   
        $beneficiary_count = count($_POST['asset_owners_id']);
         if($beneficiary_count >0) {
         //delete old oweners infor
             $qryDelete = DB::select("DELETE FROM client_liabilities_ownership WHERE client_reference_id = '".$_POST['client_reference_id']."' "); 
        //add/update new owners
        for($i = 0; $i < $beneficiary_count; $i++)
        {
            $saveclientLiabilityOwnersy = DB::table('client_liabilities_ownership')->insert([
                        	'owner_id' => $_POST['asset_owners_id'][$i],
                        	'type' => $_POST['owners_item_type'][$i],
                        	'client_reference_id' => $_POST['owners_client_reference_id'][$i],
                        	'liabilities_id' => $_POST['liability_id'],
                        	'percentage' => $_POST['asset_allocation_owners'][$i]
                        ]);
        }
        }
        }

        
        //dd('done');
         $client_reference_id = $_POST["client_reference_id"];
        return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id,'client_type' => 'Main Client', 'type' => 'liabilities' ]);
        
}

 public function delete_Liability($client_reference_id, $id) {
        session_start();
        if(empty($_SESSION['login']))
        {
            header("location: https://fna2.phpapplord.co.za/public/");
            exit;
        }
        $userId = $_SESSION['userId'];
        $query1 = DB::select("DELETE FROM client_liabilities_ownership  WHERE client_reference_id = '".$client_reference_id."' AND liabilities_id = '".$id."'");
        $query = DB::select("DELETE FROM client_liabilities_new where client_reference_id  = '".$client_reference_id."' AND id = '".$id."'");      
   
    	return redirect()->route('updateDeleteAssetsLiabilities',['client_reference_id' => $client_reference_id , 'client_type' => 'Main Client', 'type' => 'liabilities' ]);
    }
    

}
