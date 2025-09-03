<?php

namespace App\Providers;

use App\Helpers\IpHelper;
use App\Models\Contact;
use App\Models\Invitee;
use App\Models\Meeting;
use App\Models\User;
use App\Observers\ContactObserver;
use App\Observers\InviteeObserver;
use App\Observers\MeetingObserver;
use App\Observers\UserObserver;
use App\Traits\ModelRelation;
use App\Traits\SocketService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    use ModelRelation, SocketService;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Period Tracker Services
        $this->app->singleton(\App\Services\PeriodTrackerService::class, function ($app) {
            return new \App\Services\PeriodTrackerService();
        });

        $this->app->singleton(\App\Services\HealthAnalyticsService::class, function ($app) {
            return new \App\Services\HealthAnalyticsService();
        });

        $this->app->singleton(\App\Services\AIInsightsService::class, function ($app) {
            return new \App\Services\AIInsightsService();
        });

        $this->app->singleton(\App\Services\SecurityAuditService::class, function ($app) {
            return new \App\Services\SecurityAuditService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        Contact::observe(ContactObserver::class);
        Meeting::observe(MeetingObserver::class);
        Invitee::observe(InviteeObserver::class);

        Activity::saving(function (Activity $activity) {
            $activity->properties = $activity->properties->put('ip', IpHelper::getClientIp());
            $activity->properties = $activity->properties->put('user_agent', \Request::header('User-Agent'));
        });

        $this->socketConfig();

        JsonResource::withoutWrapping();

        Relation::morphMap($this->relations());
    }
}
