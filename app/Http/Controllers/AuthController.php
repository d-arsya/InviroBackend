<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponder;

    /**
     * Get JWT token(login)
     *
     * Mendapatkan JWT token untuk mengakses guarded route
     */
    #[Group('Authentication')]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request['email'])->first();
        if (! $user) {
            return $this->error('Unauthorized', 401, null);
        }
        if (! $token = Auth::claims([
            'email' => $user->email,
        ])->attempt($request->all())) {
            return $this->error('Unauthorized', 401, null);
        }

        return $this->success([
            'token' => $token,
            'expiresIn' => now()
                ->addMinutes(Auth::factory()->getTTL())
                ->setTimezone(config('app.timezone'))
                ->toIso8601String(),
        ]);
    }

    /**
     * Get the autheticated user profile
     */
    #[Group('User')]
    public function me()
    {
        $user = Auth::user();

        return $this->success($user);
    }

    /**
     * Invalidate the JWT (logout)
     *
     * Meng-invalidasi token JWT sehingga tidak bisa dipakai lagi
     */
    #[Group('Authentication')]
    public function logout()
    {
        Auth::logout(true);

        return $this->success(null, 'Success logout');
    }

    /**
     * Get JWT refreshed token
     *
     * Mendapatkan refresh token apabila token JWT sudah expired (max 2 jam setelah login pertama)
     */
    #[Group('Authentication')]
    public function refresh()
    {
        $token = Auth::refresh();

        return $this->success([
            'token' => $token,
            'expiresIn' => now()
                ->addMinutes(Auth::factory()->getTTL())
                ->setTimezone(config('app.timezone'))
                ->toIso8601String(),
        ]);
    }
}
