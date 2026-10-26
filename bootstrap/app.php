<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\MaintenanceModeMiddleware::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'prevent.staff' => \App\Http\Middleware\PreventStaffAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Database Connection Failures
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            $isConnectionError = in_array($e->getCode(), [2002, 1045, '2002', 'HY000'])
                || str_contains($e->getMessage(), '2002')
                || str_contains($e->getMessage(), 'Operation not permitted')
                || str_contains($e->getMessage(), 'Connection refused')
                || str_contains($e->getMessage(), 'No such file or directory');

            if ($isConnectionError) {
                \Illuminate\Support\Facades\Log::critical('Database connection failure: ' . $e->getMessage());

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The database service is temporarily unavailable. Please try again shortly.',
                    ], 503);
                }

                return response()->view('errors.db', [], 503);
            }

            return null;
        });

        // 2. Mail Transport Exceptions (SMTP authentication, connection failure, timeouts)
        $exceptions->render(function (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error('SMTP/Mailer Transport Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);

            $message = 'The action was processed, but the email notification could not be delivered due to an SMTP authentication or mail server issue. The technical log has recorded this.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        });

        // 3. Global Exception Handler - Never show raw Laravel error pages to users
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (app()->runningUnitTests()) {
                return null;
            }

            // Allow Laravel's built-in handlers for Validation and Authentication
            if ($e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            // Handle expired sessions / CSRF token mismatch
            if ($e instanceof \Illuminate\Session\TokenMismatchException) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Your session expired. Please refresh the page and try again.');
            }

            // Handle unauthorized access gracefully
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return redirect()->back()
                    ->with('error', $e->getMessage() ?: 'You are not authorized to perform this action.');
            }

            // Always log the full exception details with stack trace in storage/logs/laravel.log
            \Illuminate\Support\Facades\Log::error('Unhandled Application Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
            ]);

            $errorMessage = config('app.debug')
                ? 'Error: ' . $e->getMessage()
                : 'An unexpected error occurred while processing your request. Please try again or contact support.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            // Check if we can safely redirect back to avoid infinite loops on GET
            $previousUrl = url()->previous();
            $currentUrl = $request->fullUrl();

            if ($previousUrl && $previousUrl !== $currentUrl) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }

            // On direct GET request failure, redirect to dashboard with the alert
            $homeUrl = url('/');
            if ($currentUrl !== $homeUrl) {
                return redirect($homeUrl)->with('error', $errorMessage);
            }

            // If already on the home page and it crashed on GET, let fallback error view render to avoid redirect loop
            return null;
        });
    })->create();
