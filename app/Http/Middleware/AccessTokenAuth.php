<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AccessTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');

        $user = User::where('access_token', $token)
            ->first();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $request->merge(['auth_user' => $user]);
        return $next($request);
    }
}
