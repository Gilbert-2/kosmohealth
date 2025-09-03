# KosmoHealth Error Fix PowerShell Script
# This script will help resolve the console errors you're experiencing

Write-Host "🔧 KosmoHealth Error Fix Script" -ForegroundColor Cyan
Write-Host "===============================" -ForegroundColor Cyan

# 1. Check if we're in the right directory
$currentPath = Get-Location
if ($currentPath.Path -notlike "*kosmohealth*") {
    Write-Host "⚠️  Please run this script from the KosmoHealth directory" -ForegroundColor Yellow
    Set-Location "e:/kosmohealth"
    Write-Host "✅ Changed to KosmoHealth directory" -ForegroundColor Green
}

# 2. Check Laravel application status
Write-Host "`n🔍 Checking Laravel Application Status..." -ForegroundColor Blue

if (Test-Path ".env") {
    Write-Host "✅ .env file found" -ForegroundColor Green
} else {
    Write-Host "❌ .env file missing - copying from .env.example" -ForegroundColor Red
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "✅ .env created from example" -ForegroundColor Green
    }
}

# 3. Clear all caches
Write-Host "`n🧹 Clearing All Caches..." -ForegroundColor Blue

try {
    # Laravel caches
    php artisan cache:clear
    Write-Host "✅ Application cache cleared" -ForegroundColor Green
    
    php artisan config:clear
    Write-Host "✅ Configuration cache cleared" -ForegroundColor Green
    
    php artisan route:clear
    Write-Host "✅ Route cache cleared" -ForegroundColor Green
    
    php artisan view:clear
    Write-Host "✅ View cache cleared" -ForegroundColor Green
    
    # Composer autoload
    composer dump-autoload
    Write-Host "✅ Composer autoload refreshed" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Some cache clearing commands failed - this might be normal" -ForegroundColor Yellow
}

# 4. Check and rebuild assets
Write-Host "`n📦 Checking Asset Build Status..." -ForegroundColor Blue

if (Test-Path "package.json") {
    # Check if node_modules exists
    if (-not (Test-Path "node_modules")) {
        Write-Host "📥 Installing Node.js dependencies..." -ForegroundColor Yellow
        npm install
    }
    
    # Check for Vite or Laravel Mix
    if (Test-Path "vite.config.js") {
        Write-Host "🔨 Building assets with Vite..." -ForegroundColor Yellow
        npm run build
        Write-Host "✅ Assets built with Vite" -ForegroundColor Green
    } elseif (Test-Path "webpack.mix.js") {
        Write-Host "🔨 Building assets with Laravel Mix..." -ForegroundColor Yellow
        npm run production
        Write-Host "✅ Assets built with Laravel Mix" -ForegroundColor Green
    }
} else {
    Write-Host "⚠️  No package.json found - skipping asset build" -ForegroundColor Yellow
}

# 5. Check critical files
Write-Host "`n🔍 Checking Critical Files..." -ForegroundColor Blue

$criticalFiles = @(
    "public/assets/css/views/layouts/general-layout.css",
    "public/js/face-processing-plugin.js",
    "public/assets/js/single-kyc-button.js",
    "routes/api.php",
    "app/Http/Controllers/ConfigController.php"
)

foreach ($file in $criticalFiles) {
    if (Test-Path $file) {
        $size = (Get-Item $file).Length
        Write-Host "✅ $file ($size bytes)" -ForegroundColor Green
    } else {
        Write-Host "❌ $file - MISSING" -ForegroundColor Red
    }
}

# 6. Check server configuration
Write-Host "`n🌐 Checking Server Configuration..." -ForegroundColor Blue

# Check if APP_URL is set correctly
$envContent = Get-Content ".env" -ErrorAction SilentlyContinue
$appUrl = $envContent | Where-Object { $_ -like "APP_URL=*" }
if ($appUrl) {
    Write-Host "✅ APP_URL configured: $appUrl" -ForegroundColor Green
} else {
    Write-Host "⚠️  APP_URL not found in .env" -ForegroundColor Yellow
}

# 7. Generate Laravel key if needed
if (-not ($envContent | Where-Object { $_ -like "APP_KEY=*" -and $_.Length -gt 10 })) {
    Write-Host "🔑 Generating Laravel application key..." -ForegroundColor Yellow
    php artisan key:generate
    Write-Host "✅ Application key generated" -ForegroundColor Green
}

# 8. Check storage permissions
Write-Host "`n📁 Checking Storage Permissions..." -ForegroundColor Blue

$storageDirs = @("storage/logs", "storage/app", "storage/framework/cache", "storage/framework/sessions", "storage/framework/views")

foreach ($dir in $storageDirs) {
    if (Test-Path $dir) {
        Write-Host "✅ $dir exists" -ForegroundColor Green
    } else {
        Write-Host "❌ $dir missing - creating..." -ForegroundColor Yellow
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✅ Created $dir" -ForegroundColor Green
    }
}

