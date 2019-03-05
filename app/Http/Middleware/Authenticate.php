<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

//för jwt
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use \UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Hash;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        /* original
        if ($this->auth->guard($guard)->guest()) {
            //return response('Unauthorized.', 401);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
        */

        $authorized = false;

        if (!$request->has('api_key')) {

            $token = $request->get('token');
                    
            if(!$token) {
                // Unauthorized response if token not there
                return response()->json([
                    'error' => 'Token not provided.'
                ], 401);
            }

            try {
                //JWT::$leeway = 60; 
                //Nyckeln är base64kodad när JWT skapas
                $secretKey = base64_decode(env("JWT_SECRET", "missing"));
                $credentials = JWT::decode($token, $secretKey, ['HS512']);
            } catch(ExpiredException $e) {
                return response()->json([
                    'error' => 'Provided token is expired.'
                ], 400);
            } catch(SignatureInvalidException $e) {
                return response()->json([
                    'error' => 'Signature is invalid.'
                ], 400);
            } catch(UnexpectedValueException $e) {
                return response()->json([
                    'error' => "Invalid token"
                ], 400);
            } catch(DomainException $e) {
                return response()->json([
                    'error' => "Invalid token"
                ], 400);
            } catch(Exception $e) {
                return response()->json([
                    'error' => 'An error while decoding token.'
                ], 400);
            }
            $authorized = true;
            return $next($request);
            
        } else {
            if ($this->checkApiKey($request)) {
                return $next($request);
            } else {
                //return response()->json($credentials);
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
        
    }

    /**
     * @param Request $request
     *
     * @return User|null
     */
    private function checkApiKey($request) {
        if (!$request->has('api_key')) {
                return;
        } else {
            if($request->input('api_key') == env("API_KEY_READ", "missing")){
                return true;
            }
        }
        return;
    }
}
