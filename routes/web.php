<?php

use App\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\User;
use Illuminate\Support\Facades\DB;
use App\Cpb;



/*
|--------------------------------------------------------------------------
| Web Routes 
|--------------------------------------------------------------------------
| 
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('test', function() {
    session_start();
    dd($_SESSION);
});

Route::get('/cpb', function() {
    $cpb = new Cpb;
    $client = DB::table('clients')
                    ->select('users.*', 'clients.*')
                    ->join('users', 'clients.user_id', '=', 'users.id')
                    ->where('client_reference_id', 'fna000000001')
                    ->first();

    $cpb->kyc($client);
    
});

Route::get('send-mail-test', function () {
   $link_ref = '';
    $details = [
        'title' => 'Mail from vb.jyoti@gmail.com', //jyoti@applord.co.za',
        'body' => 'This is for testing email using smtp'
    ];
            $link_ref = 'https://fna2.phpapplord.co.za/public/clientslink/'.$link_ref;
        $mailData = [
                    'title' => 'Welcome To Flight Plan - checking email test',
                    'name' => 'jyoti',
                    'link' =>$link_ref
                ];
                
   
    Mail::to('vb.jyoti@gmail.com')->send(new \App\Mail\SendUserMail($mailData));
    dd("Email is Sent.");
});

Route::get('/', function () {
    return view('auth.login');
})->name('login');
Route::get('/summary/{client_reference_id}', 'SeedAnalyticsController@summary')->name('summary');
Route::get('/checkMainClientVIN', 'ClientController@checkMainClientVIN')->name('checkMainClientVIN');
Route::post('/checkMainClientVIN', 'ClientController@checkMainClientVIN')->name('checkMainClientVIN');
Route::get('/view_kyc/{user}', 'CpbController@kyc')->name('view_kyc');
Route::get('/view_cpb_report/{user}', 'CpbController@cpb')->name('view_cpb_report');

Route::get('/bank_login/{client_reference_id}/{client_type}', 'BankStatementController@bank_login')->name('bank_login');

Route::get('/bank_login_test/{client_reference_id}/{client_type}', 'BankStatementController@bank_login_test');
Route::post('/bank_csv_upload', 'BankStatementController@bank_csv_upload')->name('bank_csv_upload');
Route::get('download_csv', 'BankStatementController@download_csv_template')->name('download_csv_template');



// Route::get('/clientCapturLicense', 'ClientController@clientCaptureLicense')->name('client.capture.license');
Route::post('/AjaxclientCaptureLicenseStore', 'ClientController@AjaxclientCaptureLicenseStore')->name('AjaxclientCaptureLicenseStore');



Route::get('/bank_statement_notice/{client_reference_id}/{client_type}', 'BankStatementController@bankStatementNotice')->name('bank_statement_notice');

Route::get('/populate_liabilities/{client_ref}/{client_type}', 'UserController@populate_liabilities')->name('cpb.populate_liabilities');
Route::get('/populate_assets/{client_ref}/{client_type}', 'UserController@populate_assets')->name('cpb.populate_assets');
Route::get('/populate_liabilities_lightstone/{client_ref}/{client_type}', 'UserController@populate_liabilities_lightstone')->name('lightstone.populate_liabilities');
Route::get('/populate_assets_lightstone/{client_ref}/{client_type}', 'UserController@populate_assets_lightstone')->name('lightstone.populate_assets');
Route::get('/populate_assets_lightstone_property/{client_ref}/{client_type}', 'UserController@populate_assets_property')->name('lightstone.populate_assets_property');
Route::post('/populateClientVehicleAssets', 'UserController@populate_client_vehicle_assets')->name('lightstone.populateClientVehicleAssets');

 
Auth::routes();

//Export Clients
Route::get('/exportClients', 'ClientController@exportClients')->name('exportClients');


Route::get('/income_expense/create_notes/{client_reference_id}', 'UserController@income_expense_notes')->name('income_expense.create_notes');
Route::post('/income_expense/create_notes/{client_reference_id}', 'UserController@income_expense_notes_store')->name('income_expense.store_notes');


Route::get('/assetListAjax/{client_reference_id}', 'AssetLiabilitiesController@assetListAjax')->name('assetListAjax');
Route::post('/assetListAjax/{client_reference_id}', 'AssetLiabilitiesController@assetListAjax')->name('assetListAjax');

Route::get('/seemore', 'ClientController@seemore')->name('seemore');
Route::get('/download_mandate/{client_reference_id}', 'ClientController@download_mandate')->name('download_mandate');

Route::get('signature/{signature?}/{client_refernce_id}', 'UserController@signature')->name('signature');
Route::get('signature/{signature?}', 'UserController@signature')->name('signature');

Route::get('/clientslink/{linkReff}', 'ClientController@clientslink')->name('clientslink');
Route::get('/passwordRecoveryEmail/{clientReferenceId}', 'ClientController@passwordRecoveryEmail')->name('passwordRecoveryEmail');
Route::post('/clientpasswordupdate', 'ClientController@clientpasswordupdate')->name('clientpasswordupdate');
Route::get('/changePassword', 'UserController@changePassword')->name('change_password');
Route::post('/changePassword', 'UserController@resetPassword')->name('user.change_password');
Route::get('/resetuserpassword/{client_reference_id}/{client_type}/{user_id}/{active_code}', 'UserController@resetuserpassword')->name('resetuserpassword');
Route::post('/updatePassword', 'UserController@updatePassword')->name('updatePassword');
Route::post('/selectClientRegisterOption', 'UserController@selectClientRegisterOption')->name('selectClientRegisterOption');

Route::get('/client_registration_options', 'UserController@clientRegistrationOptions')->name('clientRegistrationOptions');
Route::get('/upload_csv', 'UserController@upload_csv')->name('upload_csv');
Route::post('/import_clients', 'UserController@import_clients')->name('import_clients');

Route::get('/edit_user_profiles/{user}', 'UserController@editUserProfile')->name('edit_user_profiles');
Route::put('/update_user_profile/{user}', 'UserController@updateUserProfile')->name('update_user_profiles');

Route::get('/companyAdvisorView', 'UserController@companyAdvisorView')->name('companyAdvisorView');
Route::post('/storeCompanyAdvisor', 'UserController@storeCompanyAdvisor')->name('storeCompanyAdvisor');
Route::get('/companyAdvisorPlanView', 'UserController@companyAdvisorPlanView')->name('companyAdvisorPlanView');

//Category Income/Expense
Route::get('/listIncomeExpenseTypes/{client_reference_id}/{client_type}', 'CategoryController@index')->name('listIncomeExpenseTypes');
Route::get('/editIncomeExpenseTypes/{id}', 'CategoryController@edit')->name('editIncomeExpenseTypes');
Route::post('/updateIncomeExpenseTypes', 'CategoryController@update')->name('updateIncomeExpenseTypes');
Route::post('/deleteIncomeExpenseTypes', 'CategoryController@delete')->name('deleteIncomeExpenseTypes');
Route::get('/createIncomeExpenseTypes', 'CategoryController@create')->name('createIncomeExpenseTypes');
Route::post('/storeIncomeExpenseTypes', 'CategoryController@store')->name('storeIncomeExpenseTypes');
Route::get('/viewIncomeExpenseTypes/{id}', 'CategoryController@view')->name('viewIncomeExpenseTypes');
Route::post('/updateIncomeExpenseItems', 'CategoryController@updateItems')->name('updateIncomeExpenseItems');
Route::get('/listIncomeExpenseTypesAjax/{client_reference_id}/{client_type}', 'CategoryController@listIncomeExpenseTypesAjax')->name('listIncomeExpenseTypesAjax');
//

Route::get('/getitemsIncomeAjax/{id}/{select_name}', 'IncomesExpensesController@getitemsIncomeAjax')->name('getitemsIncomeAjax');

//Income Expense
Route::get('/createIncomeExpense/{client_reference_id}', 'IncomesExpensesController@createIncomeExpense')->name('createIncomeExpense');
// Route::get('/createIncomeExpense', 'IncomesExpensesController@createIncomeExpense')->name('createIncomeExpense');
Route::post('/storeIncomeExpense', 'IncomesExpensesController@storeIncomeExpense')->name('storeIncomeExpense');




Route::get('/fetchIncomeExpense/{client_reference_id}', 'IncomesExpensesController@fetchIncomeExpense')->name('fetchIncomeExpense');
Route::post('/updateIncomeExpense', 'IncomesExpensesController@updateIncomeExpense')->name('updateIncomeExpense');

Route::post('/cpbCreditScore', 'IncomesExpensesController@cpbCreditScore')->name('cpbCreditScore');

//Export assets and liabilities
Route::get('/exportAssets/{client_reference_id}/{client_type}', 'AssetLiabilitiesController@exportAssets')->name('exportAssets');
Route::get('/exportLiabilities/{client_reference_id}/{client_type}', 'AssetLiabilitiesController@exportLiabilities')->name('exportLiabilities');

Route::get('/updateDeleteAssetsLiabilities/{client_reference_id}/{client_type}/{type}', 'AssetLiabilitiesController@updateView')->name('updateDeleteAssetsLiabilities');
// Route::get('/updateDeleteAssetsLiabilities', 'AssetLiabilitiesController@updateView')->name('updateDeleteAssetsLiabilities');
Route::get('/insuranceFormView/{client_reference_id}', 'ClientAssetController@insuranceFormView')->name('insuranceFormView');
Route::get('/insuranceDelete/{reff}', 'ClientAssetController@deleteClientInsuranceNew')->name('insuranceDelete');
Route::get('/fetchClientInsuranceList/{reff}', 'ClientAssetController@fetchClientInsuranceList')->name('fetchClientInsuranceList');


Route::get('/assetDetailsView/{id}/{client_reference_id}/{client_type}', 'AssetLiabilitiesController@assetDetailsView')->name('assetDetailsView');
Route::post('/updateLiabilityNew', 'AssetLiabilitiesController@updateLiability')->name('updateLiabilityNew');
// Route::get('/deleteLiabilityNew/{id}', 'AssetLiabilitiesController@deleteLiability')->name('deleteLiabilityNew');
Route::get('/deleteLiabilityNew/{id}/{client_reference_id}/{client_type}', 'AssetLiabilitiesController@deleteLiability')->name('deleteLiabilityNew');

Route::get('/fna', 'FnaController@dashboard')->name('fna');
Route::get('/clientCreate', 'ClientController@clientCreate')->name('clientCreate');
Route::post('/csvClientStore', 'ClientController@csvClientStore')->name('csvClientStore');
Route::post('/spouseSave', 'ClientController@spouseSave')->name('spouseSave');
Route::post('/clientSave', 'ClientController@clientSave')->name('clientSave');
Route::get('/clientList', 'ClientController@clientList')->name('clientList');
Route::post('/clientUpdate', 'ClientController@clientUpdate')->name('clientUpdate');
Route::get('/clientDelete/{id}', 'ClientController@clientDelete')->name('clientDelete');
Route::get('/clientEdit/{id}', 'ClientController@clientEdit')->name('clientEdit');
Route::get('/activeAccount/{id}/{token}', 'ClientController@activeAccount')->name('activeAccount');
Route::post('/clientUpdateSignature', 'ClientController@clientUpdateSignature')->name('clientUpdateSignature');
Route::post('/storeClientDisclosure', 'ClientController@storeClientDisclosure')->name('storeClientDisclosure');

Route::post('/saveClientAuthorization', 'ClientController@saveClientAuthorization')->name('saveClientAuthorization');
Route::get('/getClientAuthorization', 'ClientController@getClientAuthorization')->name('getClientAuthorization');
// Route::get('/clientListAjax', 'ClientController@clientListAjax')->name('clientListAjax');
Route::get('/clientListAjax/{authenticatedUserType}/{userId}', 'ClientController@clientListAjax')->name('clientListAjax');

Route::post('/clientListAjax', 'ClientController@clientListAjax')->name('clientListAjax');


Route::get('/dependantDelete/{id}/{ref}', 'ClientController@dependantDelete')->name('dependantDelete');


Route::get('/email', 'ClientController@mail');


Route::get('/objectives', 'FnaController@objectives')->name('objectives');
Route::get('/personal', 'FnaController@personal')->name('personal');

Route::get('/overview', 'FnaController@overview_list')->name('overview_index');
Route::get('/overviewListAjax', 'FnaController@overviewListAjax')->name('overviewListAjax');

Route::get('/getClients', 'FnaController@getClients')->name('getClients');

Route::get('/overview/{client_reference_id}/{client_type}', 'FnaController@overview')->name('overview');
Route::get('/cashflow/{client_reference_id}/{client_type}/{bank_name}', 'FnaController@cashflow')->name('cashflow');
Route::get('/insurance/{client_reference_id}/{client_type}', 'FnaController@insurance')->name('insurance');
Route::post('/insurance/{client_reference_id}/{client_type}', 'FnaController@insurance')->name('insurance');
//Route::get('/insurance', 'FnaController@insurance')->name('insurance');
Route::get('/what-am-i-worth/{client_reference_id}/{client_type}', 'FnaController@whatamiworth')->name('whatamiworth');

Route::get('/overviewIncomeExpenses', 'IncomeExpenseController@overview')->name('overviewIncomeExpenses');
// Route::get('/overview/{client_reference_id}/{client_type}', 'FnaController@overview')->name('overview');


Route::get('/userIncomeExpense/', 'IncomeExpenseController@userIncomeExpense')->name('userIncomeExpense');
Route::get('/listIncomeExpense/{client_reference_id}/{id}', 'IncomeExpenseController@listIncomeExpense')->name('listIncomeExpense');
Route::get('/addIncomeExpense/{client_reference_id}/{id}', 'IncomeExpenseController@addIncomeExpense')->name('addIncomeExpense');
Route::post('/addIncomeExpense/{client_reference_id}/{id}', 'IncomeExpenseController@addIncomeExpense')->name('addIncomeExpense');
Route::post('/saveIncomeExpense', 'IncomeExpenseController@store')->name('saveIncomeExpense');
Route::get('/viewIncomeExpense/{id}/{client_reference_id}/{type}', 'IncomeExpenseController@viewIncomeExpense')->name('viewIncomeExpense');
Route::get('/IncomeExpense/{client_reference_id}/{id}', 'IncomeExpenseController@index')->name('IncomeExpense');
Route::get('/fetchSingleIncomeExpense/{id}/{client_reference_id}/{type}', 'IncomeExpenseController@fetchSingleIncomeExpense')->name('fetchSingleIncomeExpense');
Route::get('/deleteSingleIncome/{id}/{client_reference_id}/{type}/{client_id}', 'IncomeExpenseController@deleteSingleIncome')->name('deleteSingleIncome');
Route::get('/deleteSingleExpense/{id}/{client_reference_id}/{type}/{client_id}', 'IncomeExpenseController@deleteSingleExpense')->name('deleteSingleExpense');
Route::post('/update', 'IncomeExpenseController@update')->name('update');

Route::get('/InsuranceOverview/{client_reference_id}', 'InsuranceOverviewController@index')->name('index');
Route::post('/SaveInsuranceOverview/{client_reference_id}', 'InsuranceOverviewController@SaveInsuranceOverview')->name('SaveInsuranceOverview');
Route::get('/EditnsuranceOverview/{client_reference_id}', 'InsuranceOverviewController@EditnsuranceOverview')->name('EditnsuranceOverview');
Route::post('/UpdateInsuranceOverview/{client_reference_id}', 'InsuranceOverviewController@UpdateInsuranceOverview')->name('UpdateInsuranceOverview');

#Route::get('/listAssetLiabilities', 'AssetLiabilitiesController@index')->name('listAssetLiabilities');
Route::get('/listAssetLiabilities/{client_reference_id}/{client_type}', 'AssetLiabilitiesController@index')->name('listAssetLiabilities');
Route::post('/saveAssetLiabilities', 'AssetLiabilitiesController@store')->name('saveAssetLiabilities');
Route::post('/updateAssetLiabilities', 'AssetLiabilitiesController@update')->name('updateAssetLiabilities');

Route::get('/overviewAssetliabilities', 'AssetLiabilitiesController@overview')->name('overviewAssetliabilities');
// Route::get('/overview/{client_reference_id}/{client_type}', 'FnaController@overview')->name('overview');


Route::get('/listLiabilities', 'LiabilitiesController@index')->name('listLiabilities');
Route::get('/createLiabilities', 'LiabilitiesController@createLiabilities')->name('createLiabilities');
Route::post('/saveLiabilities', 'LiabilitiesController@saveLiabilities')->name('saveLiabilities');
Route::post('/updateLiabilities/{id}', 'LiabilitiesController@updateLiabilities')->name('updateLiabilities');
Route::post('/deleteLiabilities/{id}', 'LiabilitiesController@deleteLiabilities')->name('deleteLiabilities');


Route::get('/listAudit', 'AuditTrailController@index')->name('listAudit');
Route::get('/listAuditAjax', 'AuditTrailController@listAuditAjax')->name('listAuditAjax');
Route::post('/listAuditAjax', 'AuditTrailController@listAuditAjax')->name('listAuditAjax');
Route::get('/createAudit', 'AuditTrailController@createAudit')->name('createAudit');
Route::post('/saveAudit', 'AuditTrailController@saveAudit')->name('saveAudit');
Route::post('/updateAudit/{id}', 'AuditTrailController@updateAudit')->name('updateAudit');
Route::post('/deleteAudit/{id}', 'AuditTrailController@deleteAudit')->name('deleteAudit');
// Route::get('/exportCsvAuditFilter/{from_date}/{to_date}/{module_name}', 'AuditTrailController@exportCsvFilter')->name('exportCsvAuditFilter');
Route::post('/exportCsvAuditFilter', 'AuditTrailController@exportCsvFilter')->name('exportCsvAuditFilter');
Route::get('/exportCsvAudit', 'AuditTrailController@exportCsv')->name('exportCsvAudit');

Route::get('/noAccess', 'FnaController@noAccess')->name('noAccess'); 
Route::get('/company', 'FnaController@company')->name('company');
Route::get('/usersList', 'FnaController@usersList')->name('usersList');
Route::get('/userCreate', 'FnaController@userCreate')->name('userCreate');
Route::post('/userCreateForm', 'FnaController@userCreateForm')->name('userCreateForm');
Route::get('/userGroup', 'FnaController@userGroup')->name('userGroup');
Route::get('/createObjectives', 'FnaController@createObjectives')->name('createObjectives');
Route::get('/dependantList', 'FnaController@dependantList')->name('dependantList');
Route::get('/require', 'FnaController@require')->name('require');
Route::get('/assetList', 'FnaController@assetList')->name('assetList');
Route::get('/dependants', 'FnaController@dependants')->name('dependants');
Route::get('/liabilities', 'FnaController@liabilities')->name('liabilities');
Route::get('/income', 'FnaController@income')->name('income');
Route::get('/createAsset', 'FnaController@createAsset')->name('createAsset');
Route::get('/createLiability', 'FnaController@createLiability')->name('createLiability');
Route::get('/logout', 'FnaController@logout')->name('logout');
Route::post('/logins', 'FnaController@logins')->name('logins')->name('sign_up');
Route::get('/clients', 'FnaController@clients')->name('clients');
Route::post('/storeBankStatement', 'BankStatementController@storeBankStatement')->name('storeBankStatement');

Route::get('/clientAssetsList', 'ClientAssetController@clientAssetsList')->name('clientAssetsList');
Route::get('/createClientAssets', 'ClientAssetController@createClientAssets')->name('createClientAssets');
Route::post('/createClientAssetsForm', 'ClientAssetController@createClientAssetsForm')->name('createClientAssetsForm');
Route::post('/updateClientAssets', 'ClientAssetController@updateClientAssets')->name('updateClientAssets');
/*Route::get('/deleteClientAssets', 'ClientAssetController@deleteClientAssets')->name('deleteClientAssets');*/
Route::get('/deleteClientAssetsNew/{client_reference_id}/{id}', 'ClientAssetController@deleteClientAssetsNew')->name('deleteClientAssetsNew');

