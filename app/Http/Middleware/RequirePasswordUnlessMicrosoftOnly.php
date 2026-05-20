<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordUnlessMicrosoftOnly extends RequirePassword
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null): Response
    {
        $user = $request instanceof Request ? $request->user() : null;

        if ($user && method_exists($user, 'mustUseMicrosoftLogin') && $user->mustUseMicrosoftLogin()) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute, $passwordTimeoutSeconds);
    }
}
