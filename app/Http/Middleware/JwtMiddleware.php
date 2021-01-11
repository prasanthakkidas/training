<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class JwtMiddleware {
    public function handle($request, Closure $next, $guard = null) {
        $token = $request->bearerToken();
        
        if(!$token) {
            // Unauthorized request if token isn't provided
            return response()->json(
                ['error' => 'Unauthorized'], 401
            );
        }

        try {
            $credentials = JWT::decode($token, env('JWT_LOGIN'), ['HS256']);
        } catch(ExpiredExpection $e) {
            return response()->json(
                ['error' => 'token is expired'], 408   
            );
        } catch(Expection $e) {
            return response()->json(
                ['error' => 'error decoding token'], 500
            );
        }
        
        // find the user and store it in a request instance for later use.

        $request->auth = User::find($credentials->sub);

        return $next($request);
    }
}