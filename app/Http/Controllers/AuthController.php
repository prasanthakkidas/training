<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController 
{
    
    protected function jwt(User $user) {
        $payload = [
            'iss' => "lumen-jwt",       // Issuer of the token
            'sub' => $user->id,         // Subject of the token
            'iat' => time(),            // Time when JWT was issued. 
            'exp' => time() + 60*60     // Expiration time
        ];
        
        return JWT::encode($payload, env('JWT_SECRET'));
    } 

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     * 
     * @param  \App\User   $user 
     * @return mixed
     */
    public function authenticate(Request $request, User $user) {
        $this->validate($request, [
            'email'     => 'required|email',  // formatted as email address
            'password'  => 'required'
        ]);

        // Find the user by email
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json([
                'error' => 'Email does not exist.'
            ], 400);
        }

        // Verify the password and generate the token
        if (Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'token' => $this->jwt($user)
            ], 200);
        }

        // Bad Request response
        return response()->json([
            'error' => 'Email or password is wrong.'
        ], 400);
    }
}