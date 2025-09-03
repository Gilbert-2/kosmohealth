<?php

namespace App\Providers;

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

class ChatbotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChatbotController::class, function ($app) {
            return new ChatbotController();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register a directive to include the chatbot in Blade templates
        Blade::directive('chatbot', function ($expression) {
            $expression = $expression ?: "'default'";
            return "<?php echo app(\\App\\Http\\Controllers\\ChatbotController::class)->getScript($expression); ?>";
        });
        
        // Share the chatbot controller with all views
        View::share('chatbotController', app(ChatbotController::class));
    }
}
