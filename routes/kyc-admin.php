<?php

use Illuminate\Support\Facades\Route;

// Direct KYC admin routes with higher priority
Route::middleware(['web'])->group(function () {
    // Main KYC admin route
    Route::get('/app/admin/kyc/requests', 'KycAdminController@index');

    // Alternative routes for direct access
    Route::get('/admin/kyc', 'KycAdminController@index');
    Route::get('/admin/kyc/requests', 'KycAdminController@index');
    Route::get('/kyc-admin', function() {
        return file_get_contents(public_path('kyc-admin-access.html'));
    });

    // API routes for KYC admin
    Route::prefix('api')->middleware(['auth:api'])->group(function () {
        Route::get('/admin/kyc/requests', 'Admin\\KycRequestController@index');
        Route::get('/admin/kyc/requests/{id}/document', 'Admin\\KycRequestController@getDocument');
        Route::post('/admin/kyc/requests/{id}/approve', 'Admin\\KycRequestController@approve');
        Route::post('/admin/kyc/requests/{id}/reject', 'Admin\\KycRequestController@reject');
    });
});
