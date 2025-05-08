<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

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

// Guest Routes
Route::namespace('Auth')->prefix('auth')->group(function () {
    Route::post('login/otp', 'LoginWithOtp');
    Route::post('login', 'LoginController@login');

    Route::post('register', 'RegisterController@register');
    Route::post('verify', 'RegisterController@verify');

    Route::post('password', 'ResetPasswordController@password');
    Route::post('validate-reset-password', 'ResetPasswordController@validateCode');
    Route::post('reset', 'ResetPasswordController@reset');
});

// Global Routes
Route::namespace('Config')->group(function () {
    Route::get('config/pre-requisite', 'ConfigController@preRequisite');
    Route::group(['middleware' => ['under_maintenance']], function () {
        Route::get('config', 'ConfigController@index');

        Route::post('config/auth', 'ConfigController@authToken');
    });
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    // Auth Routes
    Route::namespace('Auth')->prefix('auth')->group(function () {
        Route::post('logout', 'LoginController@logout');
        Route::post('lock', 'LockScreen');
        Route::get('user', 'UserController@me');
        Route::post('security', 'TwoFactorSecurity');
    });
});

Route::prefix('meetings')->group(function() {
    Route::get('pam/{identifier}', 'MeetingController@showPam');

    Route::prefix('{meeting}')->group(function() {
        Route::get('summary', 'MeetingController@summary');
        Route::get('pam', 'MeetingController@pam');
        Route::post('join', 'InviteeController@join');
        Route::post('leave', 'InviteeController@leave');
        Route::post('joining-request', 'InviteeController@joiningRequest');
    });

    Route::get('{meeting}/polls', 'MeetingPollController@index');
    Route::get('{meeting}/polls/{poll}', 'MeetingPollController@show');
    Route::post('{meeting}/polls/{poll}/vote', 'MeetingPollController@vote');
});

