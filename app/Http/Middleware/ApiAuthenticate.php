<?php

namespace App\Http\Middleware;

use Closure;
use Log;

use App\User;
use Response;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiAuthenticate
{
    public function handle($request, Closure $next) {
        Log::debug('Authenticating request');
        // Check for api_token existence and check in the database for its existence
        $api_token = $request->header('api_token');

        if ($api_token === NULL) {
            return Response::json([
                'status' => 'error',
                'error' => 'Please specify an api_token to access this route'
            ]);
        }

        $api_tokens = NULL;
        $api_tokens = \DB::table('api_tokens')->where('value', $api_token)->get();

        if (count( $api_tokens ) == 0) {
            Log::error('token not found in database');
            return Response::json([
                'status' => 'error',
                'error' => 'Invalid "api_token", please login first'
            ]);
        }

        // Check if the token is expired
        $expires = $api_tokens[0]->expires;
        if (new \DateTime($expires) < new \DateTime()) {
            return Response::json([
                'status' => 'error',
                'error' => 'Expired "api_token", please login first'
            ]);
        }

        // Log::debug('Authenticated request');
        // Continue the routing process
        return $next($request);
    }
}
