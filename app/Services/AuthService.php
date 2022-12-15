<?php

namespace App\Services;

use App\Events\ForgotPassword;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthService
{
    use ApiResponse;

    public function createAccount(array $data)
    {
        try {
            
            $user = User::create($data);
            return $this->success('success', 'Account created successfully', $user, 201);
        } catch (\Exception $e) {
            return $this->error('error', $e->getMessage(), null, 500);
        }
    }

    public function login(array $data)
    {
        try {
            $user = User::where('email', $data['email'])->first();
            // dd($user);
            if (!$user || !Hash::check($data['password'], $user->password)) {
                return $this->error('error', 'Incorrect email/password', null, 400);
            }

            $token = $user->createToken($data['email'])->plainTextToken;
            return $this->success('success', 'Login successful', ['token' => $token, 'user' => $user], 200);
        } catch (\Exception $e) {
            return $this->error('error', $e->getMessage(), null, 500);
        }
    }

    public function forgotPassword(array $data)
    {
        try {
            $user = User::where('email', $data['email'])->first();
            if($user == null) {
                return $this->error('error', 'Account does not exist', null, 400);
            }
            $user->otp = random_int(100000, 999999);
            $user->save();

            event(new ForgotPassword($user));
            return $this->success('success', 'A reset code has been sent to '. $data['email'], $user, 200);
        } catch (\Exception $e) {
            return $this->error('error', $e->getMessage(), null, 500);
        }
    }

    public function resetPassword(array $data)
    {
        try {
            $user = User::where('email', $data['email'])->first();
            if ($user == null) {
                return $this->error('error', 'Account does not exist', null, 400);
            }
            if ($user->otp == null) {
                return $this->error('error', 'Invalid reset code', null, 400);
            }

            $user->otp = null;
            $user->password = Hash::make($data['password']);
            $user->save();
            $user->tokens()->delete();
            return $this->success('success', 'Password reset successful', $user, 200);
        } catch (\Exception $e) {
            return $this->error('error', $e->getMessage(), null, 500);
        }
    }


    
}
