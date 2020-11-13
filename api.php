<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

      /**
      * Auth Api's
      */
    Route::post('/change/language','Api\AuthController@changeLanguage');
    Route::post('/login','Api\AuthController@login');
    Route::post('/social/login','Api\AuthController@socialLogin');
    Route::post('/register','Api\AuthController@register');
    Route::post('/forgot/password','Api\AuthController@forgotPassword');
    Route::post('/verify/otp','Api\AuthController@verifyOtp');
    Route::post('/create/password','Api\AuthController@createPassword');
    Route::get('/get/profile','Api\AuthController@getProfile');
    Route::post('/update/profile','Api\AuthController@updateProfile');
    Route::post('/upload/profile','Api\AuthController@uploadProfile');
    Route::post('/change/password','Api\AuthController@changePassword');
    Route::get('/get/cities','Api\AuthController@getCities');
    Route::get('/get/city/areas','Api\AuthController@getCityAreas');

    Route::get('/get/categories','Api\HomeController@getCategories');
    Route::get('/get/sub/categories','Api\HomeController@getSubCategories');
    Route::get('/get/home','Api\HomeController@home');
    Route::get('/get/ads','Api\HomeController@getAds');
    Route::get('/get/ad','Api\HomeController@getAd');

    Route::post('/do/favourite/ad','Api\HomeController@doFavouriteAd');
    Route::get('/get/favourite/ads','Api\HomeController@getFavouriteAds');

    Route::post('/ad/add','Api\HomeController@adAdd');
    Route::post('/ad/update','Api\HomeController@adUpdate');
    Route::get('/get/my/ads','Api\HomeController@getMyAds');
    Route::get('/get/ad/details','Api\HomeController@getAdDetails');

    Route::post('/upload/ad/image','Api\HomeController@uploadAdImage');
    Route::post('/remove/ad/image','Api\HomeController@removeAdImage');

    Route::get('/get/chat/users','Api\HomeController@chatUsers');
    Route::get('/get/chat/conversation','Api\HomeController@chatConversation');

    



      




      
      

      

      

      

      

    


