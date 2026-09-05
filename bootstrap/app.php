<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureTutorVerified;
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
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a tunnel or load balancer, TLS is terminated upstream and the
        // request reaches PHP as plain HTTP. Without trusting the forwarded
        // headers, Laravel builds every URL as http:// while the page is
        // served over https:// — the browser blocks the assets as mixed
        // content and the page renders blank.
        //
        // This also matters for the payment gateway: the callback URL sent to
        // Billplz has to be the https one the outside world can reach.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        // Billplz posts here from its own servers, with no session and no
        // token. The X-Signature is what authenticates it instead, so CSRF
        // would only ever reject a genuine notification.
        //
        // Not covered by the test suite: Laravel skips CSRF entirely when
        // running tests, so this can only be verified against a real request.
        $middleware->validateCsrfTokens(except: [
            'payments/billplz/webhook',
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'tutor.verified' => EnsureTutorVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 500, 503]) && ! app()->environment(['local', 'testing'])) {
                return Inertia::render('Error', [
                    'status' => $status,
                ])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
