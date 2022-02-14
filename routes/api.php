<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\ChatController;



/*
Friend Route
*/

Route::group(["prefix" => "friends", "middleware" => "auth:api"], function () {

    Route::get("/friendRequest", [FriendController::class, "getFriendRequestList"]);
    Route::post("/friendList", [FriendController::class, "getFriendList"]);
    Route::post("/searchFriend", [FriendController::class, "searchFriend"]);
    Route::post("/acceptRequest/{id}", [FriendController::class, "acceptRequest"]);
    Route::post("/sendRequest{id}", [FriendController::class, "sendRequest"]);
});

/*
Chat Route
*/
Route::group(["prefix" => "chats",], function () {

    Route::group(["middleware" => "auth:api"], function () {
        Route::get("/{id}", [ChatController::class, "getMessage"]);
        Route::post("/{id}", [ChatController::class, "sendMessage"]);
    });
});


/*
Auth Route
*/
 
Route::post("/me", [AuthController::class, 'me'])->middleware("auth:api");
Route::post("/refresh", [AuthController::class, 'refresh']);
Route::post("/reset-password", [AuthController::class, 'resetPassword']);
Route::post("/check-token", [AuthController::class, 'checkToken']);
Route::post("/forget-password", [AuthController::class, "forgetPasswordRequest"]);
Route::post("/reverify", [AuthController::class, "reverifyRequest"]);
Route::post("/verify", [AuthController::class, "verify"]);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/register', [AuthController::class, 'register']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});