/*New routes*/
Route::get('/newAssetsList/{clientReff}/', 'ClientAssetController@newAssetsList')->name('newAssetsList');
//assets
Route::get('/createClientAssetsNew/{client_reference_id}/{client_type}', 'ClientAssetController@createClientAssetsNew')->name('createClientAssetsNew');
Route::post('/storeClientAssets', 'ClientAssetController@storeClientAssets')->name('storeClientAssets');
Route::post('/storeClientAssetsNew', 'ClientAssetController@storeClientAssetsNew')->name('storeClientAssetsNew');
//liablities
Route::get('/createClientLiabilitiesNew/{client_reference_id}/{client_type}', 'ClientLiabilitiesController@createClientLiabilitiesNew')->name('clients.liabilities.index');
Route::post('/storeClientLiabilitiesNew', 'ClientLiabilitiesController@storeClientLiabilitiesNew')->name('storeClientLiabilitiesNew');
Route::post('/storeClientLiabilities', 'ClientLiabilitiesController@storeClientLiablilities')->name('clients.liablilities.store');
Route::get('/editLiabilityNew/{client_reference_id}/{id}', 'ClientLiabilitiesController@editLiabilityNew')->name('clients.liablilities.edit');
Route::post('/update_Liability', 'ClientLiabilitiesController@update_Liability')->name('clients.liablilities.update');
Route::get('/delete_Liability/{client_reference_id}/{id}', 'ClientLiabilitiesController@delete_Liability')->name('clients.liablilities.delete');

