<?php

use \API\ClientController;
use \API\InvoiceController;
use \API\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \API\CouponController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('getClients/{client_reference_id}/{month}/{year}', 'API\CashflowController@getClients');
Route::get('getCashflowNames', 'API\CashflowController@getCashflowNames');
Route::get('getCashflowCategories', 'API\CashflowController@getCashflowCategories');
Route::patch('updateClientsCashflow/{client_reference_id}/{month}/{year}', 'API\CashflowController@updateClientCashflow');
Route::post('requestCreditScore', 'API\CashflowController@cpbCreditScore');
Route::post('addClientIncome', 'API\CashflowController@addClientIncome');
Route::post('addClientExpense', 'API\CashflowController@addClientExpense');
Route::post('deleteClientTransactions', 'API\CashflowController@deleteClientTransactions');
Route::patch('updateClientTransaction', 'API\CashflowController@updateClientTransaction');

Route::get('/getAllBanks', 'BankStatementController@getAllBanks');
Route::post('/authenticateBankLogin', 'BankStatementController@authenticateBankLogin');
Route::get('/getLoginProgress', 'BankStatementController@getLoginProgress');

Route::post('/storeBankLoginTransactions', 'BankStatementController@storeBankLoginTransactions');