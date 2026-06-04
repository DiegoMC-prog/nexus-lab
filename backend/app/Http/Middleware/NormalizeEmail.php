<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('email')) {
            $email = $request->input('email');
            if (is_string($email)) {
                $request->merge([
                    'email' => User::normalizeEmail($email),
                ]);
            }
        }

        return $next($request);
    }
}