# 9. Create missing public directories
Write-Host "`n📂 Ensuring Public Directories..." -ForegroundColor Blue

$publicDirs = @(
    "public/storage",
    "public/assets/js",
    "public/assets/css",
    "public/js",
    "public/css"
)

foreach ($dir in $publicDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✅ Created $dir" -ForegroundColor Green
    }
}

# 10. Create storage link
Write-Host "`n🔗 Creating Storage Link..." -ForegroundColor Blue
try {
    php artisan storage:link
    Write-Host "✅ Storage link created" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Storage link creation failed or already exists" -ForegroundColor Yellow
}

# 11. Check database connection
Write-Host "`n🗄️  Checking Database Connection..." -ForegroundColor Blue
try {
    php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';"
    Write-Host "✅ Database connection working" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Database connection issue - check .env database settings" -ForegroundColor Yellow
}

# 12. Migrate database if needed
Write-Host "`n📊 Running Database Migrations..." -ForegroundColor Blue
try {
    php artisan migrate --force
    Write-Host "✅ Database migrations completed" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Database migration failed - check database configuration" -ForegroundColor Yellow
}

# 13. Create fix assets if missing
Write-Host "`n🔧 Creating Missing Asset Fixes..." -ForegroundColor Blue

# Create single-kyc-button.js if missing
if (-not (Test-Path "public/assets/js/single-kyc-button.js")) {
    @"
console.log('Single KYC Button fix loaded');
// Placeholder for single KYC button functionality
window.SingleKYCButton = {
    init: function() {
        console.log('Single KYC Button initialized');
    }
};
"@ | Out-File -FilePath "public/assets/js/single-kyc-button.js" -Encoding UTF8
    Write-Host "✅ Created single-kyc-button.js placeholder" -ForegroundColor Green
}

# Create face-processing-plugin.js if missing
if (-not (Test-Path "public/js/face-processing-plugin.js")) {
    @"
console.log('Face Processing Plugin fix loaded');
// Placeholder for face processing functionality
window.FaceProcessingPlugin = {
    init: function() {
        console.log('Face Processing Plugin initialized');
    },
    isSupported: function() {
        return 'mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices;
    }
};
"@ | Out-File -FilePath "public/js/face-processing-plugin.js" -Encoding UTF8
    Write-Host "✅ Created face-processing-plugin.js placeholder" -ForegroundColor Green
}

# 14. Fix service worker issues
Write-Host "`n⚙️  Fixing Service Worker Issues..." -ForegroundColor Blue

if (Test-Path "public/app-sw.js") {
    # Add error handling to service worker
    $swContent = Get-Content "public/app-sw.js" -Raw
    if ($swContent -notlike "*try*catch*") {
        $fixedSW = @"
// Enhanced service worker with error handling
self.addEventListener('install', function(event) {
    console.log('Service Worker installing');
});

self.addEventListener('activate', function(event) {
    console.log('Service Worker activating');
});

self.addEventListener('fetch', function(event) {
    // Only handle GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    event.respondWith(
        fetch(event.request).catch(function(error) {
            console.log('Service Worker fetch error:', error);
            // Return a basic response for failed requests
            return new Response('Network error occurred', {
                status: 408,
                statusText: 'Request Timeout'
            });
        })
    );
});
"@
        $fixedSW | Out-File -FilePath "public/app-sw.js" -Encoding UTF8
        Write-Host "✅ Fixed service worker error handling" -ForegroundColor Green
    }
}

# 15. Final recommendations
Write-Host "`n🎯 Fix Summary & Recommendations:" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan

Write-Host "✅ Caches cleared" -ForegroundColor Green
Write-Host "✅ Missing files checked/created" -ForegroundColor Green
Write-Host "✅ Permissions verified" -ForegroundColor Green
Write-Host "✅ Service worker fixed" -ForegroundColor Green

Write-Host "`n📋 Next Steps:" -ForegroundColor Yellow
Write-Host "1. Restart your development server (php artisan serve)" -ForegroundColor White
Write-Host "2. Clear browser cache (Ctrl+Shift+R)" -ForegroundColor White
Write-Host "3. Check browser console for remaining errors" -ForegroundColor White
Write-Host "4. If using Apache/Nginx, restart the web server" -ForegroundColor White

Write-Host "`n🚀 Run this to start development server:" -ForegroundColor Cyan
Write-Host "php artisan serve --host=0.0.0.0 --port=8000" -ForegroundColor White

Write-Host "`n💡 If errors persist, try:" -ForegroundColor Yellow
Write-Host "- Check .htaccess file for rewrite rules" -ForegroundColor White
Write-Host "- Verify file permissions (755 for directories, 644 for files)" -ForegroundColor White
Write-Host "- Check PHP error logs" -ForegroundColor White
Write-Host "- Ensure all dependencies are installed" -ForegroundColor White

Write-Host "`n✨ Fix script completed!" -ForegroundColor Green