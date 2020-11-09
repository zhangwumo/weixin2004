<?php

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/info', function () {
    phpinfo();
});



Route::any('Token','TestController@wxEvent');

Route::get('/wx/token','TestController@getAccessToken');//获取

Route::get('data','TestController@data');//测试
Route::post('date','TestController@date');//测试1
//微信接口
Route::get('/guzzle','TestController@guzzle');
Route::any('/guzzle2','TestController@guzzle2');