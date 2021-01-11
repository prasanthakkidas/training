<?php

namespace App\Http\Controllers;


use Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;


class AuthController extends BaseController {
    /**
     * 
     */
    protected function jwtSignup(User $user) {
        $payload = [
            'iss' => "lumen-jwt",       
            'sub' => $user->id,         
            'iat' => time(),            
            'exp' => time() + 60*60*24,
        ];
        
        return JWT::encode($payload, env('JWT_SIGNUP'));
    }
    
    public function emailRequest(Request $request){

        $this->validate($request, [
        'email' => 'required|email',
        ]);

        $user = User::where('email', $request->input('email')) -> first();

        if($user->is_email_verified) {
            return response()->json(
                ["message" => "Email already verfied"], 200
            );
        }

        $token = $this->jwtSignup($user);

        $email_address = $request->input('email');
        Mail::raw('Verify Email link: http://localhost:3000/verification?token='.$token, 
            function($message) use($email_address) {
                $message->to($email_address)
                ->subject('Email verfication');
            });

        if(Mail::failures()) {
            return response()->json(
                ['message' => 'Error in sending verify email, Try Again'], 500
            );
        }
        
        return response()->json(
            ['message' => 'Verify Email sent to '.$email_address], 200
        );
    }

    /**
     * Creates a new user in the database.
     * 
     * @param  $request
     * @return response of email verification link.
     */
    public function signUp(Request $request) {
        //validate incoming request 
        $this->validate($request, [
            'name' => 'bail|required|string|max:40',
            'email' => 'bail|required|email|unique:users',
            'password' => 'bail|required|string|
                            min:6|
                            max:15|
                            regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/|',
        ]);

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $plain_password = $request->input('password');
        $user->password = app('hash')->make($plain_password);

        $user->save();
        
        $token = $this->jwtSignup($user);

        $this->emailRequest($request);

        //return verfication link
        // return response()->json([
        //         'message' => "http://localhost:3000/verification?token=".$token,
        //         // 'message' => $token, 
        //     ], 200
        // );
    }

    /**
     * Generates a jwt token with user information encoded in it.
     * 
     * @param $user
     * @return jwt token
     */
    protected function jwt(User $user) {
        $payload = [
            'iss' => "lumen-jwt",       
            'sub' => $user->id,         
            'iat' => time(),            
            'exp' => time() + 60*60*24,
        ];
        
        return JWT::encode($payload, env('JWT_LOGIN'));
    }
    

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     * 
     * @param  $request, $user 
     * @return response
     */
    public function authenticate(Request $request) {
        $this->validate($request, [
            'email'     => 'required|email',  // formatted as email address
            'password'  => 'required'
        ]);

        // Find the user by email
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return response()->json(
                ['message' => 'Incorrect Email or Password'], 400
            );
        }

        if(!$user->is_email_verified) {
            return response()->json(
                ['message' => "Email hasn't been verified"], 401
            );
        }

        // Verify the password and generate the token
        if (Hash::check($request->input('password'), $user->password)) {
            return response()->json(
                ['token' => $this->jwt($user), 'is_admin' => $user->is_admin], 200
            );
        }

        // Bad Request response
        return response()->json(
            ['message' => 'Incorrec Email or wrong'], 400
        );
    }


    /**
     * Verify the user email address
     * 
     * @param $request
     * @return response
     */
    public function verify(Request $request) {
        $token = $request->bearerToken();

        if(!$token) {
            return response()->json(
                ['message' => 'token is not provided'], 401
            );
        }

        try {
            $credentials = JWT::decode($token, env('JWT_SIGNUP'), ['HS256']);
        } catch(ExpiredExpection $e) {
            return response()->json(
                ['message' => 'token is expired'], 400
            );
        } catch(Expection $e) {
            return response()->json(
                ['message' => 'error decoding token'], 400
            );
        }
        
        // find the user using subject claim
        $user = User::find($credentials->sub);

        $user->is_email_verified = true;
        $user->save();

        return response()->json(
            ['message' => 'User account is created'], 201
        );
    }

    /**
     * 
     * 
     */
    protected function jwtForget(User $user){
        $payload = [
            'iss' => "lumen-jwt",       
            'sub' => $user->id,         
            'iat' => time(),            
            'exp' => time() + 60*60*24,     
        ];
        
        return JWT::encode($payload, env('JWT_FORGET'));
    }


    /**
     * 
     * 
     * 
     */
    public function forgetPassword(Request $request){
        $user = User::where('email', $request->input('email'))->first();

        if($user === NULL) {
            return response()->json(
                ['message' => "Incorrect email address"], 400
            );
        }
        //Generate the token
        $token = $this->jwtForget($user);
        $email_address = $request->input('email');

        Mail::raw('Reset Password link: http://localhost:3000/reset-password?token='.$token, 
            function($message) use($email_address) {
                $message->to($email_address)
                ->subject('Reset Password');
            });

        if(Mail::failures()) {
            return response()->json(
                ['message' => 'Error in sending reset password email, Try Again'], 500
            );
        }
        
        return response()->json(
            ['message' => 'Reset Password Email sent to '.$email_address], 200
        );
    }

    /**
     * 
     */
    public function resetPassword(Request $request){
        $this->validate($request, [
            'password'  => 'bail|required|string|
                            min:6|
                            max:15|
                            regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/'
        ]);

        $token = $request->bearerToken();

        if(!$token) {
            // Unauthorized request if token isn't provided
            return response()->json(
                ['message' => 'Unauthorized'], 401
            );
        }

        try {
            $credentials = JWT::decode($token, env('JWT_FORGET'), ['HS256']);
        } catch(ExpiredExpection $e) {
            return response()->json(
                ['message' => 'token is expired'], 408   
            );
        } catch(Expection $e) {
            return response()->json(
                ['message' => 'error decoding token'], 500
            );
        }

        $user =  User::find($credentials->sub);

        $plain_password = $request->input('password');
        $user->password = app('hash')->make($plain_password);

        $user->save();

        return response()->json(
            ['message' => 'Reset password successful'], 200
        );
    }
}