Route::get('/fetchClientAssetsList/{client_reference_id}/{id}', 'ClientAssetController@fetchClientAssetsList')->name('fetchClientAssetsList');

Route::get('/updateClientAssetsNew/{client_reference_id}/{id}', 'ClientAssetController@updateClientAssetsNew')->name('updateClientAssetsNew');
Route::post('/updateClientAssetsNew', 'ClientAssetController@updateClientAssetsNew')->name('updateClientAssetsNew');

Route::get('/updateClientAssetsBeneficiary', 'ClientAssetController@updateClientAssetsBeneficiary')->name('updateClientAssetsBeneficiary');
Route::get('/updateClientAssetsBeneficiary', 'ClientAssetController@updateClientAssetsBeneficiary')->name('updateClientAssetsBeneficiary');

Route::get('/deleteClientAssets/{id}', 'ClientAssetController@deleteLiabilitiesNew')->name('deleteClientAssets');

//Route::get('/createClientLiabilitiesNew', 'ClientAssetController@createClientLiabilitiesNew')->name('createClientLiabilitiesNew');
Route::post('/createClientLiabilitiesNew', 'ClientAssetController@createClientLiabilitiesNew')->name('createClientLiabilitiesNew');

Route::get('/updateClientLiabilitiesNew/{client_reference_id}/{id}', 'ClientAssetController@updateClientLiabilitiesNew')->name('updateClientLiabilitiesNew');
Route::post('/updateClientLiabilitiesNew/{client_reference_id}/{id}', 'ClientAssetController@updateClientLiabilitiesNew')->name('updateClientLiabilitiesNew');

