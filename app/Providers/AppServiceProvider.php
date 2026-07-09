<?php

declare(strict_types=1);

namespace App\Providers;

use App\Exceptions\Auth\NonStatefulAuthGuardException;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Bind the precise stateful auth contract used by the auth Actions.
        // Laravel only binds the generic Guard contract out of the box; the
        // Login/Register/Logout Actions call stateful methods (attempt, login,
        // logout), so depending on StatefulGuard makes the contract correct for
        // both static analysis and runtime. Surfacing a non-stateful default
        // guard here is a config error worth failing loudly at resolution time.
        $this->app->bind(static function ($app): StatefulGuard {
            $guard = $app->make(AuthManager::class)->guard();

            if (!$guard instanceof StatefulGuard) {
                throw NonStatefulAuthGuardException::forGuard($guard::class);
            }

            return $guard;
        });

        $this->app->singleton(
            \App\Services\Payments\Contracts\PaymentGatewayInterface::class,
            \App\Services\Payments\StripeGateway::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Waitlist\WaitlistEntryExpired::class,
            \App\Listeners\Waitlist\NotifyWaitlistOnExpiredListener::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Payments\PaymentCompleted::class,
            \App\Listeners\Payments\GenerateInvoiceOnPaymentCompleted::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Payments\PaymentCompleted::class,
            \App\Listeners\Payments\GeneratePayoutOnPaymentCompleted::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Payments\RefundProcessed::class,
            \App\Listeners\Payments\IssueCreditNoteOnRefundProcessed::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Payments\RefundProcessed::class,
            \App\Listeners\Payments\AdjustPayoutOnRefundProcessed::class,
        );
    }
}
