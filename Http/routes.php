<?php

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for the module.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::group(['prefix' => 'enroll'], function() {

    // 登录页面
    Route::get('/login', 'AuthController@view')->middleware('enroll.guest');
    // 用户操作
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', 'AuthController@toLogin');
        Route::get('/logout', 'AuthController@toLogout');
    });
    // 主要模块
    Route::get('/index', function () { return view('enroll::index'); });
    Route::get('/setup', 'SetupController@index')->middleware('enroll.auth');
    Route::get('/dashboard', 'Table\\ViewController@index')->middleware(['enroll.auth', 'enroll.setup']);
    // 表单api
    Route::group(['prefix' => 'api', 'middleware' => [/** 'enroll.auth',  */'enroll.hold']], function () {
        // 安装模块
        Route::post('/setup', 'SetupController@create');
        // DataTable
        Route::post('/read', 'Table\\ViewController@read');
        Route::post('/refresh/{dept}', 'Table\\ViewController@refresh')->where('dept', '[0-9]{1,2}|all|recycle');
        Route::post('/handle', 'Table\\EditController@handle');
        Route::post('/notify', 'Table\\ViewController@notify');
        Route::post('/step', 'Table\\EditController@step');
        Route::post('/update', 'Table\\EditController@update');
        Route::post('/checkout', 'Table\\EditController@checkout');
        // 短信模块
        Route::post('/sendSMS', 'SMSController@send');

        // 渲染视图
        Route::any('/view/setup', function () {
            return view('enroll::ajax.design');
        });
    });
});
