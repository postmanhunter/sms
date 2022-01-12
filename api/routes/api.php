<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthUser;
use App\Http\Middleware\AuthMer;

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


//登录模块
Route::namespace('App\Http\Controllers\Admin')->group(function(){
    Route::post('login','LoginController@login');//登陆
    Route::post('refresh_token','LoginController@refreshToken');
    Route::Post('upload','UploadController@index');//上传文件
    Route::Post('send','SendController@send');//发送短信
    Route::Post('get_temp_list','TempController@getTempList');//获取数据列表
    Route::Post('delete_queue','SendController@delete');//删除队列
    
    Route::Post('get_message_status','SendController@getMessageStatus');//获取发送记录
    Route::Post('callback','SendController@callback');//获取发送记录
});

//主后台模块
Route::namespace('App\Http\Controllers\Admin')->middleware(AuthUser::class)->group(function(){
    Route::post('get_mine_info','UserController@getInfo');//获取账号信息
    Route::post('logout','LoginController@logOut');//用户登出

    Route::post('get_service_list','ServiceController@getList');//获取服务商列表
    Route::post('add_service','ServiceController@addService');//添加服务商
    Route::post('add_params','ServiceController@addParams');//添加服务商配置参数
   
    
    Route::Post('add_or_edit_temp','TempController@addOrUpdate');//添加或者更新数据
    Route::Post('del_temp','TempController@delTemp');//删除数据

    
    Route::Post('get_web_info','WebController@getInfo');//获取网站数据
    Route::Post('get_record_list','RecordController@getList');//获取发送记录
    Route::Post('get_base_service','ServiceController@getBaseService');//获取基础短信服务
    Route::Post('stop_sms_push','SendController@stopSmsPush');//暂停短信发送
    Route::Post('start_sms_push','SendController@startSmsPush');//获开启短信发送
    Route::Post('stop_check_num','EmptynumController@stopCheckNum');//暂停短信检测
    Route::Post('start_check_num','EmptynumController@startCheckNum');//开启短信检测
    Route::Post('get_num_service','EmptynumController@getNumService');//开启短信检测
    Route::Post('submit_num_service','EmptynumController@submit');//开启短信检测
    Route::Post('get_message_num','SendController@getMessageNUm');//获取未发送的短信条数
    Route::Post('clean_sms_push','SendController@cleanSms');//获取未发送的短信条数

   

});
