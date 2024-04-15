<?php

namespace App\Http\Controllers;

use App\Models\User;
use phpseclib\Crypt\RSA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $rsa = new RSA();
            $keyPair = $rsa->createKey(2048);

            extract($keyPair);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'public_key' => $publickey,
                'private_key' => $privatekey,
            ]);

            $token = $user->createToken('user')->accessToken;

            DB::commit();

            return response()->json([
                "success" => true,
                "data" =>  new UserResource($user),
                "token" => $token
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching messages.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {

                $token = Auth::user()->createToken('user')->accessToken;

                return response()->json([
                    "success" => true,
                    "data" => new UserResource(Auth::user()),
                    "token" => $token
                ]);
            } else {
                return response()->json([
                    "success" => false,
                    "data" => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching messages.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
