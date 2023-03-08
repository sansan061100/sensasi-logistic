<?php

use App\Http\Controllers\AppSystemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InitializeAppController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialInController;
use App\Http\Controllers\MaterialOutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductInController;
use App\Http\Controllers\ProductOutController;
use App\Http\Controllers\ManufactureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MaterialReportController;
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\ManufactureReportController;
use App\Http\Controllers\MaterialIndexController;
use App\Http\Controllers\ProductIndexController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

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



Route::get('initialize-app', [InitializeAppController::class, 'index']);
Route::get('initialize-app/check', [InitializeAppController::class, 'check'])->name('initialize-app.check');

Route::middleware('guest')->group(function () {

    //
    Route::controller(InitializeAppController::class)->group(function () {
        Route::prefix('initialize-app')->name('initialize-app')->group(function () {
            Route::name('.create-admin-user')->prefix('create-admin-user')->group(function () {
                Route::get('/', 'createAdminUser');
                Route::post('/', 'storeAdminUser')->name('.store');

                Route::name('.oauth.google')->prefix('oauth/google')->group(function () {
                    Route::get('/', 'signUpWithGoogle');
                    Route::get('redirect', 'handleGoogleCallback')->name('.redirect');
                });
            });
        });
    });




    Route::view('forgot-password', 'pages.auth.forgot-password-form');

    Route::controller(AuthController::class)->group(function () {

        Route::post('login', 'login');

        Route::prefix('login')->name('login')->group(function () {
            Route::view('/', 'pages.auth.login-form');
            Route::get('oauth/google', 'googleOauth')->name('.oauth.google');
            Route::get('oauth/google/redirect', 'handleGoogleOauth')->name('.oauth.google.callback');
        });

        Route::post('forgot-password', 'forgotPassword');
        Route::get('reset-password/{token}', 'resetPasswordForm')->name('password.reset');
        Route::post('reset-password', 'resetPassword')->name('password.update');
    });
});


Route::middleware('auth')->group(function () {

    // all user
    Route::get('/', function () {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {
            return redirect()->route('dashboard');
        }

        if ($user->hasRole('Stackholder')) {
            return redirect()->route('dashboard');
        }

        if ($user->hasRole('Warehouse')) {
            return redirect()->route('materials.index');
        }

        if ($user->hasRole('Sales')) {
            return redirect()->route('products.index');
        }

        if ($user->hasRole('Manufacture')) {
            return redirect()->route('manufactures.index');
        }
    })->name('/');
    Route::post('user/update', [UserController::class, 'selfUpdate'])->name('user.update');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');


    Route::middleware('role:Super Admin|Stackholder')->get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:Super Admin')->controller(AppSystemController::class)->group(function () {
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('ip-addr', 'ipAddrIndex')->name('ip-addr');

            Route::resource('users', UserController::class)->except([
                'create', 'show', 'edit', 'destroy'
            ]);
        });
    });

    Route::middleware('role:Super Admin|Stackholder')->group(function () {
        Route::prefix('report')->name('report.')->group(function () {
            route::get('materials', MaterialReportController::class)->name('material.index');
            route::get('products', ProductReportController::class)->name('product.index');
            route::get('manufactures', ManufactureReportController::class)->name('manufacture.index');
        });
    });


    Route::middleware('role:Super Admin|Warehouse')->group(function () {
        Route::get('materials', MaterialIndexController::class)->name('materials.index');

        Route::resource('materials', MaterialController::class)->only([
            'store', 'update', 'destroy'
        ]);

        Route::resource('material-ins', MaterialInController::class)->only([
            'store', 'update', 'destroy'
        ]);

        Route::resource('material-outs', MaterialOutController::class)->only([
            'store', 'update', 'destroy'
        ]);
    });


    Route::middleware('role:Super Admin|Sales|Warehouse')->group(function () {
        Route::get('products', ProductIndexController::class)->name('products.index');

        Route::resource('products', ProductController::class)->only([
            'store', 'update', 'destroy'
        ]);

        Route::resource('product-ins', ProductInController::class)->only([
            'store', 'update', 'destroy'
        ]);

        Route::resource('product-outs', ProductOutController::class)->only([
            'store', 'update', 'destroy'
        ]);
    });


    Route::middleware('role:Super Admin|Manufacture')->group(function () {
        Route::resource('manufactures', ManufactureController::class)->except([
            'create', 'show', 'edit'
        ]);
    });


    Route::middleware('role:Super Admin')->group(function () {
        Route::get('~basic-page-format', fn () => view('basic-page-format'));
        Route::get('~phpinfo', fn () => phpinfo());
    });
});
