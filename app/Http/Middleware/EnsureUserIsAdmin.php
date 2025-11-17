<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // usuário precisa estar autenticado
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            return response()->json(['error' => 'Acesso negado.'], 403);
        }

        return $next($request);
    }
}
