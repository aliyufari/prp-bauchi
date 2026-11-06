<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (!app()->hasDebugModeEnabled() && in_array($response->getStatusCode(), [403, 404, 500, 503])) {
                return Inertia::render('errors/Error', [
                    'status' => $response->getStatusCode(),
                    'message' => $response->getStatusCode() === 403 ? $e->getMessage() : null,
                ])->toResponse($request)->setStatusCode($response->getStatusCode());
            }
            return $response;
        });

        // $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
        //     $status = $response->getStatusCode();

        //     if (app()->hasDebugModeEnabled() || ! in_array($status, [403, 404, 419, 429, 500, 503])) {
        //         return $response;
        //     }

        //     $message = match ($status) {
        //         403 => $e->getMessage() ?: "You don't have permission to do that.",
        //         404 => "That record doesn't exist, or it is outside the area you manage.",
        //         419 => 'Your session expired. Refresh the page and try again.',
        //         429 => 'Too many attempts. Wait a moment and try again.',
        //         default => 'Something went wrong on our side. Please try again.',
        //     };

        //     // A failed save: stay on the page so the user keeps what they typed, and show the reason.
        //     if ($request->header('X-Inertia') && ! $request->isMethod('GET')) {
        //         return back()->with('error', $message);
        //     }

        //     return Inertia::render('errors/Error', ['status' => $status, 'message' => $message])
        //         ->toResponse($request)->setStatusCode($status);
        // });
    })->create();
