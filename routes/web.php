<?php

use App\Helpers\SysHelper;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

// custom test route
Route::get('/test', function () {
    return 'Laravel is working!';
});

Route::get('/cache', function () {
    \Artisan::call('cache:clear');
    return 'Server cache is cleared!';
});

Route::get('/migrate', function () {

    if (\Auth::check() && \Auth::user()->hasRole('admin')) {

        \Artisan::call('migrate', [
            '--force' => true,
        ]);

        \Artisan::call('optimize:clear');

        return 'Database migrated successfully!';
    }

    return 'Database migration failed!';
});

Route::get('locale/{locale}/sync', 'Config\LocaleController@sync');

Route::get('permission/sync', function() {
    \Artisan::call('sync:permission', ['--force' => true]);
    return 'Permission synched.';
});

Route::get('/', function() {
    return response()->json([
        'message' => 'KosmoHealth API Server',
        'version' => '1.0.0',
        'status' => 'running',
        'endpoints' => [
            'api_docs' => '/api-docs',
            'meetings' => '/api/meetings',
            'auth' => '/api/auth',
            'users' => '/api/users'
        ],
        'documentation' => 'Visit /api-docs for complete API documentation'
    ]);
});

// Flo Health inspired landing page
Route::get('/flo', function() {
    return view('site.flo-landing');
});

// Modern Enhanced Landing Page
Route::get('/modern', function() {
    return view('site.modern-landing', [
        'title' => 'KosmoHealth - Modern Healthcare Platform'
    ]);
});

// Enhanced KosmoHealth Landing (can replace main route)
Route::get('/enhanced', function() {
    return view('site.modern-landing', [
        'title' => 'KosmoHealth - Enhanced Healthcare Experience'
    ]);
});

Route::middleware('site_enabled')->group(function() {

    Route::get('/about', function() {
        if (config('config.website.enable_about_page')) {
            return view('site.about');
        } else {
            return redirect('/');
        }
    });

    Route::get('/events', 'EventController@index');
    Route::get('/events/{slug}/{uuid}', 'EventController@show');

    Route::get('/faq', function() {
        if (config('config.website.enable_faq_page')) {
            return view('site.faq');
        } else {
            return redirect('/');
        }
    });

    Route::get('/contact', function() {
        if (config('config.website.enable_contact_page')) {
            return view('site.contact');
        } else {
            return redirect('/');
        }
    });
});

// ENV route
Route::get('/js/env', function () {
    $cache_name = 'env'.'.js';

    if (App::environment('local')) {
        Cache::forget($cache_name);
    }

    $strings = Cache::remember($cache_name, 43200, function () {
        $strings = [];

        $strings['name'] = env('APP_NAME');
        $strings['url'] = config('app.url');
        $strings['env'] = config('app.env');
        $strings['kmon'] = env('APP_KMON') ? true : false;

        if(env('APP_DSS')) {
            $strings['dss'] = true;
        }

        if(env('APP_MODE') === 'test') {
            $strings['test_mode'] = true;
        }

        $strings['gaid'] = config('config.system.ga_tracking_id') ?? env('GA_TRACKING_ID');
        $strings['version'] = SysHelper::getApp('VERSION');

        return $strings;
    });
    header('Content-Type: text/javascript');
    echo ('window.kmenv = ' . json_encode($strings) . ';');
    exit();
})->name('assets.env');

// language route
Route::get('/js/lang/{clear?}', function (Request $request, $clear = false) {
    $lang = config('config.system.locale') ?? 'en';

    $request_lang = $request->input('locale');
    if($request_lang) {
        $lang = $request_lang;
    }

    $cache_name = 'lang-'. $lang .'.js';

    if (App::environment('local') || $clear) {
        Cache::forget($cache_name);
    }

    $strings = Cache::remember($cache_name, 86400, function () use ($lang) {
        $files = glob(base_path('lang/' . $lang . '/*.php'));
        $strings = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $strings[$name] = require $file;
        }
        return $strings;
    });
    header('Content-Type: text/javascript');
    echo ('window.locale = ' . json_encode($strings) . ';');
    exit();
})->name('assets.lang');

