<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use  App\Models\User;
use  App\Models\Task;

class featureController extends Controller {
    /**
     * @param $request
     * @return response 
     */
    public function allUsers(Request $request) {
        $user = $request->auth;

        if($user->is_admin !== "Admin") { 
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }

        $id = $request->get('keyword'); 
        if($id === NULL) {
            $users = User::select('id', 'name', 'email', 'is_admin', 'deleted_at')->get();
            return response()->json(
                ['users' => $users], 200
            );
        }
        
        $users = User::select('id', 'name', 'email', 'is_admin', 'deleted_at')->where('name', 'like', '%'.$id.'%')->orwhere('email', 'like', '%'.$id.'%')->get();
        return response()->json(
            ['users' => $users], 200
        );
    }

    /**
     * @param $request
     * @return response of corresponding user data
     */
    public function singleUser(Request $request) {
        return response()->json(
            ['user' => $request->auth], 200
        );
    }

     /**
      * checks if user is admin then soft deletes corresponding user and tasks assigned to him if any
      * @param $request, $id
      * @return response
      */
    public function delete(Request $request) {
        $this->validate($request, [
            'id' => 'exists:users',  
        ]);

        $user = $request->auth;

        if(!$user->is_admin) {
            return response()->json(
                ['message' => 'Not an admin'], 401
            );
        }

        $id = $request->get('id');
        User::destroy($id);
        Task::where('user_id', $id)->delete();

        return response()->json(
            ['message' => 'Deleted successfully'], 200
        );
    }

    public function createUser(Request $request) {
        $this->validate($request, [
            'name' => 'required|string|max:40',
            'email' => 'bail|required|email|unique:users',
            'password' => 'required|string'
        ]);
        
        if($request->auth->is_admin !== "Admin"){
            return response()->json(
                ['message' => 'Forbidden'], 403
            );
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $temporary_password = $request->input('password');
        $user->password = app('hash')->make($temporary_password);
        $user->is_email_verified = true;
        
        $user->save();

        return response()->json(
            ['message' => 'New user account is created'], 201
        );
    }

    public function changePassword(Request $request){
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'bail|required|string|
                                min:6|
                                max:15|
                                regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/'
                                
        ]);
        
        $user = $request->auth;
        
        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(
                ['message' => 'Incorrect Old password'], 400
            );
        }


        $plain_password = $request->input('new_password');
        $user->password = app('hash')->make($plain_password);

        $user->save();

        return response()->json(
            ['message' => 'Password changed'], 200
        );
    }
}