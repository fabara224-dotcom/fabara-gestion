<?php

namespace App\Providers;

use App\Support\CurrentTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        Blade::directive('money', fn ($e) => "<?php echo e(\\App\\Support\\Money::format($e, \$currentTenant->currency ?? 'GNF')); ?>");
        Blade::directive('qty', fn ($e) => "<?php echo e(\\App\\Support\\Money::qty($e)); ?>");

        // Protection contre les abus : 120 requêtes/min par utilisateur sur l'API, 60 sur la synchro
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('sync', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
    }
}
