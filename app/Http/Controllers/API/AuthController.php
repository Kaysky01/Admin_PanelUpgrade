<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        // HAPUS TOKEN LAMA
        $user->tokens()->delete();

        // BUAT TOKEN SEKALI SAJA
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user'  => $user,
                'token' => $token, // ⬅️ TOKEN INI YANG DIPAKAI FLUTTER
            ]
        ]);
    }

    public function google(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email',
            'google_id' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->orWhere('google_id', $request->google_id)
            ->first();

        if (!$user) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'google_id' => $request->google_id,
                'password'  => Hash::make(uniqid()),
            ]);
        }

        if (!$user->google_id) {
            $user->update(['google_id' => $request->google_id]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user'  => $user,
                'token' => $token,
            ],
        ]);
    }
}
