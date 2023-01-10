<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

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

Route::get( '/', function () {
    return view( 'welcome' );
} );

Auth::routes();

Route::group( [ 'middleware' => 'auth' ], function () {
    Route::get( '/home', [ HomeController::class, 'index' ] )->name( 'home' );

    /** Orders */
    Route::get( '/orders', [ OrderController::class, 'index' ] )->name( 'orders' );
    Route::get( '/orders/get/{id?}', [ OrderController::class, 'getById' ] )->name( 'orders-get-by-id' );
    Route::post( '/orders/download', [ OrderController::class, 'downloadOrder' ] )->name( 'orders-download' );
    Route::post( '/orders/sendmail', [ OrderController::class, 'sendMail' ] )->name( 'orders-sendmail' );
    Route::get( '/orders/download-invoice/{id}', [ OrderController::class, 'downloadInvoice' ] )->name( 'orders-download-invoice' );
    Route::get( '/orders/edit-order-content/{id?}', [ OrderController::class, 'editOrderContent' ] )->name( 'orders-edit-content' );
    Route::post( '/orders/update-invoice', [ OrderController::class, 'updateOrderInvoice' ] )->name( 'orders-update-invoice' );

    /** Reports */
    Route::get( '/reports', [ ReportController::class, 'index' ] )->name( 'reports' );
    Route::post( '/reports/get-graph-data', [ ReportController::class, 'getReportData' ] )->name( 'reports-graph-data' );
    Route::post( '/reports/get-summary-view', [ ReportController::class, 'getSummaryView' ] )->name( 'reports-summary-view' );
    Route::post( '/reports/invoice/download', [ ReportController::class, 'downloadInvoice' ] )->name( 'reports-invoice-download' );

    /** Settings */
    Route::get( '/settings/whatsapp', [ SettingsController::class, 'whatsapp' ] )->name( 'settings-whatsapp' );
    Route::post( '/settings/whatsapp/add-template', [ SettingsController::class, 'addWATemplate' ] )->name( 'settings-add-wa-template' );
    Route::get( '/settings/whatsapp/get-template/{id?}', [ SettingsController::class, 'getWATemplate' ] )->name( 'settings-get-wa-template' );
    Route::post( '/settings/whatsapp/delete', [ SettingsController::class, 'deleteWATemplate' ] )->name( 'settings-delete-wa-template' );

    Route::get( '/settings/email', [ SettingsController::class, 'email' ] )->name( 'settings-email' );
    Route::post( '/settings/email/add-template', [ SettingsController::class, 'addEmailTemplate' ] )->name( 'settings-add-email-template' );
    Route::get( '/settings/email/get-template/{id?}', [ SettingsController::class, 'getEmailTemplate' ] )->name( 'settings-get-email-template' );
    Route::post( '/settings/email/delete', [ SettingsController::class, 'deleteEmailTemplate' ] )->name( 'settings-delete-email-template' );
    Route::get( '/settings/vat', [ SettingsController::class, 'vat' ] )->name( 'settings-vat' );
    Route::post( '/settings/vat/save', [ SettingsController::class, 'vatSave' ] )->name( 'settings-vat-save' );

    Route::get( '/users/change-password', [ UserController::class, 'changePassword' ] )->name( 'user-change-password' );
    Route::post( '/users/change-password', [ UserController::class, 'changePasswordPost' ] )->name( 'user-change-password' );
} );
