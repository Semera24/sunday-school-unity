<?php

namespace App\Providers;

use App\Models\Media;
use App\Models\Setting;
use App\Models\User;
use App\Policies\MediaPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Support\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $trustedProxies = config('app.trusted_proxies');
        if (is_string($trustedProxies) && $trustedProxies !== '') {
            TrustProxies::at($trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)));
        }
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables[] = 'PHP_INI_SCAN_DIR';
        }
        Date::use(CarbonImmutable::class);
        $this->configureQueryBuilderMacros();
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        DB::prohibitDestructiveCommands(app()->isProduction());
        Password::defaults(fn (): ?Password => app()->isProduction() ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised() : null);
        Event::listen(fn (Login $event) => ActivityLogger::record(actor: $event->user, event: 'auth.login', description: 'Signed in successfully.', subject: $event->user));
        Event::listen(function (Logout $event): void {
            if ($event->user !== null) {
                ActivityLogger::record(actor: $event->user, event: 'auth.logout', description: 'Signed out successfully.', subject: $event->user);
            }
        });
    }

    private function configureQueryBuilderMacros(): void
    {
        EloquentBuilder::macro('searchLike', function (array $columns, string $term): EloquentBuilder {
            $pattern = '%'.str($term)->trim().'%';

            return $this->where(function (EloquentBuilder $query) use ($columns, $pattern): void {
                foreach ($columns as $column) {
                    $query->orWhereRaw("lower({$column}) like lower(?)", [$pattern]);
                }
            });
        });
        QueryBuilder::macro('searchLike', function (array $columns, string $term): QueryBuilder {
            $pattern = '%'.str($term)->trim().'%';

            return $this->where(function (QueryBuilder $query) use ($columns, $pattern): void {
                foreach ($columns as $column) {
                    $query->orWhereRaw("lower({$column}) like lower(?)", [$pattern]);
                }
            });
        });
    }
}
