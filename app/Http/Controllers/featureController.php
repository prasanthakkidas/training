<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  App\Models\User;

class featureController extends Controller {

    public function allUsers(Request $request) {
        $user = User::all(); 
        return response()->json(['users' => $user, 'message' => 'All users'], 200);
    }

    public function singleUser(Request $request, $id) {
        $user = User::find($id); 
        return response()->json(['user' => $user], 200);
    }

    public function update(Request $request, $id) {
        $user = User::find($id); 

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $plainPassword = $request->input('password');
        $user->password = app('hash')->make($plainPassword);

        $user->save();

        return response()->json(['user' => $user, 'message' => 'Updated successfully'], 200);
    }

    public function delete(Request $request, $id) {
        User::destroy($id);

        return response()->json(['message' => 'Deleted successfully'], 200);
    }

}