Route::get('/deleteClientLiabilitiesNew/{client_reference_id}/{id}', 'ClientAssetController@deleteClientLiabilitiesNew')->name('deleteClientLiabilitiesNew');
Route::get('/fetchClientLiabilitiesList/{client_reference_id}/{id}', 'ClientAssetController@fetchClientLiabilitiesList')->name('fetchClientLiabilitiesList');

Route::get('/insuranceListView/{client_reference_id}', 'ClientAssetController@insuranceListView')->name('insuranceListView');
Route::get('/createClientInsuranceNew', 'ClientAssetController@createClientInsuranceNew')->name('createClientInsuranceNew');
Route::post('/createClientInsuranceNew', 'ClientAssetController@createClientInsuranceNew')->name('createClientInsuranceNew');

Route::get('/updateClientInsuranceNew/{client_reference_id}', 'ClientAssetController@updateClientInsuranceNew')->name('updateClientInsuranceNew');
Route::post('/updateClientInsuranceNew/{client_reference_id}/{id}', 'ClientAssetController@updateClientInsuranceNew')->name('updateClientInsuranceNew');


Route::get('/insuranceList/{client_reference_id}/{client_type}', 'InsuranceController@indexList')->name('insuranceList');
Route::get('/createAstuteInsurance/{client_reference_id}/{client_type}', 'InsuranceController@createAstuteInsurance')->name('createAstuteInsurance'); 
Route::get('/createAstuteInsuranceReadFromXMLFile/{client_reference_id}/{client_type}', 'InsuranceController@createAstuteInsuranceReadFromXMLFile')->name('createAstuteInsuranceReadFromXMLFile'); 

