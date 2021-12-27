<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', "App\Http\Controllers\Front\IndexController@index");

Route::get('test/pay',"App\Http\Controllers\Test\TestPayController@index");
Route::post('test/api_pay',"App\Http\Controllers\Test\TestPayController@api");