<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MainController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:5|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->save();

        return response()->json([
            'message' => 'User successfully created',
            'data' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'remember_me' => 'boolean',
        ]);

        // get user credentials
        $userCredentials = request(['email', 'password']);

        if (!Auth::attempt($userCredentials)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // get user from the request
        $user = $request->user();
        $tokenObj = $user->createToken('User Personal Access Token');
        $token = $tokenObj->token;

        // set expires to a time in the future if remember me is active
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(2);
        }

        // save token
        $token->save();

        return response()->json([
            'access_token' => $tokenObj->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($tokenObj->token->expires_at)->toDateString(),
        ]);
    }

    public function logout(Request $request)
    {
        // revoke user token
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successful logout'
        ], 200);
    }
}