Route::get('/testAstuteInsurance', 'AuditTrailController@testloop')->name('testAstuteInsurance');
//Route::get('/insuranceList', 'InsuranceController@index')->name('insuranceList');

Route::get('/createInsurance/{client_reference_id}/{client_type}', 'InsuranceController@createInsurance')->name('createInsurance');
Route::post('/saveInsurance/{client_reference_id}', 'InsuranceController@saveInsurance')->name('saveInsurance');

Route::get('/fetchUpdateInsuranceList/{client_reference_id}/{id}', 'InsuranceController@fetchUpdateInsuranceList')->name('fetchUpdateInsuranceList');
Route::post('/UpdateInsuranceList/{client_reference_id}/{id}', 'InsuranceController@UpdateInsuranceList')->name('UpdateInsuranceList');


Route::get('/deleteInsuranceList/{client_reference_id}/{client_type}/{id}', 'InsuranceController@deleteInsuranceList')->name('deleteInsuranceList');



Route::get('/listIncomesExpenses', 'IncomesExpensesController@listIncomesExpenses')->name('listIncomesExpenses');

Route::get('/listIncomesExpensesDetails/{client_reference_id}/{id}', 'IncomesExpensesController@listIncomesExpensesDetails')->name('listIncomesExpensesDetails');

