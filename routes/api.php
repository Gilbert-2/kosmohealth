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

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Backend is running',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'database' => 'connected'
    ]);
});

// API root endpoint
Route::get('/', function () {
    return response()->json([
        'message' => 'KosmoHealth API',
        'version' => '1.0.0',
        'status' => 'active',
        'endpoints' => [
            'health' => '/api/health',
            'auth' => '/api/auth/*',
            'admin' => '/api/admin/*'
        ]
    ]);
});

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

    // Test pregnancy endpoint without auth
    Route::get('pregnancy/test', 'PregnancyTrackerController@test');

    // Pregnancy placeholder images
    Route::get('pregnancy/week-image/{week}', 'PregnancyTrackerController@getWeekImage');

// API Documentation routes (public access)
Route::prefix('docs')->group(function () {
    Route::get('api', 'DocsController@swagger');
    Route::get('api.yaml', 'DocsController@swaggerYaml');
    Route::get('api.json', 'DocsController@swaggerJson');
});

// Public health resources (available without auth)
Route::get('resources', '\App\\Http\\Controllers\\ResourcesController@index');
Route::get('resources/{uuid}', '\App\\Http\\Controllers\\ResourcesController@show');

// Public backend aliases for Next.js proxy
Route::prefix('backend')->group(function () {
    Route::get('resources', '\App\\Http\\Controllers\\ResourcesController@index');
    Route::get('resources/{uuid}', '\App\\Http\\Controllers\\ResourcesController@show');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    // Backend alias prefix for frontend proxy compatibility
    Route::prefix('backend')->group(function () {
        Route::prefix('admin')->group(function () {
            Route::namespace('Admin')->prefix('consultations')->group(function () {
                Route::get('settings', 'ConsultationSettingsAdminController@show');
                Route::put('settings', 'ConsultationSettingsAdminController@update');
                Route::post('bookings/{id}/assign', 'ConsultationAdminController@assign');
                Route::post('bookings/{id}/schedule', 'ConsultationAdminController@schedule');
                Route::post('bookings/{id}/status', 'ConsultationAdminController@updateStatus');
            });
        });
    });

    // Auth Routes
    Route::namespace('Auth')->prefix('auth')->group(function () {
        Route::post('logout', 'LoginController@logout');
        Route::post('lock', 'LockScreen');
        Route::get('user', 'UserController@me');
        Route::get('profile', 'UserController@me'); // Alias for getting current user profile
        Route::post('profile', 'UserController@updateProfile');
        Route::post('security', 'TwoFactorSecurity');
    });

    // Period Tracker Routes (without health data access middleware)
    Route::get('period-tracker/test', 'PeriodTrackerController@test');
    Route::get('period-tracker/cycles', 'PeriodTrackerController@getCycles');
    Route::post('period-tracker/cycles', 'PeriodTrackerController@saveCycle');
    Route::post('period-tracker/data', 'PeriodTrackerController@handlePeriodData'); // New endpoint for frontend format
    Route::delete('period-tracker/cycles/{id}', 'PeriodTrackerController@deleteCycle');
    Route::get('period-tracker/cycles/{id}', 'PeriodTrackerController@getCycle');
    Route::put('period-tracker/cycles/{id}', 'PeriodTrackerController@updateCycle');
    Route::get('period-tracker/predictions', 'PeriodTrackerController@getPredictions');
    Route::get('period-tracker/symptoms', 'PeriodTrackerController@getAvailableSymptoms');
    Route::post('period-tracker/symptoms', 'PeriodTrackerController@logSymptoms');
    Route::get('period-tracker/calendar-data', 'PeriodTrackerController@getCalendarData');
    Route::get('period-tracker/analytics', 'PeriodTrackerController@getAnalytics');
    Route::get('period-tracker/dashboard-data', 'PeriodTrackerController@getDashboardData');
    Route::post('period-tracker/debug', 'PeriodTrackerController@debugRequest');

    // Pregnancy Tracker Routes
    Route::prefix('pregnancy')->group(function () {
        Route::get('overview', 'PregnancyTrackerController@getOverview');
        Route::get('predictions', 'PregnancyTrackerController@getPredictions');
        Route::post('start', 'PregnancyTrackerController@startPregnancy');
        Route::get('development', 'PregnancyTrackerController@getDevelopment');
        Route::get('appointments', 'PregnancyTrackerController@getAppointments');
        Route::post('appointments', 'PregnancyTrackerController@createAppointment');
        Route::delete('appointments/{id}', 'PregnancyTrackerController@deleteAppointment');
        Route::get('symptoms', 'PregnancyTrackerController@getSymptoms');
        Route::post('symptoms', 'PregnancyTrackerController@logSymptom');
        Route::get('health-metrics', 'PregnancyTrackerController@getHealthMetrics');
        Route::post('health-metrics', 'PregnancyTrackerController@logHealthMetric');
        Route::get('timeline', 'PregnancyTrackerController@getTimeline');
    });

    // Enhanced Secure Period Tracker Routes
    Route::prefix('period-tracker')->middleware(['health_data_access'])->group(function () {
        // Core period tracking
        Route::get('secure-data', 'Api\PeriodTrackerController@getSecureData');
        Route::post('log-period-start', 'Api\PeriodTrackerController@logPeriodStart');
        Route::post('log-period-end', 'Api\PeriodTrackerController@logPeriodEnd');
        Route::post('log-symptoms', 'Api\PeriodTrackerController@logSymptoms');
        
        // AI insights and analytics
        Route::post('refresh-insights', 'Api\PeriodTrackerController@refreshInsights');
        Route::get('cycle-predictions', 'Api\PeriodTrackerController@getCyclePredictions');
        Route::get('health-analytics', 'Api\PeriodTrackerController@getHealthAnalytics');
        
        // Data export and privacy
        Route::get('export-data', 'Api\PeriodTrackerController@exportData');
        Route::delete('delete-data', 'Api\PeriodTrackerController@deleteAllData');
        Route::post('consent', 'Api\PeriodTrackerController@updateConsent');
        
        // Modal System Routes - Direct API access (moved outside middleware group)
    });
    
    // Security audit routes - Commented out until SecurityAuditController is created
    // Route::prefix('audit')->middleware(['auth', 'admin'])->group(function () {
    //     Route::post('health-dashboard', 'Api\SecurityAuditController@logHealthDashboard');
    //     Route::post('emotion-detection-access', 'Api\SecurityAuditController@logEmotionDetection');
    //     Route::post('draggable-interaction', 'Api\SecurityAuditController@logDraggableInteraction');
    // });
    // Secure Period Tracker routes - Commented out until SecurePeriodTrackerController is created
    // Route::namespace('Api')->prefix('period-tracker')->middleware(['health.data.access'])->group(function () {
    //     // Secure floating button endpoints
    //     Route::get('current-status', 'SecurePeriodTrackerController@getCurrentStatus')
    //         ->middleware('throttle:health-data-access');
    //     Route::get('notifications-count', 'SecurePeriodTrackerController@getNotificationsCount')
    //         ->middleware('throttle:health-data-access');
    //     
    //     // Secure dashboard integration
    //     Route::get('dashboard-data', 'SecurePeriodTrackerController@getDashboardData')
    //         ->middleware('throttle:health-data-access');
    //     
    //     // Secure main popup functionality
    //     Route::get('secure-analytics', 'SecurePeriodTrackerController@getSecureAnalytics')
    //         ->middleware('throttle:health-analytics');
    //     Route::get('secure-recommendations', 'SecurePeriodTrackerController@getSecureRecommendations')
    //         ->middleware('throttle:health-data-access');
    //     Route::get('secure-status', 'SecurePeriodTrackerController@getCurrentStatus')
    //         ->middleware('throttle:health-data-access');
    //     Route::get('notifications', 'SecurePeriodTrackerController@getNotifications')
    //         ->middleware('throttle:health-data-access');
    //     
    //     // Secure actions with additional verification
    //     Route::post('log-symptoms', 'SecurePeriodTrackerController@logSymptoms')
    //         ->middleware(['throttle:health-actions', 'verify.password']);
    //     Route::post('quick-action', 'SecurePeriodTrackerController@handleQuickAction')
    //         ->middleware('throttle:health-actions');
    //     
    //     // High-security operations
    //     Route::post('secure-export', 'SecurePeriodTrackerController@secureExport')
    //         ->middleware(['throttle:data-export', 'verify.identity']);
    //     Route::post('secure-backup', 'SecurePeriodTrackerController@createSecureBackup')
    //         ->middleware(['throttle:data-export', 'verify.identity']);
    //     
    //     // Security audit endpoints - Commented out until SecurityController is created
    //     // Route::get('security-status', 'SecurityController@getSecurityStatus')
    //     //     ->middleware('throttle:security-checks');
    //     // Route::get('audit-log', 'SecurityController@getAuditLog')
    //     //     ->middleware(['throttle:audit-access', 'role:admin|user']);
    // });

    // Health emergency endpoints - Commented out until HealthController is created
    // Route::namespace('Api')->prefix('health')->group(function () {
    //     Route::post('emergency-contact', 'HealthController@handleEmergencyContact')
    //         ->middleware('throttle:emergency');
    // });
});