Route::group(['middleware' => ['auth:sanctum', '2fa', 'under_maintenance']], function () {

    // Auth Routes
    Route::namespace('Auth')->prefix('auth')->group(function () {
        Route::post('change-password', 'ChangePassword');
    });

    // User Routes
    Route::namespace('Auth')->prefix('user')->group(function () {
        Route::post('preference', 'UserController@preference');
    });

    Route::namespace('Auth')->group(function () {
        Route::get('users/pre-requisite', 'UserController@preRequisite');
        Route::post('users/{user}/status', 'UserController@updateStatus');
        Route::post('users/{user}/premium', 'UserController@premium');
        Route::post('users/subscriptions', 'UserController@updateSubscription');
        Route::post('users/subscriptions/delete', 'UserController@deleteSubscription');
        Route::apiResource('users', 'UserController');

        Route::post('profile', 'ProfileController@update')->middleware('restricted_test_mode_action');
        Route::post('profile/avatar', 'ProfileController@uploadAvatar')->middleware('restricted_test_mode_action');
        Route::delete('profile/avatar', 'ProfileController@removeAvatar')->middleware('restricted_test_mode_action');
    });

    // Dashboard Routes
    Route::get('dashboard', 'DashboardController@index');
    Route::get('dashboard/stats', 'DashboardController@getStats');
    Route::get('dashboard/chart', 'DashboardController@getChart');

    // Any key search
    Route::get('search', 'Search');

    Route::post('uploads/image', 'UploadController@image');

    // Config Routes
    Route::namespace('Config')->prefix('config')->group(function () {
        Route::post('', 'ConfigController@store');
        Route::post('notification', 'ConfigController@notification');
        Route::get('notification', 'ConfigController@showDemoNotification');
        Route::post('assets', 'ConfigController@uploadAsset')->middleware('restricted_test_mode_action');
        Route::delete('assets', 'ConfigController@removeAsset')->middleware('restricted_test_mode_action');

        Route::delete('roles/{name}', 'RoleController@destroy');
        Route::apiResource('roles', 'RoleController')->except(['update', 'destroy']);

        Route::get('permissions/pre-requisite', 'PermissionController@preRequisite');
        Route::post('permissions/assign', 'PermissionController@assign');

        Route::get('locales/pre-requisite', 'LocaleController@preRequisite');
        Route::post('locales/{locale}/translate', 'LocaleController@translate');
        Route::apiResource('locales', 'LocaleController');
    });

    // Activity Routes
    Route::get('activities', 'ActivityController@index');

    // Option Routes
    Route::get('options/pre-requisite', 'OptionController@preRequisite');
    Route::apiResource('options', 'OptionController');

    Route::group(['middleware' => ['can:access-contact']], function () {
        Route::get('segments/pre-requisite', 'SegmentController@preRequisite');
        Route::apiResource('segments', 'SegmentController');

        Route::get('contacts/pre-requisite', 'ContactController@preRequisite');
        Route::apiResource('contacts', 'ContactController');

        Route::post('contacts/segment', 'ContactController@updateSegment');

        Route::post('contacts/import/start', 'ContactImportController@startImport');
        Route::post('contacts/import/finish', 'ContactImportController@finishImport');
    });

    Route::get('meetings/pre-requisite', 'MeetingController@preRequisite');
    Route::get('meetings/m/{identifer}', 'MeetingController@showMeeting');
    Route::apiResource('meetings', 'MeetingController');
    Route::apiResource('meetings.chats', 'MeetingChatController')->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('meetings.polls', 'MeetingPollController')->only(['store', 'update', 'destroy']);
    Route::post('meetings/{meeting}/polls/{poll}/publish', 'MeetingPollController@publish');
    Route::post('meetings/{meeting}/polls/{poll}/publish/result', 'MeetingPollController@publishResult');

    Route::prefix('meetings/{meeting}')->group(function() {
        Route::post('config', 'MeetingController@config');
        Route::post('snooze', 'MeetingController@snooze');
        Route::post('cancel', 'MeetingController@cancel');
        Route::post('snooze-end-time', 'MeetingController@snoozeEndTime');
        Route::post('cancel-auto-end', 'MeetingController@cancelAutoEnd');

        Route::post('snapshots', 'MeetingSnapshotController@addSnapshot');
        Route::get('snapshots', 'MeetingSnapshotController@getSnapshots');
        Route::delete('snapshots/{uuid}', 'MeetingSnapshotController@deleteSnapshot');

        Route::post('recordings', 'MeetingRecordingController@storeRecording');
        Route::get('recordings', 'MeetingRecordingController@getRecordings');
        Route::delete('recordings/{uuid}', 'MeetingRecordingController@deleteRecording');

        Route::post('cover', 'MeetingCoverController@upload');
        Route::delete('cover', 'MeetingCoverController@remove');

        Route::get('invitees/pre-requisite', 'InviteeController@preRequisite');
        Route::get('invitees', 'InviteeController@getInvitees');
        Route::post('invitees', 'InviteeController@addInvitees');

        Route::post('moderator', 'InviteeController@toggleModerator');
        Route::post('keep-alive', 'InviteeController@keepAlive');
        Route::post('invitation', 'InviteeController@sendInvitation');
        Route::post('invitees/{uuid}/block', 'InviteeController@blockInvitee');
        Route::post('invitees/{uuid}/unblock', 'InviteeController@unblockInvitee');
        Route::delete('invitees/{uuid}', 'InviteeController@deleteInvitee');

        // Route::post('join', 'InviteeController@join');
        // Route::post('leave', 'InviteeController@leave');
        Route::post('end', 'InviteeController@end');
        Route::post('alert', 'InviteeController@alert');
        Route::post('joining-request-response', 'InviteeController@joiningRequestResponse');

        Route::get('media/pre-requisite', 'MeetingMediaController@mediaPreRequisite');
        Route::post('media', 'MeetingMediaController@addMedia');
        Route::delete('media/{uuid}', 'MeetingMediaController@removeMedia');

        Route::get('comments/pre-requisite', 'MeetingCommentController@preRequisite');
        Route::get('comments', 'MeetingCommentController@index');
        Route::post('comments', 'MeetingCommentController@store');
        Route::post('comments/{comment}/media', 'MeetingCommentController@addMedia');
        Route::delete('comments/{comment}/media/{uuid}', 'MeetingCommentController@removeMedia');

        Route::prefix('payment')->group(function () {
            Route::get('pre-requisite', 'MeetingPaymentController@preRequisite');
            Route::get('receipt', 'MeetingPaymentController@receipt');
            Route::get('', 'MeetingPaymentController@index');
            Route::get('{uuid}', 'MeetingPaymentController@show');
            Route::post('', 'MeetingPaymentController@payment');
            Route::post('calculate', 'MeetingPaymentController@calculate');
        });
    });

    Route::prefix('chat')->group(function() {
        Route::get('pre-requisite', 'ChatController@preRequisite');
        Route::get('rooms', 'ChatController@getRooms');
        Route::get('rooms/search', 'ChatController@searchRoom');
        Route::get('rooms/{uuid}', 'ChatController@getRoom');
        Route::patch('rooms/{uuid}', 'ChatController@editRoom');
        Route::delete('rooms/{uuid}', 'ChatController@deleteRoom');
        Route::post('rooms/{uuid}/clear', 'ChatController@clear');
        Route::post('rooms', 'ChatController@createRoom')->name('chat.create-room');
        Route::post('rooms/{uuid}/sync', 'ChatController@syncMember')->name('chat.sync-member')->middleware(['role:admin']);
        Route::get('rooms/{uuid}/members', 'ChatController@listMember')->name('chat.list-member');
        Route::post('rooms/{uuid}/members', 'ChatController@addMember')->name('chat.add-member');
        Route::delete('rooms/{uuid}/members', 'ChatController@removeMember')->name('chat.remove-member');
        Route::get('messages', 'ChatController@getMessage');
        Route::post('messages', 'ChatController@storeMessage');
        Route::get('messages/search', 'ChatController@searchMessage');
    });

    // KYC Routes - Disabled
    // Route::prefix('kyc')->group(function () {
    //     Route::post('session/start', 'KycController@startSession');
    //     Route::post('document/verify', 'KycController@verifyDocument');
    //     Route::post('verify/complete', 'KycController@completeVerification');
    //     Route::get('status/{session_id}', 'KycController@getStatus');
    // });

    // Admin KYC Routes - Disabled
    // Route::prefix('admin/kyc')->group(function () {
    //     Route::get('requests', 'Admin\KycRequestController@index');
    //     Route::get('requests/{id}/document', 'Admin\KycRequestController@getDocument');
    //     Route::post('requests/{id}/approve', 'Admin\KycRequestController@approve');
    //     Route::post('requests/{id}/reject', 'Admin\KycRequestController@reject');
    // });

    // Period Tracker Routes
    Route::prefix('period-tracker')->group(function () {
        Route::get('cycles', 'PeriodTrackerController@getCycles');
        Route::post('cycles', 'PeriodTrackerController@saveCycle');
        Route::delete('cycles/{id}', 'PeriodTrackerController@deleteCycle');
        Route::get('predictions', 'PeriodTrackerController@getPredictions');
        Route::post('symptoms', 'PeriodTrackerController@logSymptoms');
        Route::get('symptoms', 'PeriodTrackerController@getSymptoms');
    });

    // Utility Routes
    Route::namespace('Utility')->prefix('utility')->group(function () {
        Route::post('todos/{uuid}/status', 'TodoController@toggleStatus');
        Route::apiResource('todos', 'TodoController');
    });

    // Site Routes
    Route::namespace('Site')->prefix('site')->group(function () {
        Route::get('pages/pre-requisite', 'PageController@preRequisite');
        Route::post('pages/{uuid}/media', 'PageController@addMedia');
        Route::delete('pages/{uuid}/media', 'PageController@removeMedia');
        Route::apiResource('pages', 'PageController');

        Route::apiResource('queries', 'QueryController')->only(['index', 'show', 'destroy']);
        Route::apiResource('subscribers', 'SubscriberController')->only(['index', 'show', 'destroy']);
    });

    Route::get('services/soketi/status', 'ServiceController@soketiStatus');
    Route::get('services/signal/status', 'ServiceController@signalStatus');
    Route::get('services/ice/status', 'ServiceController@iceStatus');

    Route::prefix('membership')->group(function () {
        Route::get('pre-requisite', 'MembershipController@preRequisite');
        Route::get('', 'MembershipController@index');
        Route::get('{uuid}', 'MembershipController@show');
        Route::post('', 'MembershipController@payment');
        Route::post('calculate', 'MembershipController@calculate');
    });
});

Broadcast::routes(["middleware" => ['auth:sanctum', '2fa']]);

//Fallback route
Route::fallback(function () {
    return response()->json(['message' => trans('general.api_not_found')], 404);
});