Route::get('/LightstoneVehicle/{client_reference_id}/{client_type}', 'VehicleController@index')->name('LightstoneVehicle');

Route::get('/package/{client_reference_id}/{client_type}/{default_package}', 'PaymentController@package')->name('package');
Route::get('/skipPackage/{client_reference_id}/{client_type}', 'PaymentController@skipPackage')->name('skipPackage');
Route::post('/makePayment', 'PaymentController@makePayment')->name('makePayment');

Route::get('/successPayment', 'PaymentController@successPayment')->name('successPayment');
Route::get('/cancelPayment', 'PaymentController@cancelPayment')->name('cancelPayment');
Route::get('/notifyPayment', 'PaymentController@notifyPayment')->name('notifyPayment');

Route::get('/successExtraFetaurePayment', 'PaymentController@successExtraFetaurePayment')->name('successExtraFetaurePayment');
Route::get('/cancelExtraFetaurePayment', 'PaymentController@cancelExtraFetaurePayment')->name('cancelExtraFetaurePayment');
Route::get('/notifyExtraFetaurePayment', 'PaymentController@notifyExtraFetaurePayment')->name('notifyExtraFetaurePayment');


Route::get('/finalPayment', 'PaymentController@finalPayment')->name('finalPayment');
Route::post('/finalPayment', 'PaymentController@finalPayment')->name('finalPayment');
Route::post('/storeClientPackage', 'PaymentController@storeClientPackage')->name('storeClientPackage');