// Meeting routes - Commented out until MeetingController and InviteeController are created
// Route::prefix('meetings')->group(function() {
//     Route::get('pam/{identifier}', 'MeetingController@showPam');
//
//     Route::prefix('{meeting}')->group(function() {
//         Route::get('summary', 'MeetingController@summary');
//         Route::get('pam', 'MeetingController@pam');
//         Route::post('join', 'InviteeController@join');
//         Route::post('leave', 'InviteeController@leave');
//         Route::post('joining-request', 'InviteeController@joiningRequest');
//     });
//
//     Route::get('{meeting}/polls', 'MeetingPollController@index');
//     Route::get('{meeting}/polls/{poll}', 'MeetingPollController@show');
//     Route::post('{meeting}/polls/{poll}/vote', 'MeetingPollController@vote');
// });

// Contact Form (Public - No Authentication Required)
Route::post('contact', 'ContactController@submit');
Route::get('contact/info', 'ContactController@getContactInfo');
Route::get('contact/config', 'ContactController@getConfig');

// Public content endpoint for dashboard usage
Route::get('content/articles', 'DashboardContentController@getPublicArticles');

// Community Stories (Public - No Authentication Required)
Route::get('community/stories', 'CommunityStoriesController@getPublicStories');
Route::get('community/stories/metadata', 'CommunityStoriesController@getStoriesMetadata');
Route::get('community/stories/{uuid}', 'CommunityStoriesController@getPublicStory');

