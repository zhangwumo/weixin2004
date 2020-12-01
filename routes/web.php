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

Route::any('data','TestController@data');//测试
Route::any('date','WxController@date');//测试1
//微信接口
Route::get('/guzzle','WxController@guzzle');
Route::any('/guzzle2','WxController@guzzle2');

//菜单
Route::get('/menu','TestController@menu');



//小程序

Route::get('/goodslist','AcaController@goodslist');
Route::get('/xcxlogin','Weixin\XcxController@login');//小程序登录
Route::any('/detail','Weixin\XcxController@detail');

// Route::get('/home-login','AcaController@homeLogin');//首页登录
Route::get('/add-fav','Weixin\XcxController@addfav');//收藏
Route::any('/userLogin','AcaController@userLogin');  //个人中心登录