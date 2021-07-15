<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action
        $user =$request->auth;
        $isAdmin = $user->role  =='admin';
        if(! $isAdmin) {
            // Unauthorized response if token not there
            return response()->json([
                'error' => 'You are not authorized to access this route'
            ], 401);
        }


        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