Route::group(['middleware' => ['auth:sanctum', 'under_maintenance']], function () {

    // Auth Routes
    Route::namespace('Auth')->prefix('auth')->group(function () {
        Route::get('user', 'UserController@me');
        Route::post('update-role', 'UserController@updateAuthUserRole');
        Route::post('change-password', 'ChangePassword');
    });

    // User Routes
    Route::namespace('Auth')->prefix('user')->group(function () {
        Route::post('preference', 'UserController@preference');
    });

    Route::namespace('Auth')->group(function () {
        Route::get('users/pre-requisite', 'UserController@preRequisite');
        Route::get('users/subscriptions', 'UserController@getSubscriptions');
        Route::post('users/{user}/status', 'UserController@updateStatus');
        Route::post('users/{user}/premium', 'UserController@premium');
        Route::post('users/{user}/role', 'UserController@updateRole');
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

    // Dashboard Content (Articles & Products)
    Route::get('dashboard/content', 'DashboardContentController@getDashboardContent');
    Route::get('dashboard/articles', 'DashboardContentController@getArticles');
    Route::get('dashboard/products', 'DashboardContentController@getProducts');
    Route::get('dashboard/product-categories', 'DashboardContentController@getProductCategories');

    // Dashboard Content (New Content Types)
    Route::get('dashboard/testimonials', 'DashboardContentController@getTestimonials');
    Route::get('dashboard/partners', 'DashboardContentController@getPartners');
    Route::get('dashboard/team-members', 'DashboardContentController@getTeamMembers');
    Route::get('dashboard/about-us', 'DashboardContentController@getAboutUs');
    Route::get('dashboard/about-us/complete', 'DashboardContentController@getCompleteAboutUs');

    // Community Stories (Authenticated User Actions)
    Route::post('community/stories/{uuid}/like', 'CommunityStoriesController@toggleLike');
    Route::post('community/stories/{uuid}/report', 'CommunityStoriesController@reportStory');

    // User Stories Management
    Route::prefix('user/stories')->group(function () {
        Route::get('', 'UserStoriesController@getUserStories');
        Route::post('', 'UserStoriesController@createStory');
        Route::get('{uuid}', 'UserStoriesController@getUserStory');
        Route::put('{uuid}', 'UserStoriesController@updateStory');
        Route::delete('{uuid}', 'UserStoriesController@deleteStory');
        Route::post('{uuid}/submit', 'UserStoriesController@submitStory');
    });



    // Admin Routes (aliases for admin-facing frontend)
    Route::prefix('admin')->group(function () {
        // Admin Dashboard
        Route::namespace('Admin')->group(function () {
            Route::get('dashboard', 'AdminDashboardController@index');
            Route::get('system-health', 'AdminDashboardController@systemHealth');
        });

        // Admin stats → reuse dashboard stats implementation
        Route::get('stats', 'DashboardController@getStats');

        // Admin meetings → reuse meeting index implementation
        Route::get('meetings', 'MeetingController@index');

        // Admin KYC
        Route::prefix('kyc')->group(function () {
            Route::get('requests', 'KycAdminController@getRequests');
            Route::post('{id}/approve', 'KycAdminController@approve');
            Route::post('{id}/reject', 'KycAdminController@reject');
        });

        // Admin Health Data Management
        Route::prefix('health-data')->namespace('Admin')->group(function () {
            Route::get('overview', 'HealthDataController@overview');
            Route::get('period-cycles', 'HealthDataController@periodCycles');
            Route::get('pregnancy-records', 'HealthDataController@pregnancyRecords');
            Route::get('analytics', 'HealthDataController@analytics');
            Route::post('export', 'HealthDataController@export');
        });

        // Admin Content Management
        Route::prefix('content')->namespace('Admin')->group(function () {
            // Articles
            Route::get('articles', 'ContentManagementController@getArticles');
            Route::post('articles', 'ContentManagementController@createArticle');
            Route::put('articles/{uuid}', 'ContentManagementController@updateArticle');
            Route::delete('articles/{uuid}', 'ContentManagementController@deleteArticle');
            Route::post('articles/reorder', 'ContentManagementController@reorderArticles');

            // Products
            Route::get('products', 'ContentManagementController@getProducts');
            Route::post('products', 'ContentManagementController@createProduct');
            Route::put('products/{uuid}', 'ContentManagementController@updateProduct');
            Route::delete('products/{uuid}', 'ContentManagementController@deleteProduct');
            Route::post('products/reorder', 'ContentManagementController@reorderProducts');

            // Testimonials
            Route::get('testimonials', 'ContentManagementController@getTestimonials');
            Route::post('testimonials', 'ContentManagementController@createTestimonial');
            Route::put('testimonials/{uuid}', 'ContentManagementController@updateTestimonial');
            Route::delete('testimonials/{uuid}', 'ContentManagementController@deleteTestimonial');

            // Partners
            Route::get('partners', 'ContentManagementController@getPartners');
            Route::post('partners', 'ContentManagementController@createPartner');
            Route::put('partners/{uuid}', 'ContentManagementController@updatePartner');
            Route::delete('partners/{uuid}', 'ContentManagementController@deletePartner');

            // Team Members
            Route::get('team-members', 'ContentManagementController@getTeamMembers');
            Route::post('team-members', 'ContentManagementController@createTeamMember');
            Route::put('team-members/{uuid}', 'ContentManagementController@updateTeamMember');
            Route::delete('team-members/{uuid}', 'ContentManagementController@deleteTeamMember');

            // About Us
            Route::get('about-us', 'ContentManagementController@getAboutUs');
            Route::post('about-us', 'ContentManagementController@createOrUpdateAboutUs');
            Route::put('about-us/{uuid}', 'ContentManagementController@updateAboutUs');

            // Contact Messages
            Route::get('contact-messages', 'ContentManagementController@getContactMessages');
            Route::get('contact-messages/{uuid}', 'ContentManagementController@getContactMessage');
            Route::put('contact-messages/{uuid}', 'ContentManagementController@updateContactMessage');
            Route::delete('contact-messages/{uuid}', 'ContentManagementController@deleteContactMessage');
            Route::post('contact-messages/{uuid}/retry-email', 'ContentManagementController@retryContactEmail');

            // Contact Info
            Route::get('contact-info', 'ContentManagementController@getContactInfo');
            Route::post('contact-info', 'ContentManagementController@createOrUpdateContactInfo');
            Route::get('contact-info/{uuid}', 'ContentManagementController@showContactInfo');
            Route::delete('contact-info/{uuid}', 'ContentManagementController@deleteContactInfo');

            // Community Stories Management
            Route::get('stories/pending', 'ContentManagementController@getPendingStories');
            Route::get('stories/orphaned', 'ContentManagementController@getOrphanedPublicStories');
            Route::get('stories', 'ContentManagementController@getAllStories');
            Route::get('stories/deletion-history', 'ContentManagementController@getStoryDeletionHistory');
            Route::get('stories/{uuid}', 'ContentManagementController@getStoryForReview');
            Route::post('stories/{uuid}/approve', 'ContentManagementController@approveStory');
            Route::post('stories/{uuid}/reject', 'ContentManagementController@rejectStory');

            // Admin-only story deletion routes
            Route::middleware('admin')->group(function () {
                Route::delete('stories/{uuid}', 'ContentManagementController@deleteUserStory');
                Route::delete('stories/bulk', 'ContentManagementController@bulkDeleteUserStories');
            });

            Route::get('public-stories', 'ContentManagementController@getPublicStories');
            Route::put('public-stories/{uuid}', 'ContentManagementController@updatePublicStory');
            Route::delete('public-stories/{uuid}', 'ContentManagementController@deletePublicStory');
            Route::get('story-reports', 'ContentManagementController@getStoryReports');

            // Cache management
            Route::post('clear-cache', 'ContentManagementController@clearCache');
        });

        // Admin User Management
        Route::prefix('users')->namespace('Admin')->group(function () {
            Route::get('/', 'UserManagementController@index');
            Route::get('stats', 'UserManagementController@getStats');
            Route::get('roles', 'UserManagementController@getRoles');
            Route::post('/', 'UserManagementController@store');
            Route::get('{user}', 'UserManagementController@show');
            Route::put('{user}/role', 'UserManagementController@updateRole');
            Route::put('{user}/status', 'UserManagementController@updateStatus');
            Route::delete('{user}', 'UserManagementController@destroy');
        });

        // Admin analytics → reuse dashboard chart implementation
        Route::get('analytics', 'DashboardController@getChart');

        // Admin general stats (alias to user stats)
        Route::get('stats', 'Admin\UserManagementController@getStats');

        // Admin Notifications
        Route::namespace('Admin')->prefix('notifications')->group(function () {
            Route::get('', 'NotificationsAdminController@index');
            Route::post('', 'NotificationsAdminController@store');
            Route::get('{id}', 'NotificationsAdminController@show');
            Route::put('{id}', 'NotificationsAdminController@update');
            Route::delete('{id}', 'NotificationsAdminController@destroy');
            Route::post('broadcast', 'NotificationsAdminController@broadcast');
            Route::post('users', 'NotificationsAdminController@sendToUsers');
        });

        // Admin Consultation management
        Route::namespace('Admin')->prefix('consultations')->group(function () {
            Route::get('settings', 'ConsultationSettingsAdminController@show');
            Route::put('settings', 'ConsultationSettingsAdminController@update');
            Route::get('bookings', 'ConsultationAdminController@index');
            Route::post('bookings/{id}/assign', 'ConsultationAdminController@assign');
            Route::post('bookings/{id}/schedule', 'ConsultationAdminController@schedule');
            Route::post('bookings/{id}/status', 'ConsultationAdminController@updateStatus');
            Route::delete('bookings/{id}', 'ConsultationAdminController@destroy');
        });
    });

    // General API endpoints (for frontend compatibility)
    Route::get('users', function () {
        return response()->json([
            'message' => 'Use /api/admin/users for user management',
            'redirect' => '/api/admin/users'
        ], 302);
    });

    // Greeting Routes
    Route::get('greeting', 'GreetingController@getGreeting');
    // Notifications
    Route::get('notifications', 'NotificationsController@index');
    Route::post('notifications/{id}/read', 'NotificationsController@markRead');
    Route::post('notifications/{id}/unread', 'NotificationsController@markUnread');
    Route::post('notifications/read-all', 'NotificationsController@markAllRead');
    Route::delete('notifications/{id}', 'NotificationsController@destroy');

    // Notification Preferences
    Route::get('notification-preferences', 'NotificationPreferenceController@show');
    Route::put('notification-preferences', 'NotificationPreferenceController@update');
    Route::get('greeting/with-period-context', 'GreetingController@getGreetingWithPeriodContext');
    Route::get('greeting/dashboard', 'GreetingController@getDashboardGreeting');

    // Any key search
    Route::get('search', 'Search');

    // Upload Routes
    Route::prefix('uploads')->middleware('upload.rate_limit')->group(function () {
        Route::post('image', 'UploadController@image');
        Route::get('config', 'UploadController@getUploadConfig');
        Route::delete('image', 'UploadController@deleteImage');
    });

    // Storage image serving route (for secure access)
    Route::get('storage/{path}', 'SignedMediaController@serveStorageImage')
        ->where('path', '.*')
        ->name('storage.image');

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
    
    // User-specific meeting routes
    Route::prefix('user')->group(function () {
        Route::get('meetings/my-meetings', 'MeetingController@myMeetings');
        Route::get('meetings/pre-requisite', 'MeetingController@preRequisite');
    });
    // Meeting emotion detection lifecycle
    Route::prefix('meetings/{meeting}')->group(function() {
        Route::post('emotion/start', 'MeetingEmotionController@start');
        Route::post('emotion/events', 'MeetingEmotionController@events');
        Route::post('emotion/end', 'MeetingEmotionController@end');
        Route::get('emotion/report', 'MeetingEmotionController@report');
        Route::post('notify', 'MeetingController@notify');
    });

    // Simple REST signaling for WebRTC
    Route::prefix('meetings/{uuid}/webrtc')->group(function () {
        Route::post('offer', [\App\Http\Controllers\MeetingSignalingController::class, 'postOffer']);
        Route::get('offer', [\App\Http\Controllers\MeetingSignalingController::class, 'getOffer']);
        Route::post('answer', [\App\Http\Controllers\MeetingSignalingController::class, 'postAnswer']);
        Route::get('answer', [\App\Http\Controllers\MeetingSignalingController::class, 'getAnswer']);
        Route::post('candidates', [\App\Http\Controllers\MeetingSignalingController::class, 'postCandidate']);
        Route::get('candidates', [\App\Http\Controllers\MeetingSignalingController::class, 'getCandidates']);
    });
    Route::apiResource('meetings.chats', 'MeetingChatController')->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('meetings.polls', 'MeetingPollController')->only(['store', 'update', 'destroy']);
    Route::post('meetings/{meeting}/polls/{poll}/publish', 'MeetingPollController@publish');
    Route::post('meetings/{meeting}/polls/{poll}/publish/result', 'MeetingPollController@publishResult');

    // Host-Patient Management Routes
    Route::prefix('host')->group(function () {
        Route::get('patients', 'HostPatientController@getMyPatients');
        Route::get('patients/{patientId}', 'HostPatientController@getPatientDetails');
        Route::post('patients/assign', 'HostPatientController@assignPatient');
        Route::put('patients/{patientId}/status', 'HostPatientController@updatePatientStatus');
        Route::delete('patients/{patientId}', 'HostPatientController@removePatient');
        Route::get('available-hosts', 'HostPatientController@getAvailableHosts');
        Route::get('consultation-reasons', 'HostPatientController@getConsultationReasons');
        // Host-managed health resources
        Route::prefix('resources')->group(function () {
            Route::get('', '\App\\Http\\Controllers\\Host\\HostResourcesController@index');
            Route::post('', '\App\\Http\\Controllers\\Host\\HostResourcesController@store');
            Route::get('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@show');
            Route::patch('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@update');
            Route::delete('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@destroy');
        });
    });
// (moved public resources above for unauthenticated access)

    Route::prefix('meetings/{meeting}')->group(function() {
        Route::post('config', 'MeetingController@config');
        Route::post('snooze', 'MeetingController@snooze');
        Route::post('cancel', 'MeetingController@cancel');
        Route::post('snooze-end-time', 'MeetingController@snoozeEndTime');
        Route::post('cancel-auto-end', 'MeetingController@cancelAutoEnd');

        Route::post('summary', 'MeetingController@summary');
        Route::post('pam', 'MeetingController@pam');
        Route::post('join', 'InviteeController@join');
        Route::post('leave', 'InviteeController@leave');
        Route::post('joining-request', 'InviteeController@joiningRequest');
    });

    Route::get('{meeting}/polls', 'MeetingPollController@index');
    Route::get('{meeting}/polls/{poll}', 'MeetingPollController@show');
    Route::post('{meeting}/polls/{poll}/vote', 'MeetingPollController@vote');

    // Consultation routes
    Route::prefix('consultations')->group(function () {
        Route::get('settings', 'ConsultationSettingsController@show');
        Route::get('prerequisite', 'ConsultationController@preRequisite');
        Route::get('available-hosts', 'ConsultationController@getAvailableHosts');
        Route::post('', 'ConsultationController@store'); // Create consultation from booking
        // User bookings
        Route::get('bookings', 'UserConsultationBookingController@index');
        Route::post('bookings', 'UserConsultationBookingController@store');
        Route::get('bookings/{id}', 'UserConsultationBookingController@show');
        Route::delete('bookings/{id}', 'UserConsultationBookingController@destroy');
    });

    // Host routes (aliases for host-facing frontend)
    Route::prefix('host')->group(function () {
        Route::get('meetings/pre-requisite', 'MeetingController@preRequisite');
        Route::get('meetings', 'MeetingController@index');
        Route::post('meetings', 'MeetingController@store');
        Route::get('meetings/my-meetings', 'MeetingController@myMeetings');
        Route::get('meetings/{meeting}', 'MeetingController@show');
        Route::patch('meetings/{meeting}', 'MeetingController@update');
        Route::delete('meetings/{meeting}', 'MeetingController@destroy');

        // Host consultation bookings
        Route::get('consultations/bookings', 'HostConsultationController@myAssigned');
        Route::get('consultations/bookings/{id}', 'HostConsultationController@show');
        Route::post('consultations/bookings/{id}/link-meeting', 'HostConsultationController@linkMeeting');
        Route::delete('consultations/bookings/{id}', 'HostConsultationController@destroy');

        // Host-managed resources
        Route::prefix('resources')->group(function () {
            Route::get('', '\App\\Http\\Controllers\\Host\\HostResourcesController@index');
            Route::post('', '\App\\Http\\Controllers\\Host\\HostResourcesController@store');
            Route::get('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@show');
            Route::patch('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@update');
            Route::delete('{id}', '\App\\Http\\Controllers\\Host\\HostResourcesController@destroy');
            Route::post('{id}/publish', '\App\\Http\\Controllers\\Host\\HostResourcesController@publish');
        });
    });

    // Backend routes for frontend compatibility
    Route::prefix('backend')->group(function () {
        // Backend meeting routes
        Route::prefix('meetings')->group(function() {
            Route::get('', 'MeetingController@index');
            Route::get('{meeting}', 'MeetingController@show');
            Route::post('', 'MeetingController@store');
            Route::patch('{meeting}', 'MeetingController@update');
            Route::delete('{meeting}', 'MeetingController@destroy');
            // Backend alias to notify patient(s)
            Route::post('{meeting}/notify', 'MeetingController@notify');
            
            // Backend emotion detection routes
            Route::prefix('{meeting}')->group(function() {
                Route::post('emotion/start', 'MeetingEmotionController@start');
                Route::post('emotion/events', 'MeetingEmotionController@events');
                Route::post('emotion/end', 'MeetingEmotionController@end');
                Route::get('emotion/report', 'MeetingEmotionController@report');
            });
        });
        
        // Backend notification routes
        Route::get('notifications', 'NotificationsController@index');
        Route::post('notifications', 'NotificationsController@store');
        Route::get('notifications/{notification}', 'NotificationsController@show');
        Route::patch('notifications/{notification}', 'NotificationsController@update');
        Route::delete('notifications/{notification}', 'NotificationsController@destroy');

        // (moved backend resources aliases above for unauthenticated access)

        // Backend alias for admin user management (browser-friendly under /api/backend)
        Route::prefix('admin')->group(function () {
            Route::get('users', 'Admin\\UserManagementController@index');
            Route::post('users', 'Admin\\UserManagementController@store');
            Route::get('users/{user}', 'Admin\\UserManagementController@show');
            Route::put('users/{user}', 'Admin\\UserManagementController@update');
            Route::delete('users/{user}', 'Admin\\UserManagementController@destroy');
            Route::get('users/stats', 'Admin\\UserManagementController@getStats');
            Route::get('users/roles', 'Admin\\UserManagementController@getRoles');
            Route::post('users/{user}/role', 'Admin\\UserManagementController@updateRole');
            Route::post('users/{user}/status', 'Admin\\UserManagementController@updateStatus');
            Route::post('users/{user}/premium', 'Admin\\UserManagementController@updatePremium');
            Route::get('users/pre-requisite', 'Admin\\UserManagementController@preRequisite');
        });
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

// Public meeting identifier endpoint for proxies to avoid CORS on /m/{identifier}
Route::get('m/{identifier}', 'InviteeController@goToMeeting');