//Payfast urls for lightstone Vehicle
Route::get('/successVehiclePayment', 'PaymentVehicleController@successVehiclePayment')->name('successVehiclePayment');
Route::get('/cancelVehiclePayment', 'PaymentVehicleController@cancelVehiclePayment')->name('cancelVehiclePayment');
Route::get('/notifyVehiclePayment', 'PaymentVehicleController@notifyVehiclePayment')->name('notifyVehiclePayment');
//Payfast urls for lightstone Property
Route::get('/successPropertyPayment', 'PaymentPropertyController@successPropertyPayment')->name('successPropertyPayment');
Route::get('/cancelPropertyPayment', 'PaymentPropertyController@cancelPropertyPayment')->name('cancelPropertyPayment');
Route::get('/notifyPropertyPayment', 'PaymentPropertyController@notifyPropertyPayment')->name('notifyPropertyPayment');
//Payfast urls for CPB assets
Route::get('/successCpbAssetPayment', 'PaymentCpbAssetController@successCpbAssetPayment')->name('successCpbAssetPayment');
Route::get('/cancelCpbAssetPayment', 'PaymentCpbAssetController@cancelCpbAssetPayment')->name('cancelCpbAssetPayment');
Route::get('/notifyCpbAssetPayment', 'PaymentCpbAssetController@notifyCpbAssetPayment')->name('notifyCpbAssetPayment');
//Payfast urls for CPB Liability
Route::get('/successCpbLiabilityPayment', 'PaymentCpbLiabilityController@successCpbLiabilityPayment')->name('successCpbLiabilityPayment');
Route::get('/cancelCpbLiabilityPayment', 'PaymentCpbLiabilityController@cancelCpbLiabilityPayment')->name('cancelCpbLiabilityPayment');
Route::get('/notifyCpbLiabilityPayment', 'PaymentCpbLiabilityController@notifyCpbLiabilityPayment')->name('notifyCpbLiabilityPayment');
//Payfast urls for Astute
Route::get('/successAstutePayment', 'PaymentAstuteController@successAstutePayment')->name('successAstutePayment');
Route::get('/cancelAstutePayment', 'PaymentAstuteController@cancelAstutePayment')->name('cancelAstutePayment');
Route::get('/notifyAstutePayment', 'PaymentAstuteController@notifyAstutePayment')->name('notifyAstutePayment');

