<!DOCTYPE html>
<html lang="{{config('config.system.locale') ?? 'en'}}" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Tell the browser to be responsive to screen width -->
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta content="{{ config('config.basic.app_desc') ?? 'Application by KodeMint' }}" name="description">
        <meta name="author" content="{{ config('config.basic.seo_author') ?? 'KodeMint' }}">
        <title>{{ config('config.basic.app_name') }}</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ config('config.assets.icon_180') ?? config('config.assets.icon') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ config('config.assets.icon_32') ?? config('config.assets.favicon') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ config('config.assets.icon_16') ?? config('config.assets.favicon') }}">
        <link rel="shortcut icon" href="{{ config('config.assets.favicon') }}">
        <meta name="theme-color" content="{{ config('config.basic.app_theme_color') }}"/>
        <meta name="apple-mobile-web-app-title" content="{{ config('config.basic.app_name') }}">
        <meta name="application-name" content="{{ config('config.basic.app_name') }}">

        @production
        <link rel="manifest" href="/site.webmanifest">
        @endproduction

        <script src="/js/env"></script>
        <script src="/js/lang?locale={{ request()->get('locale') ?? '' }}"></script>
        {{
            Vite::useBuildDirectory('.')
                ->useManifestFilename('app-manifest.json')
                ->withEntryPoints(['resources/sass/style.scss', 'resources/js/app.js'])
        }}

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="/js/html5shiv.js"></script>
            <script src="/js/respond.min.js"></script>
        <![endif]-->
        @production
        @if(config('config.system.enable_aths_alert'))
            <style type="text/css" media="screen">
                .aths-alert-backdrop {
                    display: none;
                }
            </style>
        @endif
        @endproduction
        <link href="/css/custom.css" rel="stylesheet">
        <link href="/css/glassmorphism-login.css" rel="stylesheet">
        <link href="/css/flo-health-design.css" rel="stylesheet">
        <link href="/css/face-processing-fix.css" rel="stylesheet">
        <link href="/css/unified-plus-menu.css" rel="stylesheet">
        <link href="/css/kosmobot-chatbot.css" rel="stylesheet">

        <!-- Fix Scripts -->
        <script src="{{ asset('js/registration-form-fix.js') }}"></script>
        <script src="{{ asset('js/password-validation-fix.js') }}"></script>
        <script src="{{ asset('js/login-form-fix.js') }}"></script>
        <script src="{{ asset('js/translation-fix.js') }}"></script>
        <script src="{{ asset('js/base-input-fix.js') }}"></script>
    </head>
    <body class="flo-design">

        <noscript>
            <div>
                <h2>JavaScript is disabled!</h2>
                <p>Without JavaScript this application will not work!</p>
                <a href="https://kodemintserviceshelp.freshdesk.com/support/home">Click here if you need help!</a>
            </div>
        </noscript>

        <div id="root">
            <router-view></router-view>
        </div>

        @production
        @if(config('config.system.enable_aths_alert'))
        <div class="aths-alert-backdrop">
            <div class="aths-alert-wrapper">
                <button type="button" class="aths-alert-action-cancel">X</button>
                <div class="aths-alert">
                    <h2>{{ config('config.basic.app_name') }}</h2>
                    <p class="text-muted">{{ trans('misc.aths_msg') }}</p>
                </div>
                <div class="aths-alert-action d-flex justify-content-end">
                    <button type="button" class="btn btn-primary aths-alert-action-btn">{{ trans('misc.add_to_home_screen') }}</button>
                </div>
            </div>
        </div>

        <script src="/js/aths.js"></script>
        @endif
        @endproduction

        <!-- Face Processing Scripts -->
        <script src="{{ asset('js/face-api.min.js') }}"></script>
        <script src="{{ asset('js/face-processing-plugin.js') }}"></script>

        <!-- Single KYC Button Script -->
        <script src="{{ asset('js/single-kyc-button.js') }}"></script>

        <!-- KosmoBot Chatbot Integration -->
        <script src="{{ asset('js/kosmobot-chatbot.js') }}"></script>
        <script src="{{ asset('js/kosmobot-chatbot-fallback.js') }}"></script>
    </body>
</html>
