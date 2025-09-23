<?php

use App\Http\Controllers\Api\ACL\Permission\PermissionController;
use App\Http\Controllers\Api\ACL\Permission\RoleController;
use App\Http\Controllers\Api\Users\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{user}', [UserController::class, 'show']);
    Route::put('{user}', [UserController::class, 'update']);
    Route::delete('{user}', [UserController::class, 'destroy']);

    Route::post('{user}/roles', [UserController::class, 'assignRoles']);
    Route::post('{user}/permissions', [UserController::class, 'givePermissions']);
});

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('{role}', [RoleController::class, 'show']);
    Route::put('{role}', [RoleController::class, 'update']);
    Route::delete('{role}', [RoleController::class, 'destroy']);
});

Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('{permission}', [PermissionController::class, 'show']);
    Route::put('{permission}', [PermissionController::class, 'update']);
    Route::delete('{permission}', [PermissionController::class, 'destroy']);
});