/*New routes end*/
Route::get('/createClientInfoForm', 'FnaController@createClientInfoForm')->name('createClientInfoForm');
Route::post('/createDependantForm', 'FnaController@createDependantForm')->name('createDependantForm');
Route::post('/updateAsset', 'FnaController@updateAsset')->name('updateAsset');
Route::post('/companyCreateForm', 'FnaController@companyCreateForm')->name('companyCreateForm'); 
Route::post('/companyUpdate', 'FnaController@companyUpdate')->name('companyUpdate');
Route::post('/createObjectivesForm', 'FnaController@createObjectivesForm')->name('createObjectivesForm');
Route::get('/deleteObjectives/{id}', 'FnaController@deleteObjectives')->name('createObjectivesForm');
Route::get('/updateDependant/{id}', 'FnaController@updateDependant')->name('updateDependant');
Route::get('/deleteAssetitem/{id}', 'FnaController@deleteAssetitem')->name('deleteAssetitem');
Route::get('/updateAssets/{id}', 'FnaController@updateAssets')->name('updateAssets');
Route::get('/updateLiabilities/{id}', 'FnaController@updateLiabilities')->name('updateLiabilities');
Route::get('/deleteRetirementObjectives/{id}', 'FnaController@deleteRetirementObjectives')->name('deleteRetirementObjectives');
Route::get('/deleteRiskObjectivesItem/{id}/{objectiveId}', 'FnaController@deleteRiskObjectivesItem')->name('deleteRiskObjectivesItem');
Route::get('/deleteRetirementObjectivesItem/{id}/{objectiveId}', 'FnaController@deleteRetirementObjectivesItem')->name('deleteRetirementObjectivesItem');
Route::get('/editRiskObjective/{id}', 'FnaController@editRiskObjective')->name('editRiskObjective');
Route::get('/editRetirementObjective/{id}', 'FnaController@editRetirementObjective')->name('editRetirementObjective');
Route::get('/delete/{id}', 'FnaController@delete')->name('createLiability');
Route::get('/deleteLiability/{id}', 'FnaController@deleteLiability')->name('deleteLiability');
Route::get('/deleteDependant/{id}', 'FnaController@deleteDependant')->name('deleteDependant');
Route::get('/createIncome', 'FnaController@createIncome')->name('createIncome');
Route::get('/accessGroup', 'FnaController@accessGroup')->name('accessGroup');
Route::get('/userUpdate/{id}', 'FnaController@userUpdate')->name('userUpdate');
Route::get('/acl', 'FnaController@acl')->name('acl');
Route::get('/retirementObjectives', 'FnaController@retirementObjectives')->name('retirementObjectives');
Route::get('/createRetirementObjectives', 'FnaController@createRetirementObjectives')->name('createRetirementObjectives');
Route::post('/updateLiability', 'FnaController@updateLiability')->name('updateLiability');
Route::post('/createAssetForm', 'FnaController@createAssetForm')->name('createAssetForm');
Route::post('/updateDependantForm', 'FnaController@updateDependantForm')->name('updateDependantForm');
Route::post('/createRetirementObjectivesForm', 'FnaController@createRetirementObjectivesForm')->name('createRetirementObjectivesForm');
Route::post('/createLiabilityForm', 'FnaController@createLiabilityForm')->name('createLiabilityForm');
Route::post('/createIncomeForm', 'FnaController@createIncomeForm')->name('createIncomeForm');
Route::post('/updateIncomeForm', 'FnaController@updateIncomeForm')->name('updateIncomeForm');
Route::post('/createPersonalInfoForm', 'FnaController@createPersonalInfoForm')->name('createPersonalInfoForm'); 
Route::post('/updatePersonalInfoForm', 'FnaController@updatePersonalInfoForm')->name('updatePersonalInfoForm'); 
Route::post('/updateObjectivesForm', 'FnaController@updateObjectivesForm')->name('updateObjectivesForm'); 
Route::post('/updateRetirementObjectivesForm', 'FnaController@updateRetirementObjectivesForm')->name('updateRetirementObjectivesForm'); 
Route::post('/userGroupUpdate', 'FnaController@userGroupUpdate')->name('userGroupUpdate'); 
Route::post('/userGroupCreateForm', 'FnaController@userGroupCreateForm')->name('userGroupCreateForm');
Route::post('/userGroupDelete', 'FnaController@userGroupDelete')->name('userGroupDelete'); 
Route::post('/aclCreateForm', 'FnaController@aclCreateForm')->name('aclCreateForm');
Route::post('/accessUpdate', 'FnaController@accessUpdate')->name('accessUpdate'); 
Route::post('/accessCreateForm', 'FnaController@accessCreateForm')->name('accessCreateForm'); 
Route::post('/accessDelete', 'FnaController@accessDelete')->name('accessDelete'); 
Route::post('/userUpdateForm', 'FnaController@userUpdateForm')->name('userUpdateForm');
Route::post('/aclUpdate', 'FnaController@aclUpdate')->name('aclUpdate'); 
Route::post('/aclDelete', 'FnaController@aclDelete')->name('aclDelete'); 

Route::get('/delete/{id}', 'FnaController@delete')->name('createLiability');

Route::post('/storeClientAssetsNew', 'ClientAssetController@storeClientAssetsNew')->name('storeClientAssetsNew');
Route::post('/storeClientLiabilitiesNew', 'ClientLiabilitiesController@storeClientLiabilitiesNew')->name('storeClientLiabilitiesNew');