// Manifest route
Route::get('/site.webmanifest', function () {
    $cache_name = 'site.webmanifest';

    if (App::environment('local')) {
        Cache::forget($cache_name);
    }

    $strings = Cache::remember($cache_name, 43200, function () {
        $strings = [];
        $icons = [];
        $screenshots = [];
        $shortcuts = [];

        $icons[] = array(
            "src" => config('config.assets.icon_192'),
            "sizes" => "192x192",
            "type" => "image/png"
        );

        $icons[] = array(
            "src" => config('config.assets.icon_512'),
            "sizes" => "512x512",
            "type" => "image/png"
        );

        $icons[] = array(
            "src" => config('config.assets.icon_maskable'),
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "maskable"
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_web_1'),
            "sizes" => "1440x900",
            "type" => "image/png",
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_web_2'),
            "sizes" => "1440x900",
            "type" => "image/png",
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_web_3'),
            "sizes" => "1440x900",
            "type" => "image/png",
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_mobile_1'),
            "sizes" => "720x1440",
            "type" => "image/png",
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_mobile_2'),
            "sizes" => "720x1440",
            "type" => "image/png",
        );

        $screenshots[] = array(
            "src" => config('config.assets.screenshot_mobile_3'),
            "sizes" => "720x1440",
            "type" => "image/png",
        );

        $shortcuts[] = array(
            'name' => trans('meetings.start_a_meeting'),
            'url' => '/app/panel/instant-meetings/start',
            'icons' => array(
                array(
                    "src" => '/images/icons/shortcut.png',
                    "sizes" => "96x96",
                    "type" => "image/png",
                )
            )
        );

        $shortcuts[] = array(
            'name' => trans('meetings.meetings'),
            'url' => '/app/panel/meetings',
            'icons' => array(
                array(
                    "src" => '/images/icons/shortcut.png',
                    "sizes" => "96x96",
                    "type" => "image/png",
                )
            )
        );

        $shortcuts[] = array(
            'name' => trans('meetings.join_a_meeting'),
            'url' => '/app/panel/instant-meetings/join',
            'icons' => array(
                array(
                    "src" => '/images/icons/shortcut.png',
                    "sizes" => "96x96",
                    "type" => "image/png",
                )
            )
        );

        $strings['name'] = config('config.basic.app_name');
        $strings['short_name'] = config('config.basic.app_name');
        $strings['description'] = config('config.basic.app_desc');
        $strings['icons'] = $icons;
        $strings['screenshots'] = $screenshots;
        $strings['shortcuts'] = $shortcuts;
        $strings['theme_color'] = config('config.basic.app_theme_color');
        $strings['background_color'] = config('config.basic.app_background_color');
        $strings['start_url'] = config('config.basic.app_start_url');
        $strings['scope'] = config('config.basic.app_scope');
        // $strings['start_url'] = config('app.url').'/app';
        // $strings['start_url'] = '.';
        // $strings['scope'] = '/app';
        $strings['display'] = 'standalone';
        $strings['orientation'] = 'portrait';
        $strings['categories'] = ['utilities', 'business', 'education', 'productivity'];

        return $strings;
    });
    header('Content-Type: application/manifest+json');
    echo (json_encode($strings));
    exit();
})->name('assets.manifest');

Route::group(['middleware' => ['guest']], function () {
    Route::get('auth/login/{provider}', 'Auth\SocialLoginController@redirectToProvider');
    Route::get('auth/login/{provider}/callback', 'Auth\SocialLoginController@handleProviderCallback');
});

Route::group(['middleware' => ['auth:sanctum']], function () {

});

Route::get('/m/{identifier}', 'InviteeController@goToMeeting');
Route::get('/meetings/{meeting}/downloads/{media_uuid}', 'MeetingMediaController@download');

Route::post('/paddle-membership', function() {
    //
});

Route::post('/paddle-payment', function() {
    //
});
Route::get('/media/{media}/{conversion?}', SignedMediaController::class)->name('media');


Route::get('/whiteboard', function (Request $request) {
    \Log::info('Whiteboard request headers:', $request->headers->all());

    // Remove the iframe restriction that was causing 403 errors
    // if ($request->server('HTTP_SEC_FETCH_DEST') !== 'iframe') {
    //     \Log::warning('Whiteboard access denied: not in iframe');
    //     return abort(403, 'Unauthorized.');
    // }

    return view('whiteboard');
});

// API Documentation Routes
Route::get('/api-docs', function () {
    return view('api-docs');
});

Route::get('/docs/api-swagger.yaml', function () {
    $path = base_path('docs/api-swagger.yaml');
    if (!file_exists($path)) {
        abort(404, 'Swagger documentation not found');
    }
    return response()->file($path, [
        'Content-Type' => 'application/x-yaml'
    ]);
});

Route::get('/offline', function () {
    return view('offline');
});

// API-only routes - frontend removed
// Route::get('/app/{vue?}', function () {
//     return view('app');
// })->where('vue', '[\/\w\.-]*')->name('app');

Route::prefix('pages')->namespace('Site')->group(function() {
    Route::get('{page?}', 'PageController@fetch')->where('page', '[\/\w\.-]*');
});

// Whiteboard sharing routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/whiteboards/{whiteboard}', 'WhiteboardController@show')
        ->name('whiteboards.show');

    Route::get('/whiteboards/{whiteboard}/share', 'WhiteboardController@showShareForm')
        ->name('whiteboards.share.form')
        ->middleware('can:share,whiteboard'); // Use Laravel's can middleware

    Route::post('/whiteboards/{whiteboard}/share', 'WhiteboardController@processShare')
        ->name('whiteboards.share.process')
        ->middleware('can:share,whiteboard'); // Use Laravel's can middleware
});









