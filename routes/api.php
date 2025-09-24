<?php

use App\Http\Controllers\Api\ACL\Permission\PermissionController;
use App\Http\Controllers\Api\ACL\Permission\RoleController;
use App\Http\Controllers\Api\Users\UserController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Invoice\InvoiceItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Users routes
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('{user}', [UserController::class, 'show']);
    Route::put('{user}', [UserController::class, 'update']);
    Route::delete('{user}', [UserController::class, 'destroy']);

    Route::post('{user}/roles', [UserController::class, 'assignRoles']);
    Route::post('{user}/permissions', [UserController::class, 'givePermissions']);
});

// Roles routes
Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('{role}', [RoleController::class, 'show']);
    Route::put('{role}', [RoleController::class, 'update']);
    Route::delete('{role}', [RoleController::class, 'destroy']);
});

// Permissions routes
Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('{permission}', [PermissionController::class, 'show']);
    Route::put('{permission}', [PermissionController::class, 'update']);
    Route::delete('{permission}', [PermissionController::class, 'destroy']);
});

// Invoices routes
Route::prefix('invoices')->group(function () {
    // Main invoice CRUD operations
    Route::get('/', [InvoiceController::class, 'index']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('{invoice}', [InvoiceController::class, 'show']);
    Route::put('{invoice}', [InvoiceController::class, 'update']);
    Route::delete('{invoice}', [InvoiceController::class, 'destroy']);
    
    // Invoice payment status update
    Route::patch('{invoice}/payment', [InvoiceController::class, 'updatePaymentStatus']);
    
    // Invoice items nested routes
    Route::prefix('{invoiceNumber}/items')->group(function () {
        Route::get('/', [InvoiceItemController::class, 'index']);
        Route::post('/', [InvoiceItemController::class, 'store']);
        Route::get('{item}', [InvoiceItemController::class, 'show']);
        Route::put('{item}', [InvoiceItemController::class, 'update']);
        Route::delete('{item}', [InvoiceItemController::class, 'destroy']);
        
        // Bulk update items
        Route::patch('/bulk', [InvoiceItemController::class, 'bulkUpdate']);
    });
});