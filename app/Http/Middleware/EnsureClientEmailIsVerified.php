<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Solo verificar email para usuarios con rol de Cliente
            if ($user->hasRole('Cliente') && !$user->hasVerifiedEmail()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Tu dirección de correo electrónico no ha sido verificada.',
                        'verification_required' => true
                    ], 403);
                }

                return redirect()->route('verification.notice');
            }
        }

        return $next($request);
    }
}
