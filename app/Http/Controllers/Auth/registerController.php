<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class registerController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

    // Validasi input
    $request->validate([
        'name' => 'required',
        'email' => 'required',
        'password' => 'required',
    ]);

    // Jika validasi lolos, buat user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

        // $token = $user->createToken('apitodos')->plainTextToken;

        return response()->json([
            'user' => $user,
            // 'token' => $token,
        ], 201);
